<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SendSupportMessageRequest;
use App\Services\Storefront\SupportChatService;
use Illuminate\Http\RedirectResponse;

class DashboardMessagesController extends Controller
{
    public function __construct(private readonly SupportChatService $chat) {}

    public function send(SendSupportMessageRequest $request): RedirectResponse
    {
        $result = $this->chat->send(
            $request->user(),
            ['message' => $request->validated('message')],
            $request->file('image')
        );

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'messages'])
            ->with(
                $result['success'] ? 'status' : 'error',
                $result['success']
                    ? 'Mesaj göndərildi.'
                    : ($result['message'] ?: 'Mesaj göndərilə bilmədi.')
            );
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->chat->delete(request()->user(), $id);

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'messages'])
            ->with('status', 'Mesaj silindi.');
    }
}
