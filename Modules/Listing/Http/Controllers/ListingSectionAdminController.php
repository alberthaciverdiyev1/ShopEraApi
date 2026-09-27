<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingSectionService;

class ListingSectionAdminController extends Controller
{
    public function __construct(private readonly ListingSectionService $sections)
    {
        // The route group only checks that a token is present, so the
        // permissions are named here, as the other admin screens do.
        $this->middleware('permission:view listings')->only(['index']);
        $this->middleware('permission:manage listings')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        return $this->sections->adminList($request);
    }

    public function store(Request $request)
    {
        return $this->sections->create($request);
    }

    public function update(int $id, Request $request)
    {
        return $this->sections->update($id, $request);
    }

    public function destroy(int $id)
    {
        return $this->sections->delete($id);
    }
}
