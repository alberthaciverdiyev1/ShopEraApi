<?php

namespace App\Http\Controllers\Admin;

use Modules\PromoCode\Entities\PromoCode;

class PromoCodeController extends ResourceController
{
    protected string $title = 'Promo kodlar';

    protected string $model = PromoCode::class;

    protected string $route = 'admin.promocodes';

    protected array $searchable = ['code'];

    protected array $columns = [
        ['key' => 'code', 'label' => 'Kod', 'type' => 'text'],
        ['key' => 'discount_percent', 'label' => 'Endirim %', 'type' => 'number'],
        ['key' => 'user_count', 'label' => 'İstifadə limiti', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'code', 'label' => 'Kod', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'discount_percent', 'label' => 'Endirim (%)', 'type' => 'number', 'col' => 6],
        ['name' => 'user_count', 'label' => 'İstifadə limiti', 'type' => 'number', 'col' => 6],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 6],
    ];
}
