<?php

namespace Modules\Manager\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Entities\Subscription;
use Modules\Manager\Entities\Theme;

class DashboardController extends Controller
{
    public function index()
    {
        return view('manager::pages.dashboard', [
            'owners' => SiteOwner::query()->withCount('domains')->latest()->limit(20)->get(),
            'counts' => [
                'owners' => SiteOwner::query()->count(),
                'plans' => Plan::query()->count(),
                'active_subscriptions' => Subscription::query()->where('status', 'active')->count(),
                'themes' => Theme::query()->count(),
            ],
        ]);
    }
}
