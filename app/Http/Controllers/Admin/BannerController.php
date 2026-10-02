<?php

namespace App\Http\Controllers\Admin;

use Modules\Banner\Entities\Banner;
use Modules\Product\Entities\Product;

class BannerController extends ResourceController
{
    protected string $title = 'Bannerlər';

    protected string $model = Banner::class;

    protected string $route = 'admin.banners';

    protected string $storagePath = 'banners';

    protected array $columns = [
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
        ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'image', 'label' => 'Şəkil', 'type' => 'image', 'path' => 'banner', 'rules' => ['required'], 'col' => 6],
        ['name' => 'second_image', 'label' => 'İkinci şəkil', 'type' => 'image', 'path' => 'banner', 'col' => 6],
        ['name' => 'type', 'label' => 'Tip', 'type' => 'select', 'col' => 4, 'rules' => ['required', 'in:big,middle,small'], 'options' => [
            'big' => 'Böyük (Hero)',
            'middle' => 'Orta (Best seller)',
            'small' => 'Kiçik (Promo)',
        ]],
        ['name' => 'url', 'label' => 'Link (URL)', 'type' => 'text', 'col' => 8],
        ['name' => 'product_id', 'label' => 'Məhsul', 'type' => 'select', 'col' => 8],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 4, 'default' => true],
    ];

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'product_id') {
            $options = ['' => '— Məhsul seçilməyib —'];
            foreach (Product::query()->orderByDesc('id')->limit(500)->get() as $product) {
                $options[$product->id] = admin_label($product, 'title', '#'.$product->id);
            }

            return $options;
        }

        return parent::resolveOptions($field);
    }
}
