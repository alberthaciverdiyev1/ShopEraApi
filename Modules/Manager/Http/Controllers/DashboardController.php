<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Enums\SubscriptionStatus;
use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Entities\Subscription;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $usable = [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::TRIALING->value, SubscriptionStatus::PAST_DUE->value];

        return view('manager::pages.dashboard', [
            'title' => 'İcmal',
            'stats' => [
                ['label' => 'Sahiblər', 'value' => SiteOwner::query()->count(), 'route' => route('manager.owners.index')],
                ['label' => 'Aktiv abunələr', 'value' => Subscription::query()->whereIn('status', $usable)->count(), 'route' => route('manager.plans.index')],
                ['label' => 'Aylıq gəlir (MRR)', 'value' => number_format((float) Subscription::query()->where('status', 'active')->sum('price'), 2).' ₼', 'route' => route('manager.plans.index')],
                ['label' => 'Feature-lar', 'value' => Feature::query()->count(), 'route' => route('manager.features.index')],
            ],
            'recentOwners' => SiteOwner::query()->with(['domains', 'currentSubscription.plan'])->latest('id')->limit(8)->get(),
            'plans' => Plan::query()->withCount('features')->orderBy('sort_order')->get(),
        ]);
    }
}
