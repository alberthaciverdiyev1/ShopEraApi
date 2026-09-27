<?php

namespace Modules\HelpAndPolicy\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\HelpAndPolicy\Http\Entities\Faq;
use Modules\HelpAndPolicy\Http\Resources\FaqResource;
use App\Helpers\TranslateHelper as Translate;
use Illuminate\Support\Str;

class FaqService
{
    private Faq $model;

    function __construct(Faq $model)
    {
        $this->model = $model;
    }

    public function getAll($request)
    {
        $params = $request->all();
        $cacheKey = 'faq_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.faq_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'title', 'description', 'type'])->orderBy('id', 'desc');

            if (isset($params['type'])) $query->where('type', $params['type']);

            return $query->get();
        });

        return responseHelper(__('Faqs retrieved successfully.'),200, FaqResource::collection($data));
    }
    public function getAllAdmin($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model->query()->select(['id', 'title', 'description', 'type']);

        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }

        $faqs = $query->orderBy('id', 'desc')->get();

        $data = $faqs->map(function ($faq) {
            $title = $faq->getRawOriginal('title');
            $description = $faq->getRawOriginal('description');

            if (is_string($title)) {
                $decoded = json_decode($title, true);
                $title = json_last_error() === JSON_ERROR_NONE ? $decoded : $title;
            }

            if (is_string($description)) {
                $decoded = json_decode($description, true);
                $description = json_last_error() === JSON_ERROR_NONE ? $decoded : $description;
            }

            return [
                'id' => $faq->id,
                'title' => $title,
                'description' => $description,
                'type' => $faq->type,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Faqs retrieved successfully.',
            'data' => $data,
        ], 200);
    }


    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $languages = ['az', 'ru', 'en', 'tr'];

        $title = $validated['title'] ?? ['az' => ''];
        foreach ($languages as $lang) {
            if (empty($title[$lang])) {
                $title[$lang] = Translate::translate($title['az'], $lang);
            }
            $title[$lang] = Str::title($title[$lang]);
        }

        $description = $validated['description'] ?? ['az' => ''];
        foreach ($languages as $lang) {
            if (empty($description[$lang])) {
                $description[$lang] = Translate::translate($description['az'], $lang);
            }
            $description[$lang] = ucfirst($description[$lang]);
        }

        unset($validated['title'], $validated['description']);

        return handleTransaction(function () use ($validated, $title, $description) {
            $faq = $this->model->create($validated);
            $faq->update([
                'title' => $title,
                'description' => $description,
            ]);

            return $faq->refresh();
        }, 'Faq added successfully.', FaqResource::class);
    }


    public function update($request,int $id)
    {
        $validated = $request->validated();

        $faq = handleTransaction(
            function () use ($validated, $id) {
                $faq = $this->model->findOrFail($id);
                $faq->update($validated);
                return $faq->refresh();
            },
            'Faq updated successfully.',
            FaqResource::class
        );

        Cache::forget('faq_list_*');

        return $faq;
    }

    public function delete($id)
    {
        $response = handleTransaction(
            function () use ($id) {
                $faq = $this->model->findOrFail($id);
                $faq->delete();
                return $faq;
            },
            'Faq deleted successfully.'
        );

        Cache::forget('faq_list_*');

        return $response;
    }
}
