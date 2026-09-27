<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Support\Carbon;

/**
 * Turns an ad's raw answers into the strings a card or a page prints.
 *
 * The formatting lives here rather than in the apps because the rules belong
 * to the section, not to the screen: a mileage is grouped, an engine is shown
 * in litres though it is stored in cm³, a flat's floor reads "6/9 mərtəbə".
 * An older app that does not know these keys keeps formatting on its own -
 * every key added here is new.
 */
class ListingText
{
    private const MONTHS = [
        1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel',
        5 => 'may', 6 => 'iyun', 7 => 'iyul', 8 => 'avqust',
        9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr',
    ];

    private const CURRENCIES = ['AZN' => '₼', 'USD' => '$', 'EUR' => '€'];

    /** "26 800 ₼", or null when the ad is up for negotiation. */
    public static function price(?float $price, ?string $currency): ?string
    {
        if ($price === null) {
            return null;
        }

        $symbol = self::CURRENCIES[strtoupper((string) $currency)] ?? (string) $currency;

        // A price is never a year, so four digits are grouped too: 2200 → "2 200".
        return trim(self::number($price, true).' '.$symbol);
    }

    /** "bu gün, 23:51" · "dünən, 21:04" · "19 sentyabr 2026, 08:12". */
    public static function moment(?Carbon $at): ?string
    {
        if ($at === null) {
            return null;
        }

        $local = $at->copy()->timezone(config('app.timezone'));
        $clock = $local->format('H:i');

        if ($local->isToday()) {
            return "bu gün, {$clock}";
        }

        if ($local->isYesterday()) {
            return "dünən, {$clock}";
        }

        return self::date($local).", {$clock}";
    }

    /**
     * "+994559999900" → "(055) 999-99-00".
     *
     * The number the app dials is the raw one; this is only what the button
     * prints, so it can be read off the screen and remembered.
     */
    public static function phone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '994')) {
            $digits = substr($digits, 3);
        }

        $digits = ltrim($digits, '0');

        // Anything that is not a nine-digit local number is left as it was
        // typed rather than cut to fit.
        if (strlen($digits) !== 9) {
            return $phone;
        }

        return sprintf(
            '(0%s) %s-%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 2),
            substr($digits, 7, 2),
        );
    }

    /** "21 oktyabr 2026". */
    public static function date(?Carbon $at): ?string
    {
        if ($at === null) {
            return null;
        }

        $local = $at->copy()->timezone(config('app.timezone'));

        return $local->day.' '.(self::MONTHS[$local->month] ?? '').' '.$local->year;
    }

    /**
     * The two or three answers a card prints, already joined with their units.
     *
     * Floor and storeys arrive as two numbers and would read "6, 9"; they are
     * merged into the "6/9 mərtəbə" every property site uses.
     *
     * @param  array<int, array<string, mixed>>  $fields  from ListingSnapshot
     * @return array<int, string>
     */
    public static function summary(array $fields): array
    {
        $byKey = [];
        foreach ($fields as $field) {
            $byKey[$field['key']] = $field;
        }

        $parts = [];

        foreach ($fields as $field) {
            $key = $field['key'];

            if ($key === 'floors_total' && isset($byKey['floor'])) {
                continue;
            }

            if ($key === 'floor' && isset($byKey['floors_total'])) {
                $floor = self::plain($field['value'] ?? null);
                $total = self::plain($byKey['floors_total']['value'] ?? null);

                if ($floor !== '' && $total !== '') {
                    $parts[] = trim($floor.'/'.$total.' '.($byKey['floors_total']['unit'] ?? ''));
                }

                continue;
            }

            $text = self::value($field);
            if ($text !== null && $text !== '') {
                $parts[] = $text;
            }
        }

        return $parts;
    }

    /**
     * One answer as it is printed: the choice's own label, or the number with
     * its unit.
     *
     * @param  array<string, mixed>  $field  one entry of ListingSnapshot
     */
    public static function value(array $field): ?string
    {
        $display = $field['display'] ?? null;

        if (is_array($display)) {
            return implode(', ', array_filter($display));
        }

        if (is_string($display) && $display !== '') {
            return $display;
        }

        $value = $field['value'] ?? null;
        $type = $field['type'] ?? 'text';
        $unit = $field['unit'] ?? null;

        if ($type === 'boolean') {
            return $value ? 'Bəli' : 'Xeyr';
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return $unit ? $value.' '.$unit : (string) $value;
        }

        // Engines are kept in cm³ so they can be filtered as whole numbers,
        // but every car site writes them in litres.
        if ($unit === 'sm³') {
            $litres = (float) $value / 1000;

            return $litres <= 0 ? null : number_format($litres, 1, '.', '').' L';
        }

        // "2200 km" is grouped, "2015" is not - a bare number with no unit is
        // a year far more often than it is a quantity.
        $text = self::number((float) $value, $unit !== null && $unit !== '');

        return $unit ? $text.' '.$unit : $text;
    }

    /**
     * 84000 → "84 000". Four digits are left alone unless [$groupSmall] says
     * otherwise, because a bare 2015 is a year and "2 015" is not.
     */
    public static function number(float $value, bool $groupSmall = false): string
    {
        $whole = $value == floor($value);
        $text = $whole ? (string) (int) $value : number_format($value, 1, '.', '');

        if (! $whole || strlen($text) <= ($groupSmall ? 3 : 4)) {
            return $text;
        }

        return number_format((int) $value, 0, '.', ' ');
    }

    private static function plain(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value) && (float) $value == floor((float) $value)) {
            return (string) (int) $value;
        }

        return (string) $value;
    }
}
