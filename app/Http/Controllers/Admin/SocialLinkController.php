<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Setting\Entities\SocialLink;
use Modules\Setting\Services\SocialLinkService;

class SocialLinkController extends ResourceController
{
    protected string $title = 'Sosial şəbəkələr';

    protected string $model = SocialLink::class;

    protected string $route = 'admin.social-links';

    protected string $orderColumn = 'sort_order';

    protected string $orderDirection = 'asc';

    protected array $searchable = ['platform', 'url'];

    protected array $columns = [
        ['key' => 'platform', 'label' => 'Şəbəkə', 'type' => 'map'],
        ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'platform', 'label' => 'Şəbəkə', 'type' => 'select', 'rules' => ['required'], 'col' => 6, 'options' => SocialLinkService::PLATFORMS],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
        ['name' => 'url', 'label' => 'Link (URL)', 'type' => 'text', 'rules' => ['required', 'max:255'], 'col' => 12],
    ];

    protected function columnMaps(): array
    {
        return ['platform' => SocialLinkService::PLATFORMS];
    }

    protected function afterSave(Model $item, Request $request, bool $created): void
    {
        SocialLinkService::forgetCache();
    }

    protected function afterDelete(Model $item): void
    {
        SocialLinkService::forgetCache();
    }
}
