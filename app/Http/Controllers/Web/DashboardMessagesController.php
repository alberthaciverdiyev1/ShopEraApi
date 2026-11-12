<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SendSupportMessageRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\RedirectResponse;
use Modules\Chat\Http\Controllers\ChatController;
use Modules\Chat\Http\SendMessageRequest;

class DashboardMessagesController extends Controller
{
    use InteractsWithModuleControllers;

    public function send(SendSupportMessageRequest $request): RedirectResponse
    {
        $form = $this->moduleRequest(SendMessageRequest::class, [
            'message' => $request->validated('message'),
        ], 'POST', $request->allFiles());

        $payload = app(ChatController::class)->send($form)->getData(true);
        $success = (bool) ($payload['success'] ?? false);

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'messages'])
            ->with(
                $success ? 'status' : 'error',
                $success
                    ? 'Mesaj göndərildi.'
                    : ($payload['message'] ?: 'Mesaj göndərilə bilmədi.')
            );
    }

    public function destroy(int $id): RedirectResponse
    {
        app(ChatController::class)->deleteOwn($id);

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'messages'])
            ->with('status', 'Mesaj silindi.');
    }
}
