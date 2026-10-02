<?php

namespace App\Http\Controllers\Api;

use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public feature flags for the storefront, read from the entitlement snapshot
 * mirrored into this tenant's database. Falls back to "everything on" when the
 * instance has never been synced, so the storefront never breaks.
 */
class FeaturesController extends Controller
{
    private const DEFAULTS = [
        'online_payment' => true,
        'cash_on_delivery' => true,
        'whatsapp_orders' => true,
        'phone_orders' => false,
    ];

    public function index(Request $request)
    {
        $flags = Features::all();

        if ($flags === null) {
            return $this->ok(self::DEFAULTS);
        }

        return $this->ok(array_merge(self::DEFAULTS, $flags));
    }

    private function ok(array $data)
    {
        return response()->json(['success' => true, 'status_code' => 200, 'message' => 'OK', 'data' => $data]);
    }
}
