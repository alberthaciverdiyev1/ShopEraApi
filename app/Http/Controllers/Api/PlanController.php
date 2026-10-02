<?php

namespace App\Http\Controllers\Api;

use App\Support\Features;
use App\Support\PlanUsage;
use App\Support\Subscription;
use Illuminate\Routing\Controller;

/**
 * The storefront's plan view: subscription status plus the numeric limits
 * mirrored from Manager.ShopEra and current usage against them.
 */
class PlanController extends Controller
{
    public function index()
    {
        $subscription = Subscription::data();

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
            'data' => [
                'plan' => $subscription['plan'] ?? null,
                'status' => $subscription['status'] ?? null,
                'ends_at' => $subscription['ends_at'] ?? null,
                'usable' => (bool) ($subscription['usable'] ?? true),
                'limits' => Features::limits(),
                'usage' => PlanUsage::all(),
            ],
        ]);
    }
}
