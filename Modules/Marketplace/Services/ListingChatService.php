<?php

namespace Modules\Marketplace\Services;

use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Entities\ListingConversation;
use Modules\Marketplace\Entities\ListingMessage;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

class ListingChatService
{
    /**
     * Find or start the buyer<->seller conversation for a listing. Only a
     * signed-in buyer can message the signed-in user who posted the listing.
     */
    public function start(Product $listing, User $buyer): ListingConversation
    {
        $sellerId = $listing->user_id;

        if (! $sellerId) {
            throw ValidationException::withMessages(['chat' => 'Bu elanın satıcısı qeydiyyatlı istifadəçi deyil.']);
        }

        if ((int) $sellerId === (int) $buyer->id) {
            throw ValidationException::withMessages(['chat' => 'Öz elanınıza mesaj yaza bilməzsiniz.']);
        }

        return ListingConversation::query()->firstOrCreate(
            ['product_id' => $listing->id, 'buyer_id' => $buyer->id],
            ['seller_id' => $sellerId]
        );
    }

    /** The current user's conversations (as buyer or seller). */
    public function conversationsFor(User $user): \Illuminate\Support\Collection
    {
        return ListingConversation::query()
            ->with(['product.images', 'buyer', 'seller'])
            ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ListingConversation $c) => $this->summary($c, $user));
    }

    public function unreadCount(User $user): int
    {
        return ListingMessage::query()
            ->whereHas('conversation', fn ($q) => $q->where(fn ($w) => $w->where('buyer_id', $user->id)->orWhere('seller_id', $user->id)))
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /** Messages of a conversation; the other party's messages get marked read. */
    public function messages(ListingConversation $conversation, User $user): array
    {
        $this->authorizeParticipant($conversation, $user);

        $conversation->messages()->where('sender_id', '!=', $user->id)->where('is_read', false)->update(['is_read' => true]);

        $messages = $conversation->messages()->orderBy('id')->get()->map(fn (ListingMessage $m) => [
            'id' => $m->id,
            'body' => $m->body,
            'mine' => (int) $m->sender_id === (int) $user->id,
            'is_read' => $m->is_read,
            'created_at' => $m->created_at?->toIso8601String(),
        ]);

        return [
            'conversation' => $this->summary($conversation->fresh(['product.images', 'buyer', 'seller']), $user),
            'messages' => $messages,
        ];
    }

    public function send(ListingConversation $conversation, User $user, string $body): array
    {
        $this->authorizeParticipant($conversation, $user);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $body,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => true,
            'is_read' => false,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function summary(ListingConversation $conversation, User $user): array
    {
        $product = $conversation->product;
        $other = (int) $conversation->buyer_id === (int) $user->id ? $conversation->seller : $conversation->buyer;

        $last = $conversation->messages()->latest('id')->first();

        return [
            'id' => $conversation->id,
            'product' => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'image' => optional($product->images->first())->image_path,
            ] : null,
            'other' => $other ? [
                'id' => $other->id,
                'name' => trim($other->name.' '.$other->surname) ?: $other->email,
            ] : null,
            'last_message' => $last?->body,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread' => $conversation->messages()
                ->where('sender_id', '!=', $user->id)->where('is_read', false)->count(),
        ];
    }

    private function authorizeParticipant(ListingConversation $conversation, User $user): void
    {
        abort_unless(
            (int) $conversation->buyer_id === (int) $user->id || (int) $conversation->seller_id === (int) $user->id,
            403,
            'Bu söhbətə girişiniz yoxdur.'
        );
    }
}
