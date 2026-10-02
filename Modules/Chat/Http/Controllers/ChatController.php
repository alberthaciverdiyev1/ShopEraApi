<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Chat\Http\SendMessageRequest;
use Modules\Chat\Services\ChatService;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
        $this->middleware('permission:full chat access')->only(['messageList', 'send', 'messages', 'markAsRead', 'delete', 'deleteConversation', 'conversationList']);
    }

    public function messageList()
    {
        return $this->chatService->messageList();
    }

    public function send(SendMessageRequest $request)
    {
        return $this->chatService->sendMessage($request);
    }

    public function messages(int $conversationId)
    {
        return $this->chatService->messages($conversationId);
    }

    public function markAsRead(int $conversationId)
    {
        return $this->chatService->markAsRead($conversationId);
    }

    public function delete(int $messageId)
    {
        return $this->chatService->deleteMessage($messageId);
    }

    public function deleteConversation(int $conversationId)
    {
        return $this->chatService->deleteConversation($conversationId);
    }

    public function conversationList(Request $request)
    {
        return $this->chatService->conversationList($request);
    }
}
