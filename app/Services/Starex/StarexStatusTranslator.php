<?php

namespace App\Services\Starex;

use Illuminate\Support\Str;

class StarexStatusTranslator
{
    public static function label(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $status = self::normalize($status);
        $key = "starex.statuses.{$status}";
        $translated = __($key);

        return $translated === $key ? Str::headline($status) : $translated;
    }

    public static function shortLabel(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $status = self::normalize($status);
        $key = "starex.short_statuses.{$status}";
        $translated = __($key);

        return $translated === $key ? self::label($status) : $translated;
    }

    private static function normalize(string $status): string
    {
        return Str::of($status)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}
