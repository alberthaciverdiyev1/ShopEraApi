<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Listing\Services\ListingBadgeService;

/**
 * One ad's own page: the gallery, every answer it gave, where it is, and who
 * to call.
 *
 * @property \Modules\Listing\Http\Entities\Listing $resource
 */
class ListingDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        // The ad page is open to everyone, so the token is read through the
        // sanctum guard: $request->user() is null on a route without the auth
        // middleware, and the owner would never see their own rejection note.
        $user = auth('sanctum')->user();
        $isOwner = $user && $user->id === $this->user_id;

        $fields = (new ListingSnapshot($this->attribute_values))->all();
        $cardFields = (new ListingSnapshot($this->attribute_values))->cardFields();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price === null ? null : (float) $this->price,
            'currency' => $this->currency,
            'is_negotiable' => (bool) $this->is_negotiable,
            'is_vip' => $this->isVip(),
            'status' => $this->status,
            'views' => (int) $this->views,

            'section' => $this->whenLoaded('section', fn () => [
                'id' => $this->section->id,
                'key' => $this->section->key,
                'name' => $this->section->name,
                'template' => $this->section->template,
                // Shown next to the call button, e.g. advice against paying a
                // deposit before seeing the goods.
                'warning_text' => $this->section->warning_text,
            ]),

            'fields' => $fields,

            // Printed text, added so a page reads the same in every app. The
            // raw keys above stay exactly as they were.
            'price_label' => ListingText::price($this->price === null ? null : (float) $this->price, $this->currency),
            // The one-line summary under the title: "2015 · 197 000 km · 1.8 L".
            'summary_fields' => ListingText::summary($cardFields),
            'badges' => app(ListingBadgeService::class)->present($this->resource),
            'published_at_label' => ListingText::moment($this->published_at ?? $this->created_at),
            'expires_at_label' => ListingText::date($this->expires_at),

            // The seller's YouTube link, ready to play. The page is served
            // from our own origin because YouTube refuses to configure an
            // embed it cannot attribute to a host (error 153).
            'video' => $this->video($fields),

            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type,
                'url' => $item->url(),
                'thumbnail_url' => $item->thumbnailUrl(),
                // A video is squeezed in the background, so it may still be
                // on its way.
                'status' => $item->status,
                'duration_seconds' => $item->duration_seconds,
            ])->values()),

            'location' => [
                'city' => $this->whenLoaded('city', fn () => $this->city?->name),
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'seller' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'member_since' => optional($this->user?->created_at)->toDateString(),
                // Shown as a chip next to the name. A seller who runs a shop
                // here is worth telling apart from someone selling one car.
                'type' => $this->sellerType(),
                'member_since_label' => ListingText::date($this->user?->created_at),
            ]),

            'contact' => [
                'name' => $this->contact_name,
                'phone' => $this->contact_phone,
                // What the call button prints; the dialled number stays raw.
                'phone_label' => ListingText::phone($this->contact_phone),
            ],

            'published_at' => optional($this->published_at)->toIso8601String(),
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),

            // Only the owner needs to know why a moderator sent it back.
            'moderation_note' => $this->when($isOwner, fn () => $this->moderation_note),
            'is_owner' => (bool) $isOwner,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, string>|null
     */
    private function video(array $fields): ?array
    {
        foreach ($fields as $field) {
            if (($field['type'] ?? null) !== 'youtube' || ! is_string($field['value'] ?? null)) {
                continue;
            }

            $id = $field['value'];

            return [
                'id' => $id,
                'player_url' => route('live.player', ['video' => $id]),
                'thumbnail_url' => "https://img.youtube.com/vi/{$id}/hqdefault.jpg",
                'watch_url' => "https://www.youtube.com/watch?v={$id}",
            ];
        }

        return null;
    }

    /** "Mağaza" for a seller with a shop on the marketplace, otherwise a person. */
    private function sellerType(): string
    {
        $hasStore = DB::table('stores')
            ->where('user_id', $this->user_id)
            ->whereNull('deleted_at')
            ->exists();

        return $hasStore ? 'Mağaza' : 'Fərdi satıcı';
    }
}
