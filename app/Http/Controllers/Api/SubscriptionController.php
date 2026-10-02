<?php

namespace App\Http\Controllers\Api;

use App\Support\Subscription;
use Illuminate\Routing\Controller;

class SubscriptionController extends Controller
{
    public function show()
    {
        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
            'data' => [
                'status' => Subscription::status(),
                'plan' => Subscription::plan(),
                'ends_at' => Subscription::endsAt(),
                'usable' => Subscription::usable(),
                'past_due' => Subscription::isPastDue(),
                'blocked' => Subscription::isBlocked(),
            ],
        ]);
    }
}
