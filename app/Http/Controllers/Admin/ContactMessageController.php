<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;

class ContactMessageController extends ResourceController
{
    protected string $title = 'Əlaqə müraciətləri';

    protected string $model = ContactMessage::class;

    protected string $route = 'admin.contact-messages';

    protected array $searchable = ['first_name', 'last_name', 'email', 'phone', 'subject', 'message'];

    protected array $columns = [
        ['key' => 'id', 'label' => 'ID', 'type' => 'number'],
        ['key' => 'full_name', 'label' => 'Ad Soyad', 'type' => 'text'],
        ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
        ['key' => 'phone', 'label' => 'Telefon', 'type' => 'text'],
        ['key' => 'subject', 'label' => 'Mövzu', 'type' => 'text'],
        ['key' => 'is_read', 'label' => 'Oxunub', 'type' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Tarix', 'type' => 'datetime'],
    ];

    protected array $fields = [
        ['name' => 'first_name', 'label' => 'Ad', 'type' => 'text', 'rules' => ['nullable'], 'col' => 6],
        ['name' => 'last_name', 'label' => 'Soyad', 'type' => 'text', 'rules' => ['nullable'], 'col' => 6],
        ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'rules' => ['required', 'email'], 'col' => 6],
        ['name' => 'phone', 'label' => 'Telefon', 'type' => 'text', 'rules' => ['nullable'], 'col' => 6],
        ['name' => 'subject', 'label' => 'Mövzu', 'type' => 'text', 'rules' => ['nullable'], 'col' => 12],
        ['name' => 'message', 'label' => 'Mesaj', 'type' => 'textarea', 'rules' => ['required'], 'col' => 12],
        ['name' => 'is_read', 'label' => 'Oxunub kimi qeyd et', 'type' => 'checkbox', 'col' => 6],
    ];
}
