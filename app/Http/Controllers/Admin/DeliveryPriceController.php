<?php

namespace App\Http\Controllers\Admin;

use Modules\Delivery\Entities\Delivery;

class DeliveryPriceController extends ResourceController
{
    protected string $title = 'Çatdırılma qiymətləri';

    protected string $model = Delivery::class;

    protected string $route = 'admin.delivery-prices';

    protected array $searchable = ['city_name'];

    protected array $columns = [
        ['key' => 'city_name', 'label' => 'Şəhər', 'type' => 'text'],
        ['key' => 'price', 'label' => 'Qiymət', 'type' => 'money'],
        ['key' => 'free_from', 'label' => 'Pulsuz (məbləğ)', 'type' => 'money'],
        ['key' => 'fast_price', 'label' => 'Tez çatdırılma', 'type' => 'money'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'city_name', 'label' => 'Şəhər', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'price', 'label' => 'Qiymət', 'type' => 'number', 'col' => 6],
        ['name' => 'free_from', 'label' => 'Bu məbləğdən pulsuz', 'type' => 'number', 'col' => 6],
        ['name' => 'fast_price', 'label' => 'Tez çatdırılma qiyməti', 'type' => 'number', 'col' => 6],
        ['name' => 'delivery_time', 'label' => 'Çatdırılma vaxtı', 'type' => 'text', 'col' => 6],
        ['name' => 'fast_delivery_time', 'label' => 'Tez çatdırılma vaxtı', 'type' => 'text', 'col' => 6],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 6],
    ];
}
