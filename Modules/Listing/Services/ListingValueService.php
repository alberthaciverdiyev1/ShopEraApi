<?php

namespace Modules\Listing\Services;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingAttributeOption;
use Modules\Listing\Http\Entities\ListingSection;
use Modules\Listing\Http\Entities\ListingValue;

/**
 * Checks the answers an ad gives to its section's fields and writes them down
 * twice: as rows in listing_values, which the filters read, and as a snapshot
 * on the ad, which a card or a page renders from without joining anything.
 *
 * The snapshot carries the labels of the moment, so a list of ads costs one
 * query no matter how many choice fields a section has.
 */
class ListingValueService
{
    /**
     * @param  array<string, mixed>  $answers  keyed by field key
     * @return array{values: array<int, array<string, mixed>>, snapshot: array<string, mixed>}
     *
     * @throws ValidationException
     */
    public function prepare(ListingSection $section, array $answers): array
    {
        $fields = $this->parentsFirst($section->fields()->where('is_active', true)->orderBy('sort_order')->get());
        $options = $this->optionsOf($fields);

        $values = [];
        $snapshot = [];
        $errors = [];
        // Remembered so a model can be checked against the make that was
        // actually chosen, not against every make in the list.
        $chosenOptionIds = [];

        foreach ($fields as $field) {
            $given = $answers[$field->key] ?? null;
            $label = $this->label($field);

            if ($this->isBlank($given)) {
                if ($field->is_required) {
                    $errors["attributes.{$field->key}"] = __(':field is required.', ['field' => $label]);
                }

                continue;
            }

            try {
                [$rows, $entry, $pickedIds] = $this->readAnswer($field, $given, $options, $chosenOptionIds);
            } catch (ValidationException $exception) {
                // getMessage() is Laravel's generic line; the real one is in
                // the bag the failure was raised with.
                $errors["attributes.{$field->key}"] = $exception->validator->errors()->first() ?: $exception->getMessage();

                continue;
            }

            $chosenOptionIds[$field->id] = $pickedIds;
            $values = array_merge($values, $rows);
            $snapshot[$field->key] = $entry;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['values' => $values, 'snapshot' => $snapshot];
    }

    /** Replaces an ad's answers with the prepared ones, in one go. */
    public function store(Listing $listing, array $prepared): void
    {
        ListingValue::where('listing_id', $listing->id)->delete();

        foreach ($prepared['values'] as $row) {
            ListingValue::create($row + ['listing_id' => $listing->id]);
        }

        $listing->attribute_values = $prepared['snapshot'];

        // A map answer is mirrored onto the ad's own columns: they are indexed
        // and are what a "near me" search would read one day, while the answer
        // itself stays where every other answer lives.
        foreach ($prepared['snapshot'] as $entry) {
            if (($entry['type'] ?? null) !== 'location' || ! is_array($entry['value'] ?? null)) {
                continue;
            }

            $listing->latitude = $entry['value']['lat'];
            $listing->longitude = $entry['value']['lng'];
            break;
        }

        $listing->save();
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: array<int, int>}
     */
    private function readAnswer(ListingAttribute $field, mixed $given, array $options, array $chosenOptionIds): array
    {
        $base = ['attribute_id' => $field->id];
        $entry = [
            'field' => $field->getTranslations('label'),
            'type' => $field->type,
            'unit' => $field->unit,
            'in_card' => (bool) $field->in_card,
            // jsonb does not keep the order keys were written in - it sorts
            // them by length - so the section's own order travels with each
            // answer, or an ad page would list mileage before make.
            'order' => (int) $field->sort_order,
        ];

        switch ($field->type) {
            case 'number':
                if (! is_numeric($given)) {
                    $this->fail(__(':field must be a number.', ['field' => $this->label($field)]));
                }

                return [[$base + ['value_number' => (float) $given]], $entry + ['value' => (float) $given, 'display' => null], []];

            case 'boolean':
                $value = filter_var($given, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($value === null) {
                    $this->fail(__(':field must be yes or no.', ['field' => $this->label($field)]));
                }

                return [[$base + ['value_bool' => $value]], $entry + ['value' => $value, 'display' => null], []];

            case 'date':
                try {
                    $date = Carbon::parse((string) $given)->toDateString();
                } catch (\Throwable) {
                    $this->fail(__(':field must be a date.', ['field' => $this->label($field)]));
                }

                return [[$base + ['value_date' => $date]], $entry + ['value' => $date, 'display' => null], []];

            case 'select':
            case 'multiselect':
                $wanted = $field->type === 'select' ? [$given] : (is_array($given) ? $given : [$given]);
                $rows = [];
                $picked = [];
                $labels = [];

                foreach ($wanted as $value) {
                    $option = $options[$field->id][(string) $value] ?? null;

                    if (! $option) {
                        $this->fail(__(':field has no such choice.', ['field' => $this->label($field)]));
                    }

                    // A model has to belong to the make the ad picked.
                    if ($field->parent_id && $option->parent_option_id
                        && ! in_array($option->parent_option_id, $chosenOptionIds[$field->parent_id] ?? [], true)) {
                        $this->fail(__(':field does not belong to the choice above it.', ['field' => $this->label($field)]));
                    }

                    $rows[] = $base + ['option_id' => $option->id, 'value_text' => $option->value];
                    $picked[] = $option->id;
                    $labels[] = $option->getTranslations('label');
                }

                $entry += $field->type === 'select'
                    ? ['value' => (string) $wanted[0], 'display' => $labels[0] ?? null]
                    : ['value' => array_map('strval', $wanted), 'display' => $labels];

                return [$rows, $entry, $picked];

            case 'youtube':
                $id = self::youtubeId((string) $given);

                if ($id === null) {
                    $this->fail(__(':field must be a YouTube link.', ['field' => $this->label($field)]));
                }

                // Only the id is kept: the seller pastes whatever the app gave
                // them - watch?v=, youtu.be, /shorts/, with a playlist and a
                // timestamp hanging off it - and the page builds its own URL.
                return [[$base + ['value_text' => $id]], $entry + ['value' => $id, 'display' => null], []];

            case 'location':
                $point = self::point($given);

                if ($point === null) {
                    $this->fail(__(':field must be a point on the map.', ['field' => $this->label($field)]));
                }

                // "lat,lng" is what the filters would read one day; the label
                // the seller saw travels in the snapshot so the ad page can
                // print it without asking a geocoder again.
                return [
                    [$base + ['value_text' => $point['lat'].','.$point['lng']]],
                    $entry + ['value' => $point, 'display' => $point['label'] ?: null],
                    [],
                ];

            default: // text
                $text = trim((string) $given);

                if (mb_strlen($text) > 500) {
                    $this->fail(__(':field is too long.', ['field' => $this->label($field)]));
                }

                return [[$base + ['value_text' => $text]], $entry + ['value' => $text, 'display' => null], []];
        }
    }

    /**
     * The section's own order, except that a field always comes after the one
     * it hangs off. Otherwise an admin who drags "Model" above "Marka" would
     * make every model look like it belongs to no make.
     *
     * @return \Illuminate\Support\Collection<int, ListingAttribute>
     */
    /**
     * The video id inside whatever a seller pasted.
     *
     * They copy the whole address from the YouTube app, so it arrives as
     * `https://youtu.be/ID?si=…`, `watch?v=ID&list=…`, `/shorts/ID`, `/embed/ID`
     * or `/live/ID`. A bare id is accepted too.
     */
    public static function youtubeId(string $given): ?string
    {
        $given = trim($given);

        if ($given === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $given) === 1) {
            return $given;
        }

        $patterns = [
            '/[?&]v=([A-Za-z0-9_-]{11})/',
            '#youtu\.be/([A-Za-z0-9_-]{11})#',
            '#/shorts/([A-Za-z0-9_-]{11})#',
            '#/embed/([A-Za-z0-9_-]{11})#',
            '#/live/([A-Za-z0-9_-]{11})#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $given, $found) === 1) {
                return $found[1];
            }
        }

        return null;
    }

    /**
     * A point as the ad form sends it: {lat, lng, label}. A "lat,lng" string is
     * accepted as well, which is what the stored answer looks like when an ad
     * is re-saved.
     *
     * @return array{lat: float, lng: float, label: string}|null
     */
    public static function point(mixed $given): ?array
    {
        $latitude = null;
        $longitude = null;
        $label = '';

        if (is_array($given)) {
            $latitude = $given['lat'] ?? $given['latitude'] ?? null;
            $longitude = $given['lng'] ?? $given['lon'] ?? $given['longitude'] ?? null;
            $label = trim((string) ($given['label'] ?? $given['address'] ?? ''));
        } elseif (is_string($given) && str_contains($given, ',')) {
            [$latitude, $longitude] = array_pad(explode(',', $given, 2), 2, null);
        }

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [
            'lat' => round($latitude, 7),
            'lng' => round($longitude, 7),
            'label' => mb_substr($label, 0, 255),
        ];
    }

    private function parentsFirst($fields)
    {
        $byId = $fields->keyBy('id');
        $seen = [];
        $ordered = [];

        $visit = function (ListingAttribute $field) use (&$visit, &$seen, &$ordered, $byId) {
            if (isset($seen[$field->id])) {
                return;
            }

            // Marked before the parent is visited, so a field pointing at
            // itself through a chain cannot spin here.
            $seen[$field->id] = true;
            $parent = $field->parent_id ? $byId->get($field->parent_id) : null;

            if ($parent) {
                $visit($parent);
            }

            $ordered[] = $field;
        };

        foreach ($fields as $field) {
            $visit($field);
        }

        return collect($ordered);
    }

    /**
     * Every choice of every field in the section, keyed by field and value, so
     * checking an answer never costs a query.
     *
     * @return array<int, array<string, ListingAttributeOption>>
     */
    private function optionsOf($fields): array
    {
        $ids = $fields->filter(fn (ListingAttribute $field) => $field->usesOptions())->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return ListingAttributeOption::whereIn('attribute_id', $ids)
            ->where('is_active', true)
            ->get()
            ->groupBy('attribute_id')
            ->map(fn ($group) => $group->keyBy('value')->all())
            ->all();
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    private function label(ListingAttribute $field): string
    {
        return (string) $field->label;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['attributes' => $message]);
    }
}
