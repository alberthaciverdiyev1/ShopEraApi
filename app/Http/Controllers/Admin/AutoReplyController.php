<?php

namespace App\Http\Controllers\Admin;

use Modules\Chat\Entities\AutoReply;

class AutoReplyController extends ResourceController
{
    protected string $title = 'Avtomatik cavablar';

    protected string $model = AutoReply::class;

    protected string $route = 'admin.auto-replies';

    protected array $searchable = ['question->az', 'answer->az'];

    protected array $columns = [
        ['key' => 'question', 'label' => 'Sual', 'type' => 'translatable'],
        ['key' => 'answer', 'label' => 'Cavab', 'type' => 'translatable'],
    ];

    protected array $fields = [
        ['name' => 'question', 'label' => 'Sual', 'type' => 'translatable_text', 'rules' => ['required', 'max:1000'], 'col' => 12],
        ['name' => 'answer', 'label' => 'Cavab', 'type' => 'translatable_textarea', 'rules' => ['required'], 'col' => 12],
    ];
}
