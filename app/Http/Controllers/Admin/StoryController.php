<?php

namespace App\Http\Controllers\Admin;

use Modules\Product\Entities\Product;
use Modules\Story\Entities\Story;

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
        $map = [];
        foreach (Product::query()->orderByDesc('id')->limit(1000)->get() as $product) {
            $map[$product->id] = admin_label($product, 'title', '#'.$product->id);
        }

        return ['product_id' => $map];
    }

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'product_id') {
            $options = ['' => '— Məhsula bağlı deyil —'];
            foreach (Product::query()->orderByDesc('id')->limit(1000)->get() as $product) {
                $options[$product->id] = admin_label($product, 'title', '#'.$product->id);
            }

            return $options;
        }

        return parent::resolveOptions($field);
    }
}
