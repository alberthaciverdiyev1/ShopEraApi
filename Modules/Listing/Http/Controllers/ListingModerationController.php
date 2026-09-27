<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingModerationService;

class ListingModerationController extends Controller
{
    public function __construct(private readonly ListingModerationService $moderation)
    {
        // The route group only checks that a token is present, so the
        // permissions are named here, as the other admin screens do.
        $this->middleware('permission:view listings')->only(['index', 'show']);
        $this->middleware('permission:manage listings')->only(['approve', 'reject', 'feature', 'destroy']);
    }

    public function index(Request $request)
    {
        return $this->moderation->index($request);
    }

    public function show(int $id, Request $request)
    {
        return $this->moderation->show($id, $request);
    }

    public function approve(int $id, Request $request)
    {
        return $this->moderation->approve($id, $request);
    }

    public function reject(int $id, Request $request)
    {
        return $this->moderation->reject($id, $request);
    }

    public function feature(int $id, Request $request)
    {
        return $this->moderation->feature($id, $request);
    }

    public function destroy(int $id)
    {
        return $this->moderation->destroy($id);
    }
}
