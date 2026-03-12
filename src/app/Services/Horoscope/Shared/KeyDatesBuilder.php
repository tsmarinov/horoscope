<?php

namespace App\Services\Horoscope\Shared;

use App\DataTransfer\Horoscope\KeyDateDTO;
use App\DataTransfer\Horoscope\LunationDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\Models\PlanetaryPosition;

class KeyDatesBuilder
{
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
     * Build key dates from selected transit aspects + lunations.
     * Shows peak day per aspect (min orb day), only if orb < 1.0 deg.
     * Excludes Moon transits (body=1) and semi_sextile/quincunx aspects.
     *
     * @param  TransitAspectDTO[]  $transitAspects  with peakDate set
     * @param  LunationDTO[]       $lunations
     * @return KeyDateDTO[]
     */
    public function build(array $transitAspects, array $lunations): array
    {
        $dates = [];

        // Lunations first
        foreach ($lunations as $lun) {
            $dates[$lun->date][] = $lun->name . ' in ' . $lun->signName;
        }

        // Peak day per aspect (only if orb < 1.0 deg -- truly tight)
        foreach ($transitAspects as $asp) {
            if ($asp->peakDate === null || $asp->orb > 1.0) {
                continue;
            }

            // Exclude Moon transits and minor aspects
            if ($asp->transitBody === 1) {
                continue;
            }
            if (in_array($asp->aspect, ['semi_sextile', 'quincunx'], true)) {
                continue;
            }

            $tGlyph = self::BODY_GLYPHS[$asp->transitBody] ?? '';
            $nGlyph = self::BODY_GLYPHS[$asp->natalBody] ?? '';
            $aGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? '';
            $aLabel = ucfirst(str_replace('_', ' ', $asp->aspect));
            $dates[$asp->peakDate][] = $tGlyph . ' ' . $asp->transitName
                                      . ' ' . $aGlyph . ' ' . $aLabel
                                      . ' ' . $nGlyph . ' natal ' . $asp->natalName;
        }

        if (empty($dates)) {
            return [];
        }

        ksort($dates);

        $result = [];
        foreach ($dates as $date => $labels) {
            $unique   = array_unique($labels);
            $result[] = new KeyDateDTO(
                date:  $date,
                label: implode('  ·  ', $unique),
            );
        }

        return $result;
    }
}
