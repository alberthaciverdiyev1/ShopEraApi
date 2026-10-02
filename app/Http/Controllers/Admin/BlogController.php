<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Services\BlogService;

class BlogController extends ResourceController
{
    protected string $title = 'Bloq';

    protected string $model = Blog::class;

    protected string $route = 'admin.blogs';

    protected string $storagePath = 'blogs';

    protected array $searchable = ['title->az', 'slug', 'category'];

    protected array $columns = [
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'title', 'label' => 'Başlıq', 'type' => 'translatable'],
        ['key' => 'category', 'label' => 'Kateqoriya', 'type' => 'text'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
        ['key' => 'published_at', 'label' => 'Dərc', 'type' => 'datetime'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Başlıq', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 8],
        ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'col' => 4],
        ['name' => 'description', 'label' => 'Qısa təsvir', 'type' => 'translatable_textarea', 'col' => 12],
        ['name' => 'content', 'label' => 'Məzmun', 'type' => 'translatable_textarea', 'col' => 12],
        ['name' => 'image', 'label' => 'Şəkil', 'type' => 'image', 'path' => 'blogs', 'col' => 6],
        ['name' => 'category', 'label' => 'Kateqoriya', 'type' => 'text', 'col' => 6],
        ['name' => 'author_name', 'label' => 'Müəllif', 'type' => 'text', 'col' => 6],
        ['name' => 'published_at', 'label' => 'Dərc tarixi', 'type' => 'date', 'col' => 6],
        ['name' => 'tags', 'label' => 'Teqlər (hər sətirdə bir)', 'type' => 'lines', 'col' => 12],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 6],
    ];

    protected function prepareData(array $data, \Illuminate\Http\Request $request, ?Model $item): array
    {
        $data = parent::prepareData($data, $request, $item);

        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '') {
            if ($item) {
                unset($data['slug']);
            } else {
                $title = $data['title']['az'] ?? 'post';
                $data['slug'] = $this->uniqueSlug(Str::slug($title) ?: 'post');
            }
        } else {
            $data['slug'] = $this->uniqueSlug(Str::slug($slug), $item?->id);
        }

        return $data;
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        return app(BlogService::class)->uniqueSlug($base, $ignoreId);
    }
}
