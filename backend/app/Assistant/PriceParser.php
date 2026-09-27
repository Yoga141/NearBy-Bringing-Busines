<?php

namespace App\Assistant;

/**
 * Turns free-text price labels ("Rp25–75rb", "Mulai Rp30rb", "Rp7rb/kg",
 * "Rp15.000", "1,5jt") into rupiah numbers, so "yang murah" can actually sort.
 */
final class PriceParser
{
    private const MULTIPLIERS = [
        'rb' => 1_000, 'ribu' => 1_000, 'k' => 1_000,
        'jt' => 1_000_000, 'juta' => 1_000_000,
    ];

    /**
     * Every amount found in the text, in rupiah, in order of appearance.
     *
     * @return list<int>
     */
    public static function amounts(?string $label): array
    {
        if ($label === null || trim($label) === '') {
            return [];
        }

        $text = mb_strtolower($label);
        preg_match_all('/(\d+(?:[.,]\d+)*)\s*(rb|ribu|k|jt|juta)?(?![a-z])/u', $text, $m, PREG_SET_ORDER);
        if (! $m) {
            return [];
        }

        // In a range like "25–75rb" the unit is written once, after the last number.
        $lastUnit = null;
        foreach (array_reverse($m) as $match) {
            if (! empty($match[2])) {
                $lastUnit = $match[2];
                break;
            }
        }

        $amounts = [];
        foreach ($m as $match) {
            $number = self::number($match[1]);
            $unit = $match[2] ?? '';
            if ($unit === '' && $number < 1000) {
                // "Rp25-75rb" → 25 ribu; a bare small number is ribuan too.
                $unit = $lastUnit ?? 'rb';
            }
            $amounts[] = (int) round($number * (self::MULTIPLIERS[$unit] ?? 1));
        }

        return array_values(array_filter($amounts, fn ($a) => $a > 0));
    }

    /** "15.000" → 15000, "1,5" → 1.5, "1.5" → 1.5 */
    private static function number(string $raw): float
    {
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
            return (float) str_replace('.', '', $raw);
        }

        return (float) str_replace(',', '.', $raw);
    }
}
