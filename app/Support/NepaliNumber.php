<?php

namespace App\Support;

/**
 * Converts Latin digits (0-9) to Nepali/Devanagari digits (०-९) for printed documents.
 */
class NepaliNumber
{
    private const MAP = [
        '0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४',
        '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९',
    ];

    public static function digits(string|int|float|null $value): string
    {
        return strtr((string) $value, self::MAP);
    }

    /**
     * Rupee amount with Nepali / Indian digit grouping: 12345678.5 -> "1,23,45,678.50".
     * Whole amounts get no decimals unless $alwaysDecimals is true.
     */
    public static function money(float|int|null $amount, bool $alwaysDecimals = false): string
    {
        $amount = round((float) $amount, 2);
        $negative = $amount < 0;
        [$whole, $fraction] = explode('.', number_format(abs($amount), 2, '.', ''));

        $last3 = substr($whole, -3);
        $rest = substr($whole, 0, -3);
        $grouped = $rest === '' ? $last3 : preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last3;

        $out = ($negative ? '-' : '').$grouped;

        return ($alwaysDecimals || $fraction !== '00') ? $out.'.'.$fraction : $out;
    }
}
