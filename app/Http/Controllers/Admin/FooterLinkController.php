<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Setting\Entities\FooterLink;
use Modules\Setting\Services\FooterLinkService;

class FooterLinkController extends ResourceController
{
    protected string $title = 'Footer linkləri';

    protected string $model = FooterLink::class;

    protected string $route = 'admin.footer-links';

    protected string $orderColumn = 'sort_order';

    protected string $orderDirection = 'asc';

    protected array $searchable = ['label->az', 'url'];

    protected array $columns = [
        ['key' => 'label', 'label' => 'Ad', 'type' => 'translatable'],
        ['key' => 'section', 'label' => 'Bölmə', 'type' => 'map'],
        ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'label', 'label' => 'Ad', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'section', 'label' => 'Bölmə', 'type' => 'select', 'rules' => ['required'], 'col' => 3, 'options' => FooterLinkService::SECTIONS],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'url', 'label' => 'Link (URL)', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 9],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];

    protected function columnMaps(): array
    {
        return ['section' => FooterLinkService::SECTIONS];
    }

    protected function afterSave(Model $item, Request $request, bool $created): void
    {
        FooterLinkService::forgetCache();
    }

    protected function afterDelete(Model $item): void
    {
        FooterLinkService::forgetCache();
    }
}
