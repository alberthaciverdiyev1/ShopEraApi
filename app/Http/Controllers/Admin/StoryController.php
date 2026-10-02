<?php

namespace App\Http\Controllers\Admin;

use Modules\Story\Entities\Story;
use Modules\Story\Services\StoryService;

class StoryController extends ResourceController
{
    protected string $title = 'Story';

    protected string $model = Story::class;

    protected string $route = 'admin.stories';

    protected string $storagePath = 'stories';

    protected array $columns = [
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'product_id', 'label' => 'Məhsul', 'type' => 'map'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
        ['key' => 'expires_at', 'label' => 'Bitmə', 'type' => 'datetime'],
    ];

    protected array $fields = [
        ['name' => 'image', 'label' => 'Şəkil', 'type' => 'image', 'path' => 'stories', 'rules' => ['nullable'], 'col' => 6],
        ['name' => 'video', 'label' => 'Video', 'type' => 'video', 'path' => 'stories', 'rules' => ['nullable'], 'col' => 6],
        ['name' => 'product_id', 'label' => 'Məhsul (opsional)', 'type' => 'select', 'col' => 6],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 4],
        ['name' => 'expires_at', 'label' => 'Bitmə tarixi (opsional)', 'type' => 'date', 'col' => 4],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 4, 'default' => true],
    ];

    protected function columnMaps(): array
    {
        return ['product_id' => app(StoryService::class)->productOptions()];
    }

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'product_id') {
            return app(StoryService::class)->productOptions();
        }

        return parent::resolveOptions($field);
    }
}
