<?php

namespace Modules\Listing\Http\Resources;

/**
 * Reads the answers stored on an ad.
 *
 * They are kept with their labels in every language, so rendering picks the
 * one the reader asked for without touching the fields table.
 */
class ListingSnapshot
{
    public function __construct(private readonly ?array $snapshot)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->map(fn () => true);
    }

    /** @return array<int, array<string, mixed>> */
    public function cardFields(): array
    {
        return $this->map(fn (array $entry) => ($entry['in_card'] ?? false) === true);
    }

    /** @return array<int, array<string, mixed>> */
    private function map(callable $keep): array
    {
        $out = [];
        $entries = $this->snapshot ?? [];

        // The section's order, which jsonb did not keep. Ads written before
        // the order was stored simply keep the order they come back in.
        uasort($entries, fn ($left, $right) => ((int) ($left['order'] ?? 0)) <=> ((int) ($right['order'] ?? 0)));

        foreach ($entries as $key => $entry) {
            if (! is_array($entry) || ! $keep($entry)) {
                continue;
            }

            $display = $entry['display'] ?? null;

            $out[] = [
                'key' => $key,
                'label' => $this->text($entry['field'] ?? null),
                'type' => $entry['type'] ?? 'text',
                'unit' => $entry['unit'] ?? null,
                'value' => $entry['value'] ?? null,
                // What to print: the choice's label when there is one, the
                // raw answer otherwise. A choice carries its label in every
                // language; a map point carries one plain string.
                'display' => is_string($display)
                    ? $display
                    : (is_array($display) && ! array_is_list($display)
                        ? $this->text($display)
                        : (is_array($display) ? array_map(fn ($item) => $this->text($item), $display) : null)),
            ];
        }

        return $out;
    }

    private function text(mixed $translations): ?string
    {
        if (is_string($translations)) {
            return $translations;
        }

        if (! is_array($translations) || $translations === []) {
            return null;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'az');

        return $translations[$locale] ?? $translations[$fallback] ?? (string) reset($translations);
    }
}
