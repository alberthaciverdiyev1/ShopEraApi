<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\User\Services\UserService;

class TeamController extends AdminController
{
    protected string $title = 'Komanda';

    public function index(Request $request)
    {
        $this->requirePermission('view users');

        $rows = app(UserService::class)->teamQuery($request)->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.team._table', ['rows' => $rows]);
        }

        return view('admin.pages.team.index', [
            'title' => $this->title,
            'rows' => $rows,
            'filters' => $request->only(['q']),
        ]);
    }
}
