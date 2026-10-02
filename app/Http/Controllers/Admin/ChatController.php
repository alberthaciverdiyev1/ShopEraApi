<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Entities\Conversation;
use Modules\Chat\Entities\Message;
use Modules\Chat\Http\SendMessageRequest;
use Modules\Chat\Services\ChatService;

class ChatController extends AdminController
{
    protected string $title = 'Mesajlar';

    public function __construct(private readonly ChatService $service) {}

    public function index(Request $request)
    {
        $query = Conversation::query()
            ->with('user')
            ->withCount([
                'messages as unread_count' => fn ($q) => $q->where('sender_type', 'user')->where('is_read', false),
            ])
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->whereHas('user', function ($user) use ($term) {
                $user->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $conversations = $query->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.chat._list', ['conversations' => $conversations]);
        }

        return view('admin.pages.chat.index', [
            'title' => $this->title,
            'conversations' => $conversations,
            'active' => null,
            'messages' => collect(),
            'filters' => $request->only(['q']),
        ]);
    }

    public function show(Request $request, int $conversationId)
    {
        $conversation = Conversation::query()->with('user')->findOrFail($conversationId);

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('attachments')
            ->orderBy('id')
            ->get();

        if ($this->isHtmx($request)) {
            return view('admin.pages.chat._messages', [
                'active' => $conversation,
                'messages' => $messages,
            ]);
        }

        $conversations = Conversation::query()
            ->with('user')
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'user')->where('is_read', false)])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('admin.pages.chat.index', [
            'title' => $this->title,
            'conversations' => $conversations,
            'active' => $conversation,
            'messages' => $messages,
            'filters' => [],
        ]);
    }

    public function send(Request $request, int $conversationId)
    {
        $conversation = Conversation::query()->findOrFail($conversationId);

        $admin = admin_user();
        $previous = Auth::guard('web')->user();
        Auth::guard('web')->setUser($admin);

        try {
            $form = SendMessageRequest::createFrom($request);
            $form->merge(['target_user_id' => $conversation->user_id]);
            $form->setContainer(app());
            $form->setRedirector(app('redirect'));
            $form->validateResolved();

            $this->service->sendMessage($form);
        } finally {
            $previous ? Auth::guard('web')->setUser($previous) : Auth::guard('web')->forgetUser();
        }

        if ($this->isHtmx($request)) {
            $messages = Message::query()->where('conversation_id', $conversation->id)->with('attachments')->orderBy('id')->get();

            return response()
                ->view('admin.pages.chat._messages', ['active' => $conversation, 'messages' => $messages])
                ->header('HX-Trigger', $this->htmxTriggers(['toast' => ['type' => 'success', 'message' => 'Mesaj göndərildi.']]));
        }

        return redirect()->route('admin.chat.show', $conversation->id)->with('status', __('Mesaj göndərildi.'));
    }

    public function destroyMessage(int $id)
    {
        $message = Message::query()->findOrFail($id);
        $this->service->deleteMessage($message->id);

        return response('', 204)->header('HX-Trigger', $this->htmxTriggers([
            'toast' => ['type' => 'success', 'message' => 'Mesaj silindi.'],
            'chat:refresh' => true,
        ]));
    }

    public function destroyConversation(int $id)
    {
        $this->service->deleteConversation($id);

        return redirect()->route('admin.chat.index')->with('status', __('Söhbət silindi.'));
    }
}
