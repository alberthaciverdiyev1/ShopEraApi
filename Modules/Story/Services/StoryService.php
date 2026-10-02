<?php

namespace Modules\Story\Services;

use Modules\Story\Entities\Story;
use Modules\Story\Http\Resources\StoryResource;

class StoryService
{
    public function __construct(private readonly Story $model) {}

    public function active()
    {
        $stories = $this->model->newQuery()
            ->with(['product.images'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get();

        return responseHelper(__('Stories retrieved successfully.'), 200, StoryResource::collection($stories));
    }
}
