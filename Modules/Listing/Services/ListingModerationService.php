<?php

namespace Modules\Listing\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Resources\ListingCardResource;
use Modules\Listing\Http\Resources\ListingDetailResource;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * The moderator's side of an ad: the queue, the yes, the no, and the VIP
 * badge. Nothing here deletes an ad outright — a refused ad keeps its note so
 * the owner can see what to fix and send it again.
 */
class ListingModerationService
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request)
    {
        $status = (string) $request->input('status', Listing::STATUS_PENDING);

        $query = Listing::query()
            ->with(['section', 'city', 'media', 'user'])
            // The queue is the point of this screen, so it is what you get
            // unless another status is asked for. "all" is how the panel says
            // "show me everything", including the ads that are already live.
            ->when(
                $status !== '' && $status !== 'all',
                fn (Builder $query) => $query->where('status', $status),
            )
            ->when($request->filled('section_id'), fn (Builder $query) => $query->where('section_id', $request->integer('section_id')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('search'), fn (Builder $query) => $query->where('title', 'ilike', '%' . $request->string('search') . '%'))
            ->orderByDesc('id');

        $page = $query->paginate(min(max((int) $request->integer('per_page', 20), 1), self::MAX_PER_PAGE));

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'items' => collect($page->items())->map(fn (Listing $listing) => $this->row($listing))->all(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'pending_count' => Listing::where('status', Listing::STATUS_PENDING)->count(),
        ]);
    }

    public function show(int $id, Request $request)
    {
        $listing = Listing::with(['section', 'city', 'media', 'user', 'moderator'])->find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $payload = (new ListingDetailResource($listing))->resolve();
        $payload['moderation'] = [
            'status' => $listing->status,
            'note' => $listing->moderation_note,
            'moderated_at' => optional($listing->moderated_at)->toIso8601String(),
            'moderator' => $listing->moderator?->name,
        ];

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $payload);
    }

    public function approve(int $id, Request $request)
    {
        $listing = Listing::with('section')->find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->status = Listing::STATUS_ACTIVE;
        $listing->moderation_note = null;
        $listing->moderated_by = $request->user()->id;
        $listing->moderated_at = now();
        $listing->published_at = $listing->published_at ?: now();
        // The clock starts when the ad goes on air, not when it was written.
        $listing->expires_at = now()->addDays($listing->section->duration_days ?: 30);
        $listing->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'status' => $listing->status,
            'expires_at' => optional($listing->expires_at)->toIso8601String(),
        ]);
    }

    public function reject(int $id, Request $request)
    {
        $validated = $request->validate([
            // The owner is shown this, so it is required: "no" without a
            // reason only produces a second identical ad.
            'note' => ['required', 'string', 'max:500'],
        ]);

        $listing = Listing::find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->status = Listing::STATUS_REJECTED;
        $listing->moderation_note = $validated['note'];
        $listing->moderated_by = $request->user()->id;
        $listing->moderated_at = now();
        $listing->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'status' => $listing->status,
        ]);
    }

    /** The VIP badge: a flag with an end date, set by hand for now. */
    public function feature(int $id, Request $request)
    {
        $validated = $request->validate([
            'is_vip' => ['required', 'boolean'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $listing = Listing::find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->is_vip = (bool) $validated['is_vip'];
        $listing->vip_until = $listing->is_vip && ! empty($validated['days'])
            ? now()->addDays((int) $validated['days'])
            : null;
        $listing->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'is_vip' => $listing->isVip(),
            'vip_until' => optional($listing->vip_until)->toIso8601String(),
        ]);
    }

    public function destroy(int $id)
    {
        $listing = Listing::find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->delete();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK);
    }

    private function row(Listing $listing): array
    {
        $row = (new ListingCardResource($listing))->resolve();

        $row['moderation_note'] = $listing->moderation_note;
        $row['views'] = (int) $listing->views;
        $row['created_at'] = optional($listing->created_at)->toIso8601String();
        $row['user'] = $listing->relationLoaded('user') && $listing->user
            ? ['id' => $listing->user->id, 'name' => $listing->user->name, 'phone' => $listing->user->phone]
            : null;

        return $row;
    }
}
