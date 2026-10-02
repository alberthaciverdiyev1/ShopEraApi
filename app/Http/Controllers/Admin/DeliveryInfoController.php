<?php

namespace App\Http\Controllers\Admin;

use Modules\Delivery\Entities\DeliveryInfo;

/**
 * Fixed set of delivery-type labels (four seeded rows). Rows are edited, never
 * created or removed from the panel.
 */
class DeliveryInfoController extends ResourceController
{
    protected string $title = 'Çatdırılma məlumatları';

    protected string $model = DeliveryInfo::class;

    protected string $route = 'admin.delivery-infos';

    protected array $columns = [
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
        ['key' => 'description', 'label' => 'Təsvir', 'type' => 'translatable'],
    ];

    protected array $fields = [
        ['name' => 'type', 'label' => 'Tip', 'type' => 'text', 'col' => 12],
        ['name' => 'description', 'label' => 'Təsvir', 'type' => 'translatable_text', 'col' => 12],
    ];
}
