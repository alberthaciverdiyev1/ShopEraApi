<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Model;
use Modules\Popup\Entities\Popup;

class PopupController extends ResourceController
{
    protected string $title = 'Popup';

    protected string $model = Popup::class;

    protected string $route = 'admin.popups';

    protected string $storagePath = 'popups';

    protected array $columns = [
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'video', 'label' => 'Video', 'type' => 'text'],
        ['key' => 'show_on_home_page', 'label' => 'Ana səhifə', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'type', 'label' => 'Media tipi', 'type' => 'select', 'rules' => ['required', 'in:image,video'], 'col' => 6, 'options' => [
            'image' => 'Şəkil',
            'video' => 'Video',
        ]],
        ['name' => 'show_on_home_page', 'label' => 'Ana səhifədə göstər', 'type' => 'checkbox', 'col' => 6],
        ['name' => 'image', 'label' => 'Şəkil', 'type' => 'image', 'path' => 'popups', 'rules' => ['required_if:type,image'], 'col' => 12, 'showWhen' => ['field' => 'type', 'value' => 'image']],
        ['name' => 'video', 'label' => 'Video', 'type' => 'video', 'path' => 'popups', 'rules' => ['required_if:type,video'], 'col' => 12, 'showWhen' => ['field' => 'type', 'value' => 'video']],
    ];
}
