<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Http\SendMessageRequest;
use Modules\Chat\Services\ChatService;

class ChatController extends AdminController
{
    protected string $title = 'Mesajlar';

    public function __construct(private readonly ChatService $service) {}

    public function index(Request $request)
    {
        $conversations = $this->service->adminConversationQuery($request)->paginate(20)->withQueryString();

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
        ['conversation' => $conversation, 'messages' => $messages] = $this->service->adminOpenConversation($conversationId);

        if ($this->isHtmx($request)) {
            return view('admin.pages.chat._messages', [
                'active' => $conversation,
                'messages' => $messages,
            ]);
        }

        return view('admin.pages.chat.index', [
            'title' => $this->title,
            'conversations' => $this->service->adminRecentConversations(),
            'active' => $conversation,
            'messages' => $messages,
            'filters' => [],
        ]);
    }

    public function send(Request $request, int $conversationId)
    {
        $conversation = $this->service->adminFindConversation($conversationId);

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
            return response()
                ->view('admin.pages.chat._messages', [
                    'active' => $conversation,
                    'messages' => $this->service->adminMessages($conversation->id),
                ])
                ->header('HX-Trigger', $this->htmxTriggers(['toast' => ['type' => 'success', 'message' => 'Mesaj göndərildi.']]));
        }

        return redirect()->route('admin.chat.show', $conversation->id)->with('status', __('Mesaj göndərildi.'));
    }

    public function destroyMessage(int $id)
    {
        $this->service->deleteMessage($id);

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
