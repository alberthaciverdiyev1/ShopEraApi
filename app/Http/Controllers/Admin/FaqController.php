<?php

namespace App\Http\Controllers\Admin;

use Modules\HelpAndPolicy\Entities\Faq;

class FaqController extends ResourceController
{
    protected string $title = 'Tez-tez verilən suallar';

    protected string $model = Faq::class;

    protected string $route = 'admin.faqs';

    protected array $searchable = ['title->az', 'description->az'];

    protected array $columns = [
        ['key' => 'title', 'label' => 'Sual', 'type' => 'translatable'],
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Sual', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 12],
        ['name' => 'description', 'label' => 'Cavab', 'type' => 'translatable_textarea', 'rules' => ['required'], 'col' => 12],
        ['name' => 'type', 'label' => 'Tip', 'type' => 'select', 'col' => 6, 'options' => [
            'faq' => 'FAQ',
            'about' => 'Haqqımızda',
            'main_page' => 'Əsas səhifə',
        ]],
    ];
}
