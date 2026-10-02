<?php

namespace App\Http\Controllers\Admin;

use Modules\Brand\Entities\Brand;

class BrandController extends ResourceController
{
    protected string $title = 'Brendlər';

    protected string $model = Brand::class;

    protected string $route = 'admin.brands';

    protected string $storagePath = 'brands';

    protected array $searchable = ['name'];

    protected array $columns = [
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'name', 'label' => 'Ad', 'type' => 'text'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Ad', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 12],
        ['name' => 'image', 'label' => 'Loqo', 'type' => 'image', 'path' => 'brands', 'col' => 6],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];
}
