<?php

namespace Modules\Popup\Services;

use Illuminate\Http\Request;
use Modules\Popup\Http\Entities\Popup;
use Modules\Popup\Http\Resources\PopupResource;

class PopupService
{
    private Popup $model;

    function __construct(Popup $model)
    {
        $this->model = $model;
    }

    public function list($request)
    {
        $popups = $request->has('page')
            ? $this->model->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100))
            : $this->model->latest()->get();
        return responseHelper(__('Popups retrieved successfully'), 200, PopupResource::collection($popups));
    }

    public function showOne()
    {
        $popups = $this->model->where('show_on_home_page', true)->first();
        if (!$popups) {
            return responseHelper(__('Popups not found.'), 404, []);
        }
        return responseHelper(__('Popups retrieved successfully'), 200, PopupResource::make($popups));
    }


//    public function add($request)
//    {
//        $validated = $request->validated();
//        $image = $validated['image'];
//        $url = compressAndUploadImage($image, 'popups', 'popup');
//        $relativePath = str_replace(url('/'), '', $url);
//
//        $validated['image'] = ltrim($relativePath, '/');
//
//        return handleTransaction(function () use ($validated) {
//            $this->model->create($validated);
//        }, 'Popup added successfully.', [], 201);
//    }


    public function add($request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $url = compressAndUploadImage($request->file('image'), 'popups', 'popup');
            $validated['video'] = null;
            $relativePath = str_replace(url('/'), '', $url);
            $validated['image'] = ltrim($relativePath, '/');

        } elseif ($request->hasFile('video')) {
            $url = compressAndUploadVideo($request->file('video'), 'popups/videos', 'video');
            $relativePath = str_replace(url('/'), '', $url);
            $validated['video'] = ltrim($relativePath, '/');
            $validated['image'] = null;
        }

        return handleTransaction(function () use ($validated) {
            return $this->model->create($validated);
        }, 'Popup added successfully.', [], 201);
    }

    public function showHome(int $id)
    {
        $popup = $this->model->find($id);

        if (!$popup) {
            return responseHelper(__('Popup not found.'), 404);
        }
        if ($popup->show_on_home_page) {

            $popup->update(['show_on_home_page' => false]);
        } else {
            $popup->update(['show_on_home_page' => true]);
        }

        $this->model
            ->where('id', '!=', $id)
            ->update(['show_on_home_page' => false]);

        return responseHelper(__('Popup updated successfully.'),
            200,
            new PopupResource($popup)
        );
    }


    public function delete(int $id)
    {
        $popup = $this->model->find($id);

        if (!$popup) {
            return responseHelper(__('Popup not found.'), 404);
        }

        $popup->delete();

        return responseHelper(__('Popup deleted successfully.'), 200);
    }

}
