<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Entities\ListingConversation;
use Modules\Marketplace\Services\ListingChatService;
use Modules\Product\Entities\Product;

/**
 * Buyer <-> seller chat for the Blade product page. The module service is the
 * same one the API uses; these endpoints only translate between the session
 * guard and JSON so the modal can stay on the page.
 */
class ListingChatController extends Controller
{
    public function __construct(private readonly ListingChatService $chat) {}

    /** The signed-in user's conversations. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->chat->conversationsFor($request->user())->values()->all()
        );
    }

    /** Open (or reuse) the conversation for a listing. */
    public function start(Request $request, int $id): JsonResponse
    {
        $listing = Product::query()->findOrFail($id);

        return response()->json(
            $this->chat->messages(
                $this->chat->start($listing, $request->user()),
                $request->user()
            )
        );
    }

    /** Messages of one conversation; the other party's get marked as read. */
    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = ListingConversation::query()->findOrFail($id);

        return response()->json(
            $this->chat->messages($conversation, $request->user())
        );
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = ListingConversation::query()->findOrFail($id);

        return response()->json(
            $this->chat->send($conversation, $request->user(), $data['body']),
            201
        );
    }
}
