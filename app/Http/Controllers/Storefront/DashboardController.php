<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\AccountService;
use App\Services\Storefront\DashboardService;
use App\Services\Storefront\SupportChatService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
        private readonly DashboardService $dashboard,
        private readonly SupportChatService $chat,
    ) {}

    public function index(): View
    {
        $tab = request()->query('tab', 'dashboard');
        $allowedTabs = ['dashboard', 'messages', 'addresses', 'settings', 'logout'];
        $tab = in_array($tab, $allowedTabs, true) ? $tab : 'dashboard';

        $chatMessages = [];
        if ($tab === 'messages') {
            $chatMessages = $this->chat->messages(request()->user());
            $this->chat->markRead(request()->user());
        }

        return view('storefront.dashboard.index', [
            'tab' => $tab,
            'defaultAddress' => $this->account->defaultAddress(),
            'addresses' => $this->account->addresses(),
            'cities' => $this->account->cities(),
            'recentOrders' => $this->dashboard->recentOrders(),
            'chatMessages' => $chatMessages,
        ]);
    }
}
