<?php

namespace App\Http\Controllers\Admin;

use Modules\Color\Entities\Color;

class ColorController extends ResourceController
{
    protected string $title = 'Rənglər';

    protected string $model = Color::class;

    protected string $route = 'admin.colors';

    protected array $searchable = ['name->az', 'name->en'];

    protected array $columns = [
        ['key' => 'hex', 'label' => '', 'type' => 'color'],
        ['key' => 'name', 'label' => 'Ad', 'type' => 'translatable'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Ad', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 8],
        ['name' => 'hex', 'label' => 'HEX', 'type' => 'color', 'col' => 4],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];
}
