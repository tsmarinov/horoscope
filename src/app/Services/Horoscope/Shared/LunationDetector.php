<?php

namespace App\Services\Horoscope\Shared;

use App\DataTransfer\Horoscope\LunationDTO;
use App\Models\PlanetaryPosition;

class LunationDetector
{
    /**
     * Detect lunations (New Moon and/or Full Moon) in a set of daily positions.
     *
     * @param  array  $dayPositions  ['YYYY-MM-DD' => Collection<PlanetaryPosition>]
     * @param  int    $maxPerType    max per type (1 = one NM + one FM, 0 = unlimited)
     * @return LunationDTO[]
     */
    public function detect(array $dayPositions, int $maxPerType = 1): array
    {
        $lunations = [];
        $prev      = null;
        $found     = []; // track 'new_moon' / 'full_moon' counts

        foreach ($dayPositions as $date => $positions) {
            $planets = $positions->keyBy('body');
            $sun     = $planets->get(PlanetaryPosition::SUN);
            $moon    = $planets->get(PlanetaryPosition::MOON);

            if (! $sun || ! $moon) {
                $prev = null;
                continue;
            }

            $elong = fmod(($moon->longitude - $sun->longitude + 360), 360);

            // New moon: elongation < 20 deg (or transition from >340 to <40)
            if (
                ($maxPerType === 0 || ($found['new_moon'] ?? 0) < $maxPerType)
                && ($elong < 20 || ($prev !== null && $prev > 340 && $elong < 40))
            ) {
                $signIdx = (int) floor($sun->longitude / 30);
                $found['new_moon'] = ($found['new_moon'] ?? 0) + 1;
                $lunations[] = new LunationDTO(
                    date:      $date,
                    type:      'new_moon',
                    name:      __('lunar.lunations.new_moon'),
                    signIndex: $signIdx,
                    signName:  PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                    longitude: $sun->longitude,
                );
            } elseif (
                ($maxPerType === 0 || ($found['full_moon'] ?? 0) < $maxPerType)
                && $elong >= 170 && $elong <= 190
            ) {
                $signIdx = (int) floor($moon->longitude / 30);
                $found['full_moon'] = ($found['full_moon'] ?? 0) + 1;
                $lunations[] = new LunationDTO(
                    date:      $date,
                    type:      'full_moon',
                    name:      __('lunar.lunations.full_moon'),
                    signIndex: $signIdx,
                    signName:  PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                    longitude: $moon->longitude,
                );
            }

            $prev = $elong;
        }

        return $lunations;
    }
}
