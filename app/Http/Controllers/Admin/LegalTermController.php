<?php

namespace App\Http\Controllers\Admin;

use Modules\HelpAndPolicy\Entities\LegalTerm;

/**
 * Legal pages are a fixed set of rows (terms, privacy, about…) whose HTML is
 * translated; they can be edited but not created or deleted here.
 */
class LegalTermController extends ResourceController
{
    protected string $title = 'Şərtlər və qaydalar';

    protected string $model = LegalTerm::class;

    protected string $route = 'admin.legal-terms';

    protected array $columns = [
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
        ['key' => 'html', 'label' => 'Məzmun', 'type' => 'translatable'],
    ];

    protected array $fields = [
        ['name' => 'type', 'label' => 'Tip', 'type' => 'text', 'col' => 12],
        ['name' => 'html', 'label' => 'HTML məzmun', 'type' => 'translatable_textarea', 'col' => 12],
    ];
}
