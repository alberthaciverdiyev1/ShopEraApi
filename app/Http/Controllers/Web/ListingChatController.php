<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Controllers\ListingChatController as ModuleListingChatController;

/**
 * Buyer <-> seller chat for the Blade product page. The module controller is
 * the same one the API uses; these endpoints only unwrap its JSON envelope so
 * the modal stays on the page.
 */
class ListingChatController extends Controller
{
    use InteractsWithModuleControllers;

    /** The signed-in user's conversations. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->payload(
                app(ModuleListingChatController::class)->index($this->moduleHttpRequest())
            )
        );
    }

    /** Open (or reuse) the conversation for a listing. */
    public function start(Request $request, int $id): JsonResponse
    {
        return response()->json(
            $this->payload(
                app(ModuleListingChatController::class)->start($this->moduleHttpRequest([], 'POST'), $id)
            )
        );
    }

    /** Messages of one conversation; the other party's get marked as read. */
    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(
            $this->payload(
                app(ModuleListingChatController::class)->show($this->moduleHttpRequest(), $id)
            )
        );
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json(
            $this->payload(
                app(ModuleListingChatController::class)->send(
                    $this->moduleHttpRequest($data, 'POST'),
                    $id
                )
            ),
            201
        );
    }
}
