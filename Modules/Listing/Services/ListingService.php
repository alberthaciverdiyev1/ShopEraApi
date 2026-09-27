<?php

namespace Modules\Listing\Services;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingSection;
use Modules\Listing\Http\Resources\ListingCardResource;
use Modules\Listing\Http\Resources\ListingDetailResource;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * The ads themselves: posting one, finding one, and what the owner can do
 * with it afterwards.
 *
 * The filters are the reason listing_values exists. A section's fields are
 * data, so "make = BMW and year between 2015 and 2020" cannot be columns; it
 * becomes one EXISTS per filter over an indexed table instead.
 */
class ListingService
{
    private const MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ListingValueService $values,
        private readonly ListingBadgeService $badges,
    ) {
    }

    /**
     * The filtered list as a query, so the apps and the website ask for the
     * same ads in the same way - one implementation of "make = BMW and year
     * between 2015 and 2020", not two that drift apart.
     */
    public function browse(Request $request, ?ListingSection $section = null): Builder
    {
        $section ??= $this->section($request->input('section'));

        $query = Listing::query()
            ->visible()
            ->with(['section', 'city', 'media']);

        if ($section) {
            $query->where('section_id', $section->id);
        }

        $this->applyCommonFilters($query, $request);

        if ($section) {
            $this->applyFieldFilters($query, $section, (array) $request->input('f', []));
        }

        $this->applySort($query, (string) $request->input('sort', 'newest'));

        return $query;
    }

    public function index(Request $request)
    {
        $page = $this->browse($request)
            ->paginate(min(max((int) $request->integer('per_page', 20), 1), self::MAX_PER_PAGE));

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'items' => ListingCardResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function show(int $id, Request $request)
    {
        $listing = Listing::with(['section', 'city', 'media', 'user'])->find($id);
        // The route is open, so the token is read through the sanctum guard
        // the way the catalogue does it; a guest simply has no user here.
        $user = auth('sanctum')->user();

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $isOwner = $user && $user->id === $listing->user_id;
        $visible = $listing->status === Listing::STATUS_ACTIVE
            && (! $listing->expires_at || $listing->expires_at->isFuture());

        // An ad that is waiting, refused or run out is still its owner's to
        // look at; to everyone else it does not exist.
        if (! $visible && ! $isOwner && ! $this->isModerator($user)) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        if (! $isOwner && $visible) {
            // Not through the model, so touching the view counter never
            // rewrites updated_at or fires model events.
            Listing::whereKey($listing->id)->update(['views' => DB::raw('views + 1')]);
            $listing->views = (int) $listing->views + 1;
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, (new ListingDetailResource($listing))->resolve());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $section = $this->section($validated['section_key'] ?? ($validated['section_id'] ?? null));

        if (! $section || ! $section->is_active) {
            return responseHelper('Section not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $prepared = $this->values->prepare($section, (array) $request->input('attributes', []));

        $listing = new Listing([
            'section_id' => $section->id,
            'user_id' => $request->user()->id,
        ]);

        // The ad and its answers are one thing; a half-saved ad would show up
        // in the list with an empty form behind it.
        DB::transaction(function () use ($listing, $section, $validated, $request, $prepared) {
            $this->fill($listing, $validated, $request);
            $this->publish($listing, $section);
            $listing->save();

            $this->values->store($listing, $prepared);
            $this->badges->rebuild($listing);
        });

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'status' => $listing->status,
            // The apps say "your ad is live" or "a moderator will look at it"
            // from this, rather than deciding it themselves.
            'needs_review' => $listing->status === Listing::STATUS_PENDING,
        ]);
    }

    public function update(int $id, Request $request)
    {
        $listing = Listing::with('section')->find($id);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate($this->rules(false));
        $section = $listing->section;
        $prepared = $request->has('attributes')
            ? $this->values->prepare($section, (array) $request->input('attributes', []))
            : null;

        DB::transaction(function () use ($listing, $section, $validated, $request, $prepared) {
            $before = $listing->price === null ? null : (float) $listing->price;

            $this->fill($listing, $validated, $request);

            $this->rememberPriceDrop($listing, $before);

            // An edited ad goes back through the moderator, unless the section
            // trusts its posters.
            if ($listing->status === Listing::STATUS_ACTIVE && ! $section->auto_approve) {
                $listing->status = Listing::STATUS_PENDING;
            }

            if ($listing->status === Listing::STATUS_REJECTED) {
                $listing->status = $section->auto_approve ? Listing::STATUS_ACTIVE : Listing::STATUS_PENDING;
                $listing->moderation_note = null;
            }

            $listing->save();

            if ($prepared !== null) {
                $this->values->store($listing, $prepared);
                $this->badges->rebuild($listing);
            }
        });

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'status' => $listing->status,
            'needs_review' => $listing->status === Listing::STATUS_PENDING,
        ]);
    }

    /**
     * The rail at the foot of an ad: a few live ads from the same section,
     * nearest in price first.
     *
     * Its own request rather than part of the page, so the page paints while
     * this is still on its way and an empty answer simply hides the block.
     */
    public function similar(int $id, Request $request)
    {
        $listing = Listing::find($id);

        if (! $listing) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $query = Listing::query()
            ->visible()
            ->where('section_id', $listing->section_id)
            ->whereKeyNot($listing->id)
            ->with(['section', 'city', 'media']);

        if ($listing->price !== null) {
            // Closest price first; an ad without one falls to the end rather
            // than to the front, which ORDER BY on a null would do.
            $query->orderByRaw('CASE WHEN price IS NULL THEN 1 ELSE 0 END, ABS(price - ?)', [(float) $listing->price]);
        }

        $items = $query->orderByDesc('is_vip')
            ->orderByDesc('published_at')
            ->limit(min(max((int) $request->integer('limit', 10), 1), 20))
            ->get();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'items' => ListingCardResource::collection($items)->resolve(),
        ]);
    }

    public function destroy(int $id, Request $request)
    {
        $listing = Listing::find($id);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->delete();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK);
    }

    /** The owner's own list, whatever state each ad is in. */
    public function mine(Request $request)
    {
        $query = Listing::query()
            ->where('user_id', $request->user()->id)
            ->with(['section', 'city', 'media'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->orderByDesc('id');

        $page = $query->paginate(min(max((int) $request->integer('per_page', 20), 1), self::MAX_PER_PAGE));

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'items' => ListingCardResource::collection($page->items())->resolve(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    /** One tap in the owner's cabinet puts an ad that ran out back on air. */
    public function renew(int $id, Request $request)
    {
        $listing = Listing::with('section')->find($id);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        if (! in_array($listing->status, [Listing::STATUS_EXPIRED, Listing::STATUS_ARCHIVED], true)) {
            return responseHelper('Only an expired or archived listing can be renewed.', StatusCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        // An ad that was already on air once goes straight back: renewing is
        // not editing, so there is nothing new for a moderator to look at.
        $wasApproved = $listing->published_at !== null;

        $this->publish($listing, $listing->section);

        if ($wasApproved) {
            $listing->status = Listing::STATUS_ACTIVE;
            $listing->expires_at = now()->addDays($listing->section->duration_days ?: 30);
        }

        $listing->renewed_at = now();
        $listing->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'id' => $listing->id,
            'status' => $listing->status,
            'expires_at' => optional($listing->expires_at)->toIso8601String(),
        ]);
    }

    public function archive(int $id, Request $request)
    {
        $listing = Listing::find($id);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing->update(['status' => Listing::STATUS_ARCHIVED]);

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['status' => $listing->status]);
    }

    private function rules(bool $creating = true): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'section_id' => [$creating ? 'required_without:section_key' : 'prohibited', 'integer', 'exists:listing_sections,id'],
            'section_key' => [$creating ? 'required_without:section_id' : 'prohibited', 'string', 'max:40'],
            'title' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_negotiable' => ['nullable', 'boolean'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'attributes' => ['nullable', 'array'],
        ];
    }

    private function fill(Listing $listing, array $validated, Request $request): void
    {
        foreach (['title', 'description', 'price', 'currency', 'is_negotiable', 'city_id', 'address', 'latitude', 'longitude', 'contact_name', 'contact_phone'] as $column) {
            if (array_key_exists($column, $validated)) {
                $listing->{$column} = $validated[$column];
            }
        }

        // Whoever posts is who gets called, unless they typed another number.
        $listing->contact_name = $listing->contact_name ?: $request->user()->name;
        $listing->contact_phone = $listing->contact_phone ?: $request->user()->phone;
    }

    /** Puts an ad on air, or in the queue, by the section's own rule. */
    /**
     * Keeps the old price for a week when the seller lowers it, so the card
     * can show the drop. Raising it, or leaving it alone, clears the mark.
     */
    private function rememberPriceDrop(Listing $listing, ?float $before): void
    {
        $after = $listing->price === null ? null : (float) $listing->price;

        if ($before !== null && $after !== null && $after < $before) {
            $listing->previous_price = $before;
            $listing->price_dropped_at = now();

            return;
        }

        if ($before !== $after) {
            $listing->previous_price = null;
            $listing->price_dropped_at = null;
        }
    }

    private function publish(Listing $listing, ListingSection $section): void
    {
        $listing->status = $section->auto_approve ? Listing::STATUS_ACTIVE : Listing::STATUS_PENDING;
        $listing->moderation_note = null;

        if ($listing->status === Listing::STATUS_ACTIVE) {
            $listing->published_at = $listing->published_at ?: now();
            $listing->expires_at = now()->addDays($section->duration_days ?: 30);
        }
    }

    private function applyCommonFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('search'), fn (Builder $query) => $query->where('title', 'ilike', '%' . $request->string('search') . '%'))
            ->when($request->filled('price_from'), fn (Builder $query) => $query->where('price', '>=', (float) $request->input('price_from')))
            ->when($request->filled('price_to'), fn (Builder $query) => $query->where('price', '<=', (float) $request->input('price_to')))
            ->when($request->filled('city_id'), fn (Builder $query) => $query->where('city_id', $request->integer('city_id')))
            ->when($request->boolean('vip_only'), fn (Builder $query) => $query->where('is_vip', true)
                ->where(fn (Builder $query) => $query->whereNull('vip_until')->orWhere('vip_until', '>', now())));
    }

    /**
     * One EXISTS per asked-for field. Only fields the section marked for the
     * filter are honoured, so a crafted query cannot search by something the
     * admin hid.
     */
    private function applyFieldFilters(Builder $query, ListingSection $section, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $fields = ListingAttribute::where('section_id', $section->id)
            ->where('is_active', true)
            ->where('in_filter', true)
            ->get()
            ->keyBy('key');

        foreach ($filters as $key => $value) {
            $field = $fields[$key] ?? null;

            if (! $field || $value === null || $value === '' || $value === []) {
                continue;
            }

            $query->whereExists(function (QueryBuilder $sub) use ($field, $value) {
                $sub->from('listing_values')
                    ->whereColumn('listing_values.listing_id', 'listings.id')
                    ->where('listing_values.attribute_id', $field->id);

                $this->matchValue($sub, $field, $value);
            });
        }
    }

    private function matchValue(QueryBuilder $sub, ListingAttribute $field, mixed $value): void
    {
        $range = is_array($value) && (array_key_exists('from', $value) || array_key_exists('to', $value));

        switch ($field->type) {
            case 'number':
                if ($range) {
                    if (filled($value['from'] ?? null)) {
                        $sub->where('listing_values.value_number', '>=', (float) $value['from']);
                    }

                    if (filled($value['to'] ?? null)) {
                        $sub->where('listing_values.value_number', '<=', (float) $value['to']);
                    }

                    return;
                }

                $sub->where('listing_values.value_number', (float) $value);

                return;

            case 'boolean':
                $sub->where('listing_values.value_bool', filter_var($value, FILTER_VALIDATE_BOOLEAN));

                return;

            case 'date':
                if ($range) {
                    if (filled($value['from'] ?? null)) {
                        $sub->whereDate('listing_values.value_date', '>=', $value['from']);
                    }

                    if (filled($value['to'] ?? null)) {
                        $sub->whereDate('listing_values.value_date', '<=', $value['to']);
                    }

                    return;
                }

                $sub->whereDate('listing_values.value_date', $value);

                return;

            case 'select':
            case 'multiselect':
                // Several choices mean "any of them", as a filter screen does.
                $sub->whereIn('listing_values.value_text', array_map('strval', (array) $value));

                return;

            default:
                $sub->where('listing_values.value_text', 'ilike', '%' . $value . '%');
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        // Paid-for ads ride on top of every ordering; that is what VIP buys.
        $query->orderByRaw('(is_vip AND (vip_until IS NULL OR vip_until > now())) DESC');

        match ($sort) {
            'price_asc' => $query->orderByRaw('price ASC NULLS LAST'),
            'price_desc' => $query->orderByRaw('price DESC NULLS LAST'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $query->orderByDesc('id');
    }

    private function section(mixed $keyOrId): ?ListingSection
    {
        if (blank($keyOrId)) {
            return null;
        }

        return ListingSection::query()
            ->when(is_numeric($keyOrId), fn ($query) => $query->whereKey((int) $keyOrId), fn ($query) => $query->where('key', (string) $keyOrId))
            ->first();
    }

    private function isModerator(mixed $user): bool
    {
        return $user && method_exists($user, 'can') && $user->can('manage listings');
    }
}
