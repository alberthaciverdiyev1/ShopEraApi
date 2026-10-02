<?php

namespace App\Http\Controllers\Admin;

use Modules\Size\Entities\Size;

class SizeController extends ResourceController
{
    protected string $title = 'Ölçülər';

    protected string $model = Size::class;

    protected string $route = 'admin.sizes';

    protected string $storagePath = 'sizes';

    protected array $searchable = ['name->az', 'name->en'];

    protected array $columns = [
        ['key' => 'icon', 'label' => 'İkon', 'type' => 'image'],
        ['key' => 'name', 'label' => 'Ad', 'type' => 'translatable'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Ad', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 8],
        ['name' => 'icon', 'label' => 'İkon', 'type' => 'image', 'path' => 'sizes', 'col' => 4],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];
}
