<?php

namespace App\Services;

use App\Models\PlanetaryPosition;
use Carbon\Carbon;

class SolarReturnCalculator
{
    /**
     * Find solar return JD for a given year.
     *
     * Algorithm:
     * 1. Start from the birth date in the target year (birth_month/birth_day/year) +/- 3 days window
     * 2. Query planetary_positions for Sun (body=0) for dates in that window
     * 3. Find the two consecutive dates where Sun crosses natal longitude (handle 0/360 wrap)
     * 4. Linear interpolation between the two days -> fractional JD
     * 5. Convert JD to Carbon datetime
     *
     * @param  float  $natalSunLon  natal Sun longitude in degrees [0,360)
     * @param  int    $birthMonth
     * @param  int    $birthDay
     * @param  int    $targetYear   the year to find solar return for
     * @return array{jd: float, datetime: \Carbon\Carbon, sunLon: float}
     */
    public function find(float $natalSunLon, int $birthMonth, int $birthDay, int $targetYear): array
    {
        // Build a window of +/- 3 days around the birthday in the target year
        $center = Carbon::createFromDate($targetYear, $birthMonth, min($birthDay, 28))->startOfDay();

        // If birth day is 29 Feb and target year is not a leap year, center on Feb 28
        if ($birthMonth === 2 && $birthDay === 29 && ! $center->isLeapYear()) {
            $center = Carbon::createFromDate($targetYear, 2, 28)->startOfDay();
        } elseif ($birthDay > 28) {
            // Re-create with the actual day (handles 29, 30, 31 properly)
            try {
                $center = Carbon::createFromDate($targetYear, $birthMonth, $birthDay)->startOfDay();
            } catch (\Exception) {
                // Keep the safe date
            }
        }

        $windowStart = $center->copy()->subDays(3)->toDateString();
        $windowEnd   = $center->copy()->addDays(3)->toDateString();

        // Query Sun positions in the window
        $sunPositions = PlanetaryPosition::where('body', PlanetaryPosition::SUN)
            ->whereBetween('date', [$windowStart, $windowEnd])
            ->orderBy('date')
            ->get();

        if ($sunPositions->count() < 2) {
            // Fallback: return approximate JD for the birthday itself
            return $this->fallback($center, $natalSunLon);
        }

        // Find consecutive pair where Sun crosses natal longitude
        $prev = null;
        foreach ($sunPositions as $pos) {
            if ($prev !== null) {
                $lon1 = $prev->longitude;
                $lon2 = $pos->longitude;

                if ($this->crossesLongitude($lon1, $lon2, $natalSunLon)) {
                    $fraction = $this->interpolateFraction($lon1, $lon2, $natalSunLon);

                    $date1  = Carbon::parse($prev->date)->startOfDay();
                    $date2  = Carbon::parse($pos->date)->startOfDay();
                    $dayGap = $date1->diffInDays($date2);

                    $srCarbon = $date1->copy()->addSeconds((int) round($fraction * $dayGap * 86400));
                    $srJd     = 2440587.5 + $srCarbon->timestamp / 86400.0;

                    // Interpolated Sun longitude
                    $srSunLon = $this->interpolateLon($lon1, $lon2, $fraction);

                    return [
                        'jd'       => $srJd,
                        'datetime' => $srCarbon->utc(),
                        'sunLon'   => $srSunLon,
                    ];
                }
            }
            $prev = $pos;
        }

        // If no crossing found (unlikely), return approximate
        return $this->fallback($center, $natalSunLon);
    }

    /**
     * Check if natal longitude lies between lon1 and lon2 (forward motion).
     */
    private function crossesLongitude(float $lon1, float $lon2, float $target): bool
    {
        // Normal case: Sun moves forward from lon1 to lon2
        if ($lon2 >= $lon1) {
            return $target >= $lon1 && $target <= $lon2;
        }

        // Wrap around 360/0 boundary
        return $target >= $lon1 || $target <= $lon2;
    }

    /**
     * Compute interpolation fraction for the target longitude between two positions.
     * Handles 360/0 wrap.
     */
    private function interpolateFraction(float $lon1, float $lon2, float $target): float
    {
        // Unwrap to make the arc monotonically increasing
        $unwrappedLon2   = $lon2 < $lon1 ? $lon2 + 360 : $lon2;
        $unwrappedTarget = $target < $lon1 ? $target + 360 : $target;

        $range = $unwrappedLon2 - $lon1;
        if (abs($range) < 0.0001) {
            return 0.0;
        }

        return ($unwrappedTarget - $lon1) / $range;
    }

    /**
     * Interpolate longitude between two values at a given fraction.
     */
    private function interpolateLon(float $lon1, float $lon2, float $fraction): float
    {
        $unwrappedLon2 = $lon2 < $lon1 ? $lon2 + 360 : $lon2;
        $result = $lon1 + $fraction * ($unwrappedLon2 - $lon1);

        return fmod($result + 360, 360);
    }

    /**
     * Fallback when no crossing is found: use the center date as approximate.
     */
    private function fallback(Carbon $center, float $natalSunLon): array
    {
        $jd = 2440587.5 + $center->timestamp / 86400.0;

        return [
            'jd'       => $jd,
            'datetime' => $center->copy()->utc(),
            'sunLon'   => $natalSunLon,
        ];
    }
}
