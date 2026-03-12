<?php

namespace App\Services\Horoscope\Shared;

use App\DataTransfer\Horoscope\ProgressedMoonDTO;
use App\Models\PlanetaryPosition;
use Carbon\Carbon;

class ProgressedMoonCalculator
{
    // Progressed Moon mean motion per year (secondary progressions)
    private const PROG_MOON_DEG_PER_YEAR = 13.1764;

    // Aspect angles for exact aspect detection
    private const ASPECT_ANGLES = [
        0   => 'conjunction',
        60  => 'sextile',
        90  => 'square',
        120 => 'trine',
        150 => 'quincunx',
        180 => 'opposition',
    ];

    private const SIGN_GLYPHS = [
        0 => "\u{2648}", 1 => "\u{2649}", 2 => "\u{264A}",  3 => "\u{264B}",
        4 => "\u{264C}", 5 => "\u{264D}", 6 => "\u{264E}",  7 => "\u{264F}",
        8 => "\u{2650}", 9 => "\u{2651}", 10 => "\u{2652}", 11 => "\u{2653}",
    ];

    private const BODY_GLYPHS = [
        0 => "\u{2609}", 1 => "\u{263D}",  2 => "\u{263F}", 3 => "\u{2640}",  4 => "\u{2642}",
        5 => "\u{2643}", 6 => "\u{2644}",  7 => "\u{2645}", 8 => "\u{2646}",  9 => "\u{2647}",
       10 => "\u{26B7}", 11 => "\u{260A}", 12 => "\u{26B8}",
    ];

    private const ASPECT_GLYPHS = [
        'conjunction'  => "\u{260C}", 'opposition'   => "\u{260D}",
        'trine'        => "\u{25B3}", 'square'       => "\u{25A1}",
        'sextile'      => "\u{26B9}", 'quincunx'     => "\u{26BB}",
        'semi_sextile' => "\u{2220}",
    ];

    /**
     * Calculate progressed Moon for the given month.
     * Shows only when: entering new sign, new house, or exact aspect (orb <= 1 deg).
     *
     * @param  Carbon  $birthDate
     * @param  float   $natalMoonLon
     * @param  Carbon  $monthStart
     * @param  array   $natalPlanets  [['body'=>int, 'longitude'=>float, ...], ...]
     * @param  float[] $houseCusps    float[12]
     * @return ProgressedMoonDTO|null  null if nothing notable
     */
    public function calculate(
        Carbon $birthDate,
        float  $natalMoonLon,
        Carbon $monthStart,
        array  $natalPlanets,
        array  $houseCusps,
    ): ?ProgressedMoonDTO {
        // Years elapsed from birth to this month vs. previous month
        $yearsNow  = $birthDate->diffInDays($monthStart) / 365.25;
        $yearsPrev = $birthDate->diffInDays($monthStart->copy()->subMonth()) / 365.25;

        $progLonNow  = fmod($natalMoonLon + $yearsNow  * self::PROG_MOON_DEG_PER_YEAR + 720, 360);
        $progLonPrev = fmod($natalMoonLon + $yearsPrev * self::PROG_MOON_DEG_PER_YEAR + 720, 360);

        $signNow  = (int) floor($progLonNow  / 30);
        $signPrev = (int) floor($progLonPrev / 30);

        $houseNow  = $this->findHouse($progLonNow,  $houseCusps);
        $housePrev = $this->findHouse($progLonPrev, $houseCusps);

        $signChange  = $signNow !== $signPrev;
        $houseChange = ! empty($houseCusps) && $houseNow !== $housePrev;

        // Exact aspects to natal planets (orb <= 1 deg)
        $exactAspects = [];
        foreach ($natalPlanets as $np) {
            $nLon = (float) ($np['longitude'] ?? 0);
            $diff = fmod(abs($progLonNow - $nLon) + 360, 360);
            if ($diff > 180) {
                $diff = 360 - $diff;
            }
            foreach (self::ASPECT_ANGLES as $angle => $aspName) {
                $orb = abs($diff - $angle);
                if ($orb <= 1.0) {
                    $nName  = PlanetaryPosition::BODY_NAMES[$np['body'] ?? -1] ?? '';
                    $nGlyph = self::BODY_GLYPHS[$np['body'] ?? -1] ?? '';
                    $aGlyph = self::ASPECT_GLYPHS[$aspName] ?? '';
                    $aLabel = ucfirst(str_replace('_', ' ', $aspName));
                    $exactAspects[] = "\u{25CB} Progressed Moon " . $aGlyph . ' ' . $aLabel
                                    . '  ' . $nGlyph . ' natal ' . $nName;
                }
            }
        }

        // Nothing notable -- skip the section
        if (! $signChange && ! $houseChange && empty($exactAspects)) {
            return null;
        }

        $signName  = PlanetaryPosition::SIGN_NAMES[$signNow] ?? '';
        $signGlyph = self::SIGN_GLYPHS[$signNow] ?? '';
        $deg       = number_format(fmod($progLonNow, 30), 1);
        $housePart = $houseNow ? '  H' . $houseNow : '';
        $line      = "\u{25CB} Progressed Moon in " . $signGlyph . ' ' . $signName . ' ' . $deg . "\u{00B0}" . $housePart;

        $notes = [];
        if ($signChange) {
            $prevSignName = PlanetaryPosition::SIGN_NAMES[$signPrev] ?? '';
            $notes[] = 'Entering ' . $signGlyph . ' ' . $signName . ' this month (was in ' . $prevSignName . ')';
        }
        if ($houseChange) {
            $notes[] = 'Moving into House ' . $houseNow . ' this month (was in House ' . $housePrev . ')';
        }
        foreach ($exactAspects as $ea) {
            $notes[] = $ea;
        }

        return new ProgressedMoonDTO(
            summaryLine: $line,
            notes:       $notes,
            signIndex:   $signNow,
            signName:    $signName,
            house:       $houseNow,
            longitude:   $progLonNow,
        );
    }

    /**
     * Find natal house number (1-indexed) for a given longitude.
     */
    private function findHouse(float $longitude, array $houseCusps): ?int
    {
        if (count($houseCusps) < 12) {
            return null;
        }

        $lon = fmod($longitude + 360, 360);

        for ($h = 0; $h < 12; $h++) {
            $cusp     = fmod($houseCusps[$h] + 360, 360);
            $nextCusp = fmod($houseCusps[($h + 1) % 12] + 360, 360);

            if ($cusp <= $nextCusp) {
                if ($lon >= $cusp && $lon < $nextCusp) {
                    return $h + 1;
                }
            } else {
                // Cusp straddles 0 deg (e.g. 355 deg -> 5 deg)
                if ($lon >= $cusp || $lon < $nextCusp) {
                    return $h + 1;
                }
            }
        }

        return 1;
    }
}
