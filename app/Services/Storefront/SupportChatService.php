<?php

namespace App\Services\Storefront;

use Illuminate\Http\UploadedFile;
use Modules\Chat\Entities\Conversation;
use Modules\Chat\Entities\Message;
use Modules\Chat\Http\SendMessageRequest;
use Modules\Chat\Services\ChatService;
use Modules\User\Entities\User;

/**
 * Customer <-> support chat for the signed-in dashboard. The chat service works
 * off the current auth user, so the session guard is enough here.
 */
class SupportChatService
{
    public function __construct(private readonly ChatService $chat) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function messages(User $user): array
    {
        $messages = $this->chat->messageList()->getData(true)['data'] ?? [];

        return array_values(array_reverse($messages));
    }

    public function markRead(User $user): void
    {
        $conversation = Conversation::query()->where('user_id', $user->id)->first();

        if ($conversation) {
            $this->chat->markAsRead($conversation->id);
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function send(User $user, array $data, ?UploadedFile $image = null): array
    {
        $request = SendMessageRequest::create(
            '/api/chat/send',
            'POST',
            $data,
            [],
            $image ? ['image' => $image] : []
        );
        $request->setContainer(app());
        $request->setUserResolver(fn () => $user);
        $request->validateResolved();

        $payload = $this->chat->sendMessage($request)->getData(true);

        return [
            'success' => (bool) ($payload['success'] ?? false),
            'message' => (string) ($payload['message'] ?? ''),
        ];
    }

    public function delete(User $user, int $messageId): void
    {
        $message = Message::query()->findOrFail($messageId);
        $conversation = Conversation::query()->find($message->conversation_id);

        abort_unless($conversation && (int) $conversation->user_id === (int) $user->id, 403);

        $this->chat->deleteMessage($messageId);
    }
}
