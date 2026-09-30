<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Small display helpers shared by the international TADA pages.
 * SQL Server date columns arrive as strings, so everything goes through Carbon::parse().
 */
class Fmt
{
    /** Show 2 decimals only when needed (legacy formatNumber/fmtNum). */
    public static function num($number, string $nullText = '0'): string
    {
        if ($number === null || $number === '') {
            return $nullText;
        }
        $number = (float) $number;

        return floor($number) == $number ? number_format($number, 0) : number_format($number, 2);
    }

    /** Format a date-ish value; returns $blank when empty. */
    public static function date($value, string $format = 'Y-m-d', string $blank = ''): string
    {
        if ($value === null || $value === '') {
            return $blank;
        }

        return Carbon::parse($value)->format($format);
    }
}
