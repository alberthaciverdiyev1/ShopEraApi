<?php

namespace Modules\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'category' => $this->category,
            'author_name' => $this->author_name ?? 'Admin',
            'author_image' => $this->author_image,
            'tags' => $this->tags ?? [],
            'views' => (int) $this->views,
            'published_at' => $this->published_at ? $this->published_at->toISOString() : $this->created_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
