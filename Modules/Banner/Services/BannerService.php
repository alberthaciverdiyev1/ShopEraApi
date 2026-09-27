<?php

namespace Modules\Banner\Services;

use Modules\Banner\Http\Entities\Banner;
use Modules\Banner\Http\Resources\BannerResource;

class BannerService
{
    private Banner $model;

    function __construct(Banner $model)
    {
        $this->model = $model;
    }

    public function getAll($request): array|\Illuminate\Http\JsonResponse
    {
        $query = $this->model::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        $banners = $request->has('page')
            ? $query->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100))
            : $query->latest()->get();

        return responseHelper(__('Banners retrieved successfully'), 200, BannerResource::collection($banners));
    }

    public function add($request)
    {
        if (!$request->hasFile('image')) {
            return responseHelper(__('Image is required'), 422);
        }

        if (!$request->filled('type')) {
            return responseHelper(__('Type is required'), 422);
        }

        return handleTransaction(function () use ($request) {

            $file = $request->file('image');
            $secondImage = $request->file('second_image');

            if (!$file->isValid()) {
                return responseHelper(__('Invalid image file'), 422);
            }

            $path = $file->store('banner', 'public');

            $secondPath = null;

            if ($secondImage) {
                if (!$secondImage->isValid()) {
                    return responseHelper(__('Invalid second image file'), 422);
                }

                $secondPath = $secondImage->store('banner', 'public');
            }

            $this->model->create([
                'image' => $path,
                'second_image' => $secondPath,
                'type' => $request->input('type'),
                'url' => $request->input('url'),
            ]);

            return responseHelper(__('Banner added successfully'), 200);

        }, 'Error occurred while adding banner');
    }


    public function delete($id)
    {
        $banner = $this->model::find($id);
        if (!$banner) {
            return responseHelper(__('Banner not found'), 404);
        }

        return handleTransaction(function () use ($banner) {
            $banner->delete();

            return responseHelper(__('Banner deleted successfully'), 200);
        }, 'Error occurred while deleting banner');
    }

}
