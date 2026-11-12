<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Modules\Chat\Http\Controllers\ChatController;
use Modules\Delivery\Http\Controllers\DeliveryController;
use Modules\Order\Http\Controllers\OrderController;
use Modules\User\Http\Controllers\AddressController;

class DashboardController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        $tab = request()->query('tab', 'dashboard');
        $allowedTabs = ['dashboard', 'messages', 'addresses', 'settings', 'logout'];
        $tab = in_array($tab, $allowedTabs, true) ? $tab : 'dashboard';

        $addresses = (array) $this->payload(
            app(AddressController::class)->getAll($this->moduleHttpRequest())
        );

        $defaultAddress = null;

        foreach ($addresses as $address) {
            if (! empty($address['is_default'])) {
                $defaultAddress = $address;
                break;
            }
        }

        $defaultAddress ??= $addresses[0] ?? null;

        $chatMessages = [];

        if ($tab === 'messages') {
            $chatMessages = array_values(array_reverse(
                (array) $this->payload(app(ChatController::class)->messageList())
            ));

            app(ChatController::class)->markOwnAsRead();
        }

        return view('storefront.dashboard.index', [
            'tab' => $tab,
            'defaultAddress' => $defaultAddress,
            'addresses' => $addresses,
            'cities' => (array) $this->payload(app(DeliveryController::class)->cities()),
            'recentOrders' => array_slice(
                (array) $this->payload(app(OrderController::class)->getAll($this->moduleHttpRequest())),
                0,
                5
            ),
            'chatMessages' => $chatMessages,
        ]);
    }
}
