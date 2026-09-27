<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class PhoneHelper
{
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '994')) {
            return '0' . substr($digits, -9);
        }

        if (strlen($digits) === 9) {
            return '0' . $digits;
        }

        return $digits;
    }

    public static function wherePhone(Builder $query, string $phone): Builder
    {
        $normalized = self::normalize($phone);

        return $query->whereRaw(
            <<<'SQL'
            CASE
                WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 12
                    AND REGEXP_REPLACE(phone, '[^0-9]', '', 'g') LIKE '994%'
                    THEN '0' || RIGHT(REGEXP_REPLACE(phone, '[^0-9]', '', 'g'), 9)
                WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 9
                    THEN '0' || REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
                ELSE REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
            END = ?
            SQL,
            [$normalized]
        );
    }
}
