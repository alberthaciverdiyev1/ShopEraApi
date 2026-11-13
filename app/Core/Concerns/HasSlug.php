<?php

namespace App\Core\Concerns;

use Illuminate\Support\Str;

/**
 * Keeps a unique, URL-safe slug in sync with the entity's display name so
 * storefront URLs and filter query strings never expose numeric ids.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    /** The column the slug is derived from. */
    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    protected function generateUniqueSlug(): string
    {
        $attribute = $this->slugSourceColumn();
        $raw = $this->getRawOriginal($attribute) ?? $this->getAttribute($attribute);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? ($decoded['az'] ?? reset($decoded)) : $raw;
        }

        $base = Str::slug((string) $raw) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }
}
