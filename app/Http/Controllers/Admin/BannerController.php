<?php

namespace App\Http\Controllers\Admin;

use Modules\Banner\Entities\Banner;
use Modules\Banner\Services\BannerService;

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
        ['name' => 'type', 'label' => 'Tip', 'type' => 'select', 'col' => 6, 'rules' => ['required', 'in:big,middle,small'], 'options' => [
            'big' => 'Böyük (Hero)',
            'middle' => 'Orta (Best seller)',
            'small' => 'Kiçik (Promo)',
        ]],
        ['name' => 'url', 'label' => 'Link (URL)', 'type' => 'text', 'col' => 12],
        ['name' => 'product_id', 'label' => 'Məhsul', 'type' => 'select', 'col' => 12],
        // Məhsul seçilməyəndə bannerin öz başlıq/alt başlığı göstərilir.
        ['name' => 'title', 'label' => 'Başlıq', 'type' => 'translatable_text', 'col' => 6, 'showWhen' => ['field' => 'product_id', 'value' => '']],
        ['name' => 'subtitle', 'label' => 'Alt başlıq', 'type' => 'translatable_text', 'col' => 6, 'showWhen' => ['field' => 'product_id', 'value' => '']],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 12, 'default' => true],
    ];

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'product_id') {
            return app(BannerService::class)->productOptions();
        }

        return parent::resolveOptions($field);
    }
}
