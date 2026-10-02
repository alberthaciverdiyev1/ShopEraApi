<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\User\Entities\User;

/**
 * "Komanda" — idarə heyəti: rolu `user`-dən fərqli olan bütün istifadəçilər.
 * (TS paneldəki `/user/list?only_team=true` məntiqi ilə eynidir.)
 */
class TeamController extends AdminController
{
    protected string $title = 'Komanda';

    public function index(Request $request)
    {
        $this->requirePermission('view users');

        $query = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'user'))
            ->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

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
