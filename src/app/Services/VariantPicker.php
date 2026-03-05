<?php

namespace App\Services;

/**
 * Deterministic variant selector.
 *
 * Given a stable seed (subject identifier + date), always returns the same
 * variant number for a block key. Different subjects get different variants
 * for the same date and key.
 *
 * Variant numbers are 1-based (1…$totalVariants).
 */
class VariantPicker
{
    /**
     * Pick a variant number for the given combination.
     *
     * @param  string|int $subjectId  Unique subject identifier (user ID, guest UUID, etc.)
     * @param  string     $date       Date string e.g. "2026-03-04"
     * @param  string     $blockKey   Block key e.g. "sun_trine_moon"
     * @param  int        $totalVariants  Total number of available variants (e.g. 3 or 8)
     * @return int  Variant number between 1 and $totalVariants (inclusive)
     */
    public function pick(string|int $subjectId, string $date, string $blockKey, int $totalVariants): int
    {
        $seed = crc32("{$subjectId}:{$date}:{$blockKey}");

        // crc32 can return negative values on 32-bit systems — abs() ensures positive
        return (abs($seed) % $totalVariants) + 1;
    }
}
