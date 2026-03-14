<?php

namespace App\Services;

use App\Models\PlanetaryPosition;
use Illuminate\Support\Facades\DB;

/**
 * Builds the retrograde calendar data for a given year.
 *
 * No UI output, no app() calls, no emojis.
 * All user-facing strings are handled by the caller via __('ui.retrograde.*').
 */
class RetrogradeCalendarService
{
    private const PLANETS = [
        PlanetaryPosition::MERCURY => ['name' => 'Mercury', 'glyph' => '☿', 'color' => '#5a90b0'],
        PlanetaryPosition::VENUS   => ['name' => 'Venus',   'glyph' => '♀', 'color' => '#b87088'],
        PlanetaryPosition::MARS    => ['name' => 'Mars',    'glyph' => '♂', 'color' => '#b85040'],
        PlanetaryPosition::JUPITER => ['name' => 'Jupiter', 'glyph' => '♃', 'color' => '#4870b8'],
        PlanetaryPosition::SATURN  => ['name' => 'Saturn',  'glyph' => '♄', 'color' => '#7060a0'],
        PlanetaryPosition::URANUS  => ['name' => 'Uranus',  'glyph' => '⛢', 'color' => '#38a0a8'],
        PlanetaryPosition::NEPTUNE => ['name' => 'Neptune', 'glyph' => '♆', 'color' => '#3858b0'],
        PlanetaryPosition::PLUTO   => ['name' => 'Pluto',   'glyph' => '♇', 'color' => '#8850a0'],
    ];

    private const INNER_BODIES = [
        PlanetaryPosition::MERCURY,
        PlanetaryPosition::VENUS,
        PlanetaryPosition::MARS,
    ];

    private const OUTER_BODIES = [
        PlanetaryPosition::JUPITER,
        PlanetaryPosition::SATURN,
        PlanetaryPosition::URANUS,
        PlanetaryPosition::NEPTUNE,
        PlanetaryPosition::PLUTO,
    ];

    public function __construct() {}

    /**
     * Build the full retrograde calendar for a year.
     *
     * @param  int  $year
     * @return array
     */
    public function getCalendar(int $year): array
    {
        $today     = now()->toDateString();
        $yearStart = $year . '-01-01';
        $yearEnd   = $year . '-12-31';

        // Load rows for the year plus 10-day buffer on each side for SR/SD detection
        $bufferStart = date('Y-m-d', strtotime($yearStart . ' -10 days'));
        $bufferEnd   = date('Y-m-d', strtotime($yearEnd   . ' +10 days'));

        $bodies = array_merge(self::INNER_BODIES, self::OUTER_BODIES);

        $rows = DB::table('planetary_positions')
            ->whereIn('body', $bodies)
            ->whereBetween('date', [$bufferStart, $bufferEnd])
            ->orderBy('body')
            ->orderBy('date')
            ->select(['date', 'body', 'longitude', 'speed', 'is_retrograde'])
            ->get();

        // Group rows by body
        $byBody = [];
        foreach ($rows as $row) {
            $byBody[(int) $row->body][] = $row;
        }

        $planetData = [];
        foreach ($bodies as $body) {
            $planetData[$body] = $this->buildPlanetData(
                $body,
                $byBody[$body] ?? [],
                $year,
                $yearStart,
                $yearEnd,
                $today
            );
        }

        // Split into inner/outer
        $inner = [];
        foreach (self::INNER_BODIES as $body) {
            $inner[] = $planetData[$body];
        }
        $outer = [];
        foreach (self::OUTER_BODIES as $body) {
            $outer[] = $planetData[$body];
        }

        // Active Rx periods (today falls within rx_start..rx_end)
        $active = [];
        foreach ($planetData as $pd) {
            foreach ($pd['periods'] as $period) {
                if ($period['is_active']) {
                    $active[] = [
                        'body'    => $pd['body'],
                        'name'    => $pd['name'],
                        'glyph'   => $pd['glyph'],
                        'sign'    => $period['sign'],
                        'rx_end'  => $period['rx_end'],
                    ];
                    break; // only one active period per planet possible
                }
            }
        }

        return [
            'year'   => $year,
            'today'  => $today,
            'active' => $active,
            'inner'  => $inner,
            'outer'  => $outer,
        ];
    }

    /**
     * Build planet data array including all Rx periods for the year.
     */
    private function buildPlanetData(
        int    $body,
        array  $rows,
        int    $year,
        string $yearStart,
        string $yearEnd,
        string $today
    ): array {
        $meta = self::PLANETS[$body];

        if (empty($rows)) {
            return [
                'body'         => $body,
                'name'         => $meta['name'],
                'glyph'        => $meta['glyph'],
                'color'        => $meta['color'],
                'has_rx'       => false,
                'periods'      => [],
                'period_count' => 0,
            ];
        }

        // Index rows by date for quick lookup
        $rowByDate = [];
        foreach ($rows as $row) {
            $rowByDate[$row->date] = $row;
        }

        // Find consecutive is_retrograde=true sequences
        $periods = [];
        $inRx    = false;
        $rxStart = null;
        $rxRows  = [];

        foreach ($rows as $row) {
            if ($row->is_retrograde) {
                if (! $inRx) {
                    $inRx    = true;
                    $rxStart = $row->date;
                    $rxRows  = [];
                }
                $rxRows[] = $row;
            } else {
                if ($inRx) {
                    // End of Rx sequence
                    $rxEnd = end($rxRows)->date;
                    $inRx  = false;

                    // Only include if the period overlaps with the target year
                    if ($rxEnd >= $yearStart && $rxStart <= $yearEnd) {
                        $periods[] = $this->buildPeriod(
                            $rxStart,
                            $rxEnd,
                            $rxRows,
                            $rowByDate,
                            $yearEnd,
                            $today
                        );
                    }

                    $rxStart = null;
                    $rxRows  = [];
                }
            }
        }

        // Handle period still open at end of buffer
        if ($inRx && ! empty($rxRows)) {
            $rxEnd = end($rxRows)->date;
            if ($rxEnd >= $yearStart && $rxStart <= $yearEnd) {
                $periods[] = $this->buildPeriod(
                    $rxStart,
                    $rxEnd,
                    $rxRows,
                    $rowByDate,
                    $yearEnd,
                    $today
                );
            }
        }

        return [
            'body'         => $body,
            'name'         => $meta['name'],
            'glyph'        => $meta['glyph'],
            'color'        => $meta['color'],
            'has_rx'       => count($periods) > 0,
            'periods'      => $periods,
            'period_count' => count($periods),
        ];
    }

    /**
     * Build a single retrograde period descriptor.
     *
     * @param string $rxStart   First is_retrograde=true date
     * @param string $rxEnd     Last is_retrograde=true date
     * @param array  $rxRows    All rows within the Rx sequence
     * @param array  $rowByDate All rows indexed by date (covers buffer range)
     * @param string $yearEnd   Dec 31 of target year
     * @param string $today     Today's date string
     */
    private function buildPeriod(
        string $rxStart,
        string $rxEnd,
        array  $rxRows,
        array  $rowByDate,
        string $yearEnd,
        string $today
    ): array {
        // Sign at rx_start (longitude / 30)
        $startRow  = $rowByDate[$rxStart] ?? $rxRows[0];
        $signIdx   = (int) floor((float) $startRow->longitude / 30) % 12;
        $sign      = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? 'Unknown';

        // SR date: among rows in the 10 days BEFORE rx_start (is_retrograde=false),
        // find the one with minimum |speed|.
        $srDate    = $rxStart;
        $minSpeed  = PHP_FLOAT_MAX;
        for ($i = 1; $i <= 10; $i++) {
            $d = date('Y-m-d', strtotime($rxStart . " -{$i} days"));
            if (isset($rowByDate[$d]) && ! $rowByDate[$d]->is_retrograde) {
                $abs = abs((float) $rowByDate[$d]->speed);
                if ($abs < $minSpeed) {
                    $minSpeed = $abs;
                    $srDate   = $d;
                }
            }
        }

        // SD date: among rows in the 10 days AFTER rx_end (is_retrograde=false),
        // find the one with minimum |speed|.
        $sdDate   = $rxEnd;
        $minSpeed = PHP_FLOAT_MAX;
        for ($i = 1; $i <= 10; $i++) {
            $d = date('Y-m-d', strtotime($rxEnd . " +{$i} days"));
            if (isset($rowByDate[$d]) && ! $rowByDate[$d]->is_retrograde) {
                $abs = abs((float) $rowByDate[$d]->speed);
                if ($abs < $minSpeed) {
                    $minSpeed = $abs;
                    $sdDate   = $d;
                }
            }
        }

        $isActive      = $today >= $rxStart && $today <= $rxEnd;
        $extendsNext   = $rxEnd > $yearEnd;

        return [
            'rx_start'          => $rxStart,
            'rx_end'            => $rxEnd,
            'sr_date'           => $srDate,
            'sd_date'           => $sdDate,
            'sign'              => $sign,
            'is_active'         => $isActive,
            'extends_next_year' => $extendsNext,
        ];
    }
}
