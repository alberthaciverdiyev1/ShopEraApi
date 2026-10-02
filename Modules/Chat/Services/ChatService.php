<?php

namespace Modules\Chat\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Entities\Conversation;
use Modules\Chat\Entities\Message;
use Modules\Chat\Entities\MessageAttachment;
use Modules\Chat\Http\Resources\AdminConversationList;
use Modules\Chat\Http\Resources\ConversationDetailsResource;
use Modules\User\Entities\User;

class ChatService
{
    public function __construct(protected Conversation $conversation, protected Message $message, protected MessageAttachment $attachment, protected AutoReplyService $autoReplyService) {}

    /**
     * @param  int  $targetUserId
     */
    //    public function sendMessage($request)
    //    {
    //        $validated = $request->validated();
    //        $sender = auth()->user();
    //
    //        return DB::transaction(function () use ($validated, $sender, $request) {
    //
    //            $isAdmin = $sender->hasRole('admin');
    //            $adminId = User::where('phone', '0708990999')->first()->id;
    //
    //            $conversationData = [
    //                'user_id'  => $isAdmin ? $validated['target_user_id'] : $sender->id,
    //                'admin_id' => $isAdmin ? $sender->id : $adminId,
    //            ];
    //
    //            $conversation = $this->conversation->firstOrCreate(
    //                $conversationData,
    //                ['last_message_at' => now()]
    //            );
    //
    //            $message = $this->message->create([
    //                'conversation_id' => $conversation->id,
    //                'sender_type'     => $isAdmin ? 'admin' : 'user',
    //                'sender_id'       => $sender->id,
    //                'message'         => $validated['message'] ?? null,
    //                'is_read'         => false,
    //            ]);
    //
    //            if ($request->hasFile('image')) {
    //
    //                $cdnPath = compressAndUploadImage(
    //                    $request->file('image'),
    //                    'chat/attachments',
    //                    null
    //                );
    //
    //                $this->attachment->create([
    //                    'message_id' => $message->id,
    //                    'path'       => $cdnPath,
    //                ]);
    //            }
    //
    //            $conversation->update([
    //                'last_message_at' => now(),
    //            ]);
    //
    //            return responseHelper(__('Message sent'), 200);
    //        });
    //    }

    public function sendMessage($request)
    {
        $validated = $request->validated();
        $sender = auth()->user();

        return DB::transaction(function () use ($validated, $sender, $request) {

            $supportAdmin = $this->resolveSupportAdmin();
            if (! $supportAdmin) {
                return responseHelper(__('Admin not found'), 404);
            }

            // Admin replies carry a `target_user_id`; anything else (including a
            // storefront account that happens to have an admin role) is stored as
            // a normal customer message inside its own conversation.
            $isAdmin = ! empty($validated['target_user_id']) && $sender->hasRole('admin');

            $userId = $isAdmin ? (int) $validated['target_user_id'] : (int) $sender->id;
            $adminId = (int) $supportAdmin->id;

            $conversation = $this->conversation
                ->where('user_id', $userId)
                ->where('admin_id', $adminId)
                ->first();

            if (! $conversation) {
                $conversation = $this->conversation->create([
                    'user_id' => $userId,
                    'admin_id' => $adminId,
                    'last_message_at' => now(),
                ]);
            }

            $message = $this->message->create([
                'conversation_id' => $conversation->id,
                'sender_type' => $isAdmin ? 'admin' : 'user',
                'sender_id' => $sender->id,
                'message' => $validated['message'] ?? null,
                'is_read' => false,
            ]);

            if ($request->hasFile('image')) {
                $cdnPath = compressAndUploadImage(
                    $request->file('image'),
                    'chat/attachments',
                    null
                );

                $this->attachment->create([
                    'message_id' => $message->id,
                    'path' => $cdnPath,
                ]);
            }

            if (! $isAdmin && ! empty($validated['message'])) {
                $autoResponse = $this->autoReplyService->getAutoResponse($validated['message']);
                if ($autoResponse) {
                    $this->message->create([
                        'conversation_id' => $conversation->id,
                        'sender_type' => 'admin',
                        'sender_id' => $adminId,
                        'message' => $autoResponse,
                        'is_read' => false,
                    ]);
                }
            }

            $conversation->update(['last_message_at' => now()]);

            return responseHelper(__('Message sent'), 200);
        });
    }

    /**
     * Resolves the account that owns storefront (customer ↔ support) conversations.
     * A dedicated support phone can be configured via CHAT_SUPPORT_ADMIN_PHONE;
     * otherwise the first admin account is used.
     */
    protected function resolveSupportAdmin(): ?User
    {
        $phone = config('chat.support_admin_phone');

        if (! empty($phone)) {
            $admin = User::where('phone', $phone)->first();
            if ($admin) {
                return $admin;
            }
        }

        return User::role('admin')->orderBy('id')->first();
    }

    public function messages(int $conversationId)
    {
        $messages = $this->message
            ->where('conversation_id', $conversationId)
            ->with('attachments')
            ->orderBy('id', 'desc')
            ->get()
            ->reverse()
            ->values();

        return responseHelper(__('Messages retrieved successfully.'), 200, ConversationDetailsResource::collection($messages));
    }

    public function markAsRead(int $conversationId)
    {
        $this->message
            ->where('conversation_id', $conversationId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return responseHelper(__('Conversation marked as read successfully.'), 200);
    }

    public function deleteMessage(int $messageId)
    {
        $message = $this->message
            ->with('attachments')
            ->findOrFail($messageId);

        DB::transaction(function () use ($message) {
            foreach ($message->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->path);
                $attachment->delete();
            }

            $message->delete();
        });

        return responseHelper(__('Message deleted successfully.'), 200);
    }

    public function conversationList(Request $request)
    {
        $query = $this->conversation
            ->with('user')
            ->withCount([
                'messages as unread_count' => function ($q) {
                    $q->where('sender_type', 'user')
                        ->where('is_read', false);
                },
            ])
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $conversations = $request->has('page')
            ? $query->paginate(min(max((int) $request->input('limit', 15), 1), 100))
            : $query->get();

        return responseHelper(__('Conversations retrieved successfully.'), 200, AdminConversationList::collection($conversations));
    }

    public function messageList()
    {
        $userId = auth()->id();

        $conversation = $this->conversation
            ->where('user_id', $userId)
            ->first();

        $messages = [];

        if ($conversation) {
            $messages = $this->message
                ->where('conversation_id', $conversation->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return responseHelper(__('Messages retrieved successfully'), 200, ConversationDetailsResource::collection($messages));
    }

    public function deleteConversation(int $conversationId)
    {
        try {
            $conversation = $this->conversation
                ->with(['messages.attachments'])
                ->find($conversationId);

            if (! $conversation) {
                return responseHelper(__('Conversation already deleted.'), 200);
            }

            DB::transaction(function () use ($conversation) {

                if ($conversation->messages && $conversation->messages->count() > 0) {

                    foreach ($conversation->messages as $message) {

                        if ($message->attachments && $message->attachments->count() > 0) {

                            foreach ($message->attachments as $attachment) {

                                if (! empty($attachment->path) &&
                                    Storage::disk('bunnycdn')->exists($attachment->path)
                                ) {
                                    Storage::disk('bunnycdn')->delete($attachment->path);
                                }

                                $attachment->delete();
                            }
                        }

                        $message->delete();
                    }
                }

                $conversation->delete();
            });

            return responseHelper(__('Conversation deleted successfully.'), 200);

        } catch (\Throwable $e) {

            \Log::error('Conversation delete failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return responseHelper(
                "Conversation could not be deleted. Because :=>: {$e->getMessage()}",
                200
            );
        }
    }
}
