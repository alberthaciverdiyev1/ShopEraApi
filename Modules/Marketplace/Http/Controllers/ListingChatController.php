<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Entities\ListingConversation;
use Modules\Marketplace\Services\ListingChatService;
use Modules\Product\Entities\Product;

class ListingChatController extends Controller
{
    public function __construct(private readonly ListingChatService $service) {}

    /** Open (or reuse) the conversation for a listing. */
    public function start(Request $request, int $id)
    {
        $listing = Product::query()->findOrFail($id);
        $conversation = $this->service->start($listing, $request->user('sanctum'));

        return responseHelper('OK', 200, $this->service->messages($conversation, $request->user('sanctum')));
    }

    /** The signed-in user's conversations. */
    public function index(Request $request)
    {
        return responseHelper('OK', 200, $this->service->conversationsFor($request->user('sanctum')));
    }

    public function unread(Request $request)
    {
        return responseHelper('OK', 200, ['count' => $this->service->unreadCount($request->user('sanctum'))]);
    }

    public function show(Request $request, int $id)
    {
        $conversation = ListingConversation::query()->findOrFail($id);

        return responseHelper('OK', 200, $this->service->messages($conversation, $request->user('sanctum')));
    }

    public function send(Request $request, int $id)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $conversation = ListingConversation::query()->findOrFail($id);

        return responseHelper('Mesaj göndərildi.', 201, $this->service->send($conversation, $request->user('sanctum'), $data['body']));
    }
}
