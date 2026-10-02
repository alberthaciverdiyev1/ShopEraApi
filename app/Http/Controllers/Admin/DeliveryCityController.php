<?php

namespace App\Http\Controllers\Admin;

use Modules\Delivery\Entities\City;

class DeliveryCityController extends ResourceController
{
    protected string $title = 'Şəhərlər';

    protected string $model = City::class;

    protected string $route = 'admin.delivery-cities';

    protected array $searchable = ['key', 'name'];

    protected array $columns = [
        ['key' => 'key', 'label' => 'Açar', 'type' => 'text'],
        ['key' => 'name', 'label' => 'Ad', 'type' => 'text'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'key', 'label' => 'Açar (key)', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'name', 'label' => 'Ad', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 6],
    ];
}
