<?php

namespace App\Http\Controllers;

use App\Services\Starex\StarexShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StarexWebhookController extends Controller
{
    public function __invoke(Request $request, StarexShipmentService $service): JsonResponse
    {
        $expected = (string) config('services.starex.webhook_secret');
        $provided = (string) $request->bearerToken();

        if (!$expected || !$provided || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $service->handleWebhook($request->all());

            return response()->json(['message' => 'Webhook accepted.']);
        } catch (\Throwable $e) {
            Log::error('Starex webhook processing failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook could not be processed.'], 422);
        }
    }
}
