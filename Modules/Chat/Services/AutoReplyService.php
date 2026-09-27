<?php

namespace Modules\Chat\Services;

use App\Helpers\TranslateHelper as Translate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Http\Entities\AutoReply;
use Modules\Chat\Http\Resources\AutoReplyResource;

class AutoReplyService
{
    private AutoReply $reply;

    public function __construct(AutoReply $reply)
    {
        $this->reply = $reply;
    }

    public function getAll()
    {
        $replies = $this->reply->latest()->get();

        return responseHelper(__('Replies List Retriewed Successfully'),
            200,
            AutoReplyResource::collection($replies)
        );
    }

    public function create($request)
    {
        $data = $request->validated();

        return handleTransaction(function () use ($data) {
            $languages = ['az', 'ru', 'en', 'tr'];

            $question = $data['question'] ?? ['az' => ''];

            foreach ($languages as $lang) {
                if (empty($question[$lang])) {
                    $question[$lang] = Translate::translate($question['az'], $lang);
                }
            }

            $answer = $data['answer'] ?? ['az' => ''];

            foreach ($languages as $lang) {
                if (empty($answer[$lang])) {
                    $answer[$lang] = Translate::translate($answer['az'], $lang);
                }
            }

            /*
             * Embedding yalnız AZ question əsasında yaradılır.
             * 4 dili bir embedding-ə qatanda match-lər qarışa bildiyi üçün bu daha stabil variantdır.
             */
            $embedding = $this->generateTextEmbedding($question['az'] ?? '');

            $finalData = array_merge($data, [
                'question' => $question,
                'answer' => $answer,
                'embedding' => $this->vectorToPgString($embedding),
            ]);

            return $this->reply->create($finalData);
        }, 'Auto reply added successfully.', AutoReplyResource::class);
    }

    public function update($id, $request)
    {
        $data = $request->validated();

        return handleTransaction(function () use ($id, $data) {
            $reply = $this->reply->findOrFail($id);

            if (isset($data['question'])) {
                $oldQuestion = $reply->getTranslations('question');
                $newQuestion = array_merge($oldQuestion, $data['question']);

                $data['question'] = $newQuestion;

                /*
                 * Question dəyişəndə embedding yenidən yalnız AZ mətnindən yaradılır.
                 */
                $embedding = $this->generateTextEmbedding($newQuestion['az'] ?? '');
                $data['embedding'] = $this->vectorToPgString($embedding);
            }

            $reply->update($data);

            return $reply;
        }, 'Auto reply updated successfully.', AutoReplyResource::class);
    }

    public function delete($id)
    {
        return handleTransaction(function () use ($id) {
            $reply = $this->reply->findOrFail($id);
            $reply->delete();

            return null;
        }, 'Auto reply deleted successfully.');
    }

    public function getAutoResponse($userMessage)
    {
        $search = $this->normalizeMessage((string) $userMessage);
        $appLocale = app()->getLocale();

        if ($search === '') {
            return null;
        }

        /*
         * 1. Mini AI semantic search
         * Threshold 0.38 saxlanılıb ki, əlaqəsiz cavablar azalısın.
         */
        $searchEmbedding = $this->generateTextEmbedding($search);

        if ($searchEmbedding) {
            $vector = $this->vectorToPgString($searchEmbedding);

            $match = $this->reply
                ->select('answer', 'question')
                ->selectRaw('embedding <=> ?::vector as distance', [$vector])
                ->whereNotNull('embedding')
                ->orderBy('distance', 'asc')
                ->first();

            if ($match && (float) $match->distance <= 0.38) {
                $answers = $match->getTranslations('answer');

                return $answers[$appLocale] ?? $answers['az'] ?? null;
            }
        }

        /*
         * 2. Fallback: old pg_trgm search
         * App locale hansı dildirsə, həmin question field-i ilə müqayisə edir.
         */
        DB::statement('SET pg_trgm.similarity_threshold = 0.25');

        $safeLocale = in_array($appLocale, ['az', 'en', 'ru', 'tr'], true) ? $appLocale : 'az';

        $match = $this->reply
            ->select('answer', 'question')
            ->selectRaw("similarity((question->>'{$safeLocale}')::text, ?::text) as score", [$search])
            ->whereRaw("(question->>'{$safeLocale}')::text % ?::text", [$search])
            ->orderBy('score', 'desc')
            ->first();

        if ($match && (float) $match->score >= 0.25) {
            $answers = $match->getTranslations('answer');
        
            return $answers[$safeLocale] ?? $answers['az'] ?? null;
        }

        return false;
    }

    private function generateTextEmbedding(?string $text): ?array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->post('http://127.0.0.1:8766/embed/text', [
                    'text' => $text,
                ]);

            if (!$response->successful()) {
                Log::warning('Text embedding service failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (
                !isset($data['embedding']) ||
                !is_array($data['embedding']) ||
                (($data['dim'] ?? null) !== 384)
            ) {
                Log::warning('Invalid text embedding response', [
                    'response' => $data,
                ]);

                return null;
            }

            return $data['embedding'];
        } catch (\Throwable $e) {
            Log::warning('Text embedding request exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function vectorToPgString(?array $embedding): ?string
    {
        if (!$embedding) {
            return null;
        }

        return '[' . implode(',', $embedding) . ']';
    }
    
    private function normalizeMessage(string $message): string
    {
        $message = mb_strtolower(trim($message), 'UTF-8');
    
        $replacements = [
            'unvan' => 'ünvan',
            'adres' => 'ünvan adres',
            'address' => 'ünvan adres',
            'location' => 'lokasiya ünvan',
            'lokasiya' => 'lokasiya ünvan',
            'konum' => 'lokasiya ünvan',
            'magaza' => 'mağaza',
            'magazaniz' => 'mağazanız',
            'hardadi' => 'haradadır',
            'hardadir' => 'haradadır',
            'haradi' => 'haradadır',
            'gelim' => 'gəlim',
            'gonder' => 'göndər',
            'gonderin' => 'göndərin',
            'atin' => 'atın',
            'odenis' => 'ödəniş',
            'odeme' => 'ödəniş',
            'kartla' => 'kartla',
            'catdirilma' => 'çatdırılma',
            'catdir' => 'çatdır',
            'karqo' => 'karqo çatdırılma',
            'kargo' => 'karqo çatdırılma',
            'sifaris' => 'sifariş',
            'zakaz' => 'sifariş',
        ];
    
        foreach ($replacements as $from => $to) {
            $message = str_replace($from, $to, $message);
        }
    
        $message = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message);
        $message = preg_replace('/\s+/u', ' ', $message);
    
        return trim($message);
    }
}
