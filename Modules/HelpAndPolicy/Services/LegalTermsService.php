<?php

namespace Modules\HelpAndPolicy\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;
use Modules\HelpAndPolicy\Http\Resources\LegalTermResource;
use App\Helpers\TranslateHelper as Translate;

class LegalTermsService
{
    private LegalTerm $model;

    function __construct(LegalTerm $model)
    {
        $this->model = $model;
    }

    public function getAll($request)
    {
        $params = $request->all();

        $query = $this->model->query()->select(['id', 'type', 'html']);
        $query = $query->where('type', $params['type'] ?? 'main_page');

        $data = $query->get();

        return responseHelper(__('Legal Terms retrieved successfully.'), 200, LegalTermResource::collection($data));
    }
    public function getAllAdmin($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model
            ->query()
            ->select(['id', 'type', 'html'])
            ->where('type', $params['type'] ?? 'main_page');

        $terms = $query->orderBy('id', 'desc')->get();

        $data = $terms->map(function ($term) {
            $html = $term->getRawOriginal('html');

            if (is_string($html)) {
                $decoded = json_decode($html, true);
                $html = json_last_error() === JSON_ERROR_NONE ? $decoded : $html;
            }

            return [
                'id' => $term->id,
                'type' => $term->type,
                'html' => $html,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Legal Terms retrieved successfully.',
            'data' => $data,
        ], 200);
    }

    public function privacyAndPolicy()
    {
        $data = $this->model
            ->query()
            ->select(['html'])
            ->where('type', 'main_page')
            ->first();

        if (!$data) {
            return responseHelper(__('Not found.'), 404);
        }

        $json = $data->getRawOriginal('html');

        $translations = json_decode($json, true);

        $lang = app()->getLocale();

        //$html = $translations[$lang] ?? $translations['en'];
        $html = $translations['en'];

        return Response::make($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }


    public function update($request, $type): JsonResponse
    {
        $validated = $request->validated();

        $languages = ['az', 'ru', 'en', 'tr'];

        $html = $validated['html'] ?? ['az' => ''];

        foreach ($languages as $lang) {
            if (empty($html[$lang])) {
                $html[$lang] = $this->translateHtmlPreserveTags($html['az'], $lang);
            }
        }

        unset($validated['html']);

        $legalTerm = handleTransaction(function () use ($validated, $html) {
            $record = $this->model->where('type', $validated['type'])->firstOrFail();
            $record->update(array_merge($validated, ['html' => $html]));
            return $record->refresh();
        }, 'Legal Terms updated successfully.', LegalTermResource::class);

        return $legalTerm;
    }


    private function translateHtmlPreserveTags(string $html, string $lang): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//text()') as $node) {
            $text = trim($node->nodeValue);
            if ($text !== '') {
                $node->nodeValue = Translate::translate($text, $lang);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHTML = '';
        foreach ($body->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }

        return $innerHTML;
    }


}
