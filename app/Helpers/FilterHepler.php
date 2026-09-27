<?php

if (!function_exists('fillFilterLanguages')) {
    /**
     * Complete missing languages from "az" and lower-case every value,
     * mirroring the Go filter helper (FillLower).
     */
    function fillFilterLanguages($value): array
    {
        $value = is_array($value) ? $value : [];
        $source = $value['az'] ?? '';

        foreach (['az', 'ru', 'en', 'tr'] as $lang) {
            if (empty($value[$lang])) {
                $value[$lang] = $source;
            }
            $value[$lang] = mb_strtolower((string) $value[$lang]);
        }

        return $value;
    }
}
