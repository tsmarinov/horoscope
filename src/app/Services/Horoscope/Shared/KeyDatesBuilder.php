<?php

namespace App\Services\Horoscope\Shared;

use App\DataTransfer\Horoscope\KeyDateDTO;
use App\DataTransfer\Horoscope\LunationDTO;
use App\DataTransfer\Horoscope\TransitAspectDTO;

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
     *
     * Priority order (within the limit):
     *   0 — lunations (always first)
     *   1 — slow planet transits (Jupiter+, body >= 5)
     *   2 — fast planet transits (Sun, Mercury, Venus, Mars)
     *
     * Within each priority group, sorted by tightest orb.
     * Final output is sorted chronologically.
     *
     * @param  TransitAspectDTO[]  $transitAspects  with peakDate + orb set
     * @param  LunationDTO[]       $lunations
     * @param  int                 $limit           max number of key dates to return
     * @return KeyDateDTO[]
     */
    public function build(array $transitAspects, array $lunations): array
    {
        // ── Phase 1: collect candidates with priority ─────────────────────
        $candidates = [];

        foreach ($lunations as $lun) {
            $candidates[] = [
                'date'     => $lun->date,
                'label'    => $lun->name . ' in ' . $lun->signName,
                'priority' => 0,
                'orb'      => 0.0,
                'textKey'  => strtolower(str_replace(' ', '_', $lun->name)) . '_' . strtolower($lun->signName),
                'section'  => 'lunation',
            ];
        }

        foreach ($transitAspects as $asp) {
            if ($asp->peakDate === null || $asp->orb > 1.5) {
                continue;
            }
            if ($asp->transitBody === 1) {
                continue; // exclude Moon transits
            }
            if (in_array($asp->aspect, ['semi_sextile', 'quincunx'], true)) {
                continue;
            }

            $tGlyph = self::BODY_GLYPHS[$asp->transitBody] ?? '';
            $nGlyph = self::BODY_GLYPHS[$asp->natalBody] ?? '';
            $aGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? '';
            $aLabel = ucfirst(str_replace('_', ' ', $asp->aspect));

            $candidates[] = [
                'date'     => $asp->peakDate,
                'label'    => $tGlyph . ' ' . $asp->transitName
                            . ' ' . $aGlyph . ' ' . $aLabel
                            . ' ' . $nGlyph . ' natal ' . $asp->natalName,
                'priority' => $asp->transitBody >= 5 ? 1 : 2,
                'orb'      => $asp->orb,
                'textKey'  => 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName),
                'section'  => 'transit_natal',
            ];
        }

        if (empty($candidates)) {
            return [];
        }

        // ── Phase 2: group by date, keep best priority + orb per date ─────
        $byDate = [];
        foreach ($candidates as $c) {
            $date = $c['date'];
            if (! isset($byDate[$date])) {
                $byDate[$date] = ['priority' => $c['priority'], 'orb' => $c['orb'], 'labels' => [], 'textKey' => $c['textKey'], 'section' => $c['section']];
            } else {
                $isBetter = $c['priority'] < $byDate[$date]['priority']
                    || ($c['priority'] === $byDate[$date]['priority'] && $c['orb'] < $byDate[$date]['orb']);
                if ($isBetter) {
                    $byDate[$date]['priority'] = $c['priority'];
                    $byDate[$date]['orb']      = $c['orb'];
                    $byDate[$date]['textKey']  = $c['textKey'];
                    $byDate[$date]['section']  = $c['section'];
                }
            }
            $byDate[$date]['labels'][] = $c['label'];
        }

        // ── Phase 3: sort by priority asc, then orb asc ──────────────────
        uasort($byDate, fn ($a, $b) => $a['priority'] !== $b['priority']
            ? $a['priority'] <=> $b['priority']
            : $a['orb'] <=> $b['orb']);

        // ── Phase 4: sort chronologically ────────────────────────────────
        ksort($byDate);
        $selected = $byDate;

        // ── Phase 5: build DTOs ───────────────────────────────────────────
        $result = [];
        foreach ($selected as $date => $data) {
            $unique   = array_unique($data['labels']);
            $result[] = new KeyDateDTO(
                date:     $date,
                label:    implode('  ·  ', $unique),
                priority: $data['priority'],
                textKey:  $data['textKey'] ?? null,
                section:  $data['section'] ?? 'transit_natal',
            );
        }

        return $result;
    }
}
