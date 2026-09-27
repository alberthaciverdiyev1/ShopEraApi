<?php

namespace Modules\Listing\Services;

use Illuminate\Support\Carbon;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingValue;

/**
 * Works out which badges an ad has earned.
 *
 * The app owns a fixed registry of keys and draws each one the same way
 * everywhere; the server only says which keys apply. Which answer earns which
 * key is data - a field or a choice carries the key in the panel - so a new
 * section needs no release to get badges.
 */
class ListingBadgeService
{
    /** How long a lowered price keeps showing the arrow. */
    public const PRICE_DROP_DAYS = 7;

    /**
     * The keys a section's own fields can produce, in the order the app should
     * draw them. vip leads because it is the one drawn over the photo.
     */
    public const ORDER = ['vip', 'price_drop', 'extract', 'no_damage', 'repair', 'complex', 'credit', 'mortgage', 'barter'];

    /**
     * Rebuilds the stored badges of one ad from its answers.
     *
     * Called after the answers are written, so a card never has to join the
     * values back together to know what to draw.
     *
     * @return array<int, string>
     */
    public function rebuild(Listing $listing): array
    {
        $badges = $this->fromValues($listing);

        $listing->forceFill(['badges' => $badges])->saveQuietly();

        return $badges;
    }

    /**
     * The badges as the app should see them: the stored ones plus the two that
     * depend on the clock rather than on an answer.
     *
     * @return array<int, string>
     */
    public function present(Listing $listing): array
    {
        $badges = is_array($listing->badges) ? $listing->badges : [];

        if ($listing->isVip()) {
            $badges[] = 'vip';
        }

        if ($this->hasPriceDrop($listing)) {
            $badges[] = 'price_drop';
        }

        return $this->sort(array_values(array_unique($badges)));
    }

    /** A price lowered in the last week, and only while it stays lower. */
    public function hasPriceDrop(Listing $listing): bool
    {
        if ($listing->price === null || $listing->previous_price === null || $listing->price_dropped_at === null) {
            return false;
        }

        if ((float) $listing->previous_price <= (float) $listing->price) {
            return false;
        }

        $droppedAt = $listing->price_dropped_at instanceof Carbon
            ? $listing->price_dropped_at
            : Carbon::parse($listing->price_dropped_at);

        return $droppedAt->greaterThanOrEqualTo(now()->subDays(self::PRICE_DROP_DAYS));
    }

    /**
     * Reads the ad's answers and the badge each field or choice stands for.
     *
     * A key carried by several fields - "vuruğu var" and "rənglənib" both
     * stand for no_damage - is only earned when every one of them agrees, so
     * a repainted car does not claim a clean history.
     *
     * @return array<int, string>
     */
    private function fromValues(Listing $listing): array
    {
        $attributes = ListingAttribute::query()
            ->where('section_id', $listing->section_id)
            ->whereNotNull('badge_key')
            ->get(['id', 'type', 'badge_key', 'badge_when']);

        $values = ListingValue::query()
            ->where('listing_id', $listing->id)
            ->with(['option:id,badge_key'])
            ->get();

        $earned = [];

        // A choice that carries a key earns it on its own: picking "Təmirli"
        // is the whole condition.
        foreach ($values as $value) {
            $key = $value->option?->badge_key;
            if (is_string($key) && $key !== '') {
                $earned[$key] = true;
            }
        }

        // A field that carries a key is a vote: all of its holders must agree.
        $votes = [];
        foreach ($attributes as $attribute) {
            $answers = $values->where('attribute_id', $attribute->id);
            $votes[$attribute->badge_key][] = $this->matches($attribute, $answers->first());
        }

        foreach ($votes as $key => $agreed) {
            if (! in_array(false, $agreed, true)) {
                $earned[$key] = true;
            }
        }

        return $this->sort(array_keys($earned));
    }

    /** Whether one answer is what its field's badge asks for. */
    private function matches(ListingAttribute $attribute, ?ListingValue $value): bool
    {
        $expected = $attribute->badge_when;

        if ($attribute->type === 'boolean') {
            $answer = $value?->value_bool === true;

            // No condition written down means "when it is true".
            return $expected === 'false' ? $answer === false : $answer === true;
        }

        // Any other type simply has to be answered.
        return $value !== null && ($value->value_text !== null || $value->value_number !== null || $value->option_id !== null || $value->value_date !== null);
    }

    /**
     * @param  array<int, string>  $badges
     * @return array<int, string>
     */
    private function sort(array $badges): array
    {
        usort($badges, function (string $left, string $right) {
            $leftAt = array_search($left, self::ORDER, true);
            $rightAt = array_search($right, self::ORDER, true);

            return ($leftAt === false ? PHP_INT_MAX : $leftAt) <=> ($rightAt === false ? PHP_INT_MAX : $rightAt);
        });

        return $badges;
    }
}
