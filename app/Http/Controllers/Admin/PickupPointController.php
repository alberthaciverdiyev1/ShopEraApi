<?php

namespace App\Http\Controllers\Admin;

use Modules\Delivery\Entities\PickupPoint;

class PickupPointController extends ResourceController
{
    protected string $title = 'Gəl al nöqtələri';

    protected string $model = PickupPoint::class;

    protected string $route = 'admin.pickup-points';

    protected array $searchable = ['name', 'address'];

    protected array $columns = [
        ['key' => 'name', 'label' => 'Ad', 'type' => 'text'],
        ['key' => 'address', 'label' => 'Ünvan', 'type' => 'text'],
        ['key' => 'price', 'label' => 'Qiymət', 'type' => 'money'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Ad', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'price', 'label' => 'Qiymət', 'type' => 'number', 'col' => 6],
        ['name' => 'address', 'label' => 'Ünvan', 'type' => 'textarea', 'rules' => ['required'], 'col' => 12],
        ['name' => 'delivery_time', 'label' => 'Çatdırılma vaxtı', 'type' => 'translatable_text', 'col' => 8],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 4],
    ];
}
