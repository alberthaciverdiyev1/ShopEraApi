<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\AccountService;
use App\Services\Storefront\DashboardService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
        private readonly DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $tab = request()->query('tab', 'dashboard');
        $allowedTabs = ['dashboard', 'addresses', 'settings', 'logout'];

        return view('storefront.dashboard.index', [
            'tab' => in_array($tab, $allowedTabs, true) ? $tab : 'dashboard',
            'defaultAddress' => $this->account->defaultAddress(),
            'addresses' => $this->account->addresses(),
            'cities' => $this->account->cities(),
            'recentOrders' => $this->dashboard->recentOrders(),
        ]);
    }
}
