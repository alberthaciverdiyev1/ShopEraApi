<?php

namespace Modules\Listing\Console;

use Illuminate\Console\Command;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Services\ListingBadgeService;
use Modules\Listing\Services\ListingValueService;

/**
 * An ad keeps a snapshot of its answers with the labels and units of the
 * moment, which is what makes a list of ads one query. The price of that is
 * that renaming a field in the panel - or giving it a unit, as "otaqlı" was
 * given to rooms - leaves the old wording on ads already posted.
 *
 * This rewrites those snapshots from the answers themselves. The answers are
 * not touched, only how they are spelled.
 */
class RefreshListingValues extends Command
{
    protected $signature = 'listings:refresh-values {--section= : yalnız bir bölmə (key)}';

    protected $description = 'Rewrite the stored answer snapshots with the current field labels and units';

    public function handle(ListingValueService $values, ListingBadgeService $badges): int
    {
        $query = Listing::with(['section', 'values'])
            ->when($this->option('section'), fn ($query) => $query->whereHas(
                'section',
                fn ($section) => $section->where('key', $this->option('section'))
            ));

        $touched = 0;

        foreach ($query->cursor() as $listing) {
            $fields = ListingAttribute::where('section_id', $listing->section_id)->get()->keyBy('id');
            $answers = [];

            foreach ($listing->values as $value) {
                $field = $fields->get($value->attribute_id);
                if (! $field) {
                    continue;
                }

                $answer = match ($field->type) {
                    'number' => $value->value_number,
                    'boolean' => $value->value_bool,
                    'date' => $value->value_date,
                    default => $value->value_text,
                };

                if ($answer === null) {
                    continue;
                }

                // A multi-select has one row per chosen option.
                if ($field->type === 'multiselect') {
                    $answers[$field->key] = array_merge((array) ($answers[$field->key] ?? []), [$answer]);
                    continue;
                }

                $answers[$field->key] = $answer;
            }

            if ($answers === []) {
                continue;
            }

            try {
                $values->store($listing, $values->prepare($listing->section, $answers));
                // The badges are worked out from the same answers, so an ad
                // posted before a field was tied to a badge picks it up here.
                $badges->rebuild($listing);
                $touched++;
            } catch (\Throwable $exception) {
                $this->warn('#' . $listing->id . ': ' . $exception->getMessage());
            }
        }

        $this->info($touched . ' listing(s) refreshed.');

        return self::SUCCESS;
    }
}
