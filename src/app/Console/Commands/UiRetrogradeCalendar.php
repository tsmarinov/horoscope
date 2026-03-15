<?php

namespace App\Console\Commands;

use App\Services\RetrogradeCalendarService;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the retrograde calendar.
 *
 * Renders a full-year view of planetary retrograde periods as a
 * 72-char wide ASCII UI block in the console.
 *
 * Layout:
 *   ┌─ header (title + subtitle) ────────────────────────────────────┐
 *   │ year navigation  ← 2025  ♻ 2026  2027 →                       │
 *   ├─ ACTIVE NOW (if any) ───────────────────────────────────────────┤
 *   ├─ INNER PLANETS ─────────────────────────────────────────────────┤
 *   │ Mercury, Venus, Mars — periods, timeline bars, descriptions     │
 *   ├─ OUTER PLANETS ─────────────────────────────────────────────────┤
 *   │ Jupiter … Pluto — same layout                                   │
 *   ├─ legend ────────────────────────────────────────────────────────┤
 *   └─────────────────────────────────────────────────────────────────┘
 */
class UiRetrogradeCalendar extends Command
{
    protected $signature = 'horoscope:ui-retrograde-calendar
                            {--year= : Year to display (default: current year)}';

    protected $description = 'Render the retrograde calendar in pseudo-browser console UI';

    // ── Layout constants ──────────────────────────────────────────────
    private const W  = 72;
    private const IW = 68;

    // ── Timeline bar width (characters) ──────────────────────────────
    private const BAR_W = 52;

    // ── Entry point ───────────────────────────────────────────────────

    public function handle(RetrogradeCalendarService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $data = $service->getCalendar($year);

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread(
            '  ♻ ' . ui_trans('retrograde.title'),
            '[' . $year . ']  '
        )));
        $this->put($this->row('  ' . ui_trans('retrograde.subtitle')));
        $this->put($this->divider());

        // ── Year navigation ──────────────────────────────────────────
        $nav = '  ← ' . ($year - 1) . '   ♻ ' . $year . '   ' . ($year + 1) . ' →';
        $this->put($this->row($nav));
        $this->put($this->row(''));

        // ── ACTIVE NOW ───────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . ui_trans('retrograde.active_now')));
        $this->put($this->row(''));

        if (empty($data['active'])) {
            $this->put($this->row('  ' . ui_trans('retrograde.active_none')));
        } else {
            foreach ($data['active'] as $item) {
                $rxEndFmt = $this->fmtDate($item['rx_end']);
                $line     = '  ' . $item['glyph'] . ' ' . $item['name']
                          . ' Rx  in ' . $item['sign']
                          . '  —  ends ' . $rxEndFmt;
                $this->put($this->row($line));
            }
        }
        $this->put($this->row(''));

        // ── INNER PLANETS ────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . ui_trans('retrograde.section_inner')));
        $this->put($this->row(''));

        foreach ($data['inner'] as $pd) {
            $this->renderPlanet($pd, $year);
        }

        // ── OUTER PLANETS ────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . ui_trans('retrograde.section_outer')));
        $this->put($this->row(''));

        foreach ($data['outer'] as $pd) {
            $this->renderPlanet($pd, $year);
        }

        // ── Legend / footer ──────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row(
            '  ' . ui_trans('retrograde.legend_today')
            . '   ' . ui_trans('retrograde.legend_rx')
            . '   ' . ui_trans('retrograde.legend_station')
        ));
        $this->put($this->row(''));
        foreach ($this->wrap(ui_trans('retrograde.station_note'), self::IW - 2) as $line) {
            $this->put($this->row('  ' . $line));
        }
        $this->put($this->row(''));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Planet block renderer ─────────────────────────────────────────

    private function renderPlanet(array $pd, int $year): void
    {
        $name  = $pd['name'];
        $key   = strtolower($name);

        // Planet header: glyph + name + theme + period count
        if ($pd['has_rx']) {
            $countLabel = $pd['period_count'] . 'x Rx';
        } else {
            $countLabel = ui_trans('retrograde.no_rx', null, null, ['year' => $year]);
        }
        $theme = ui_trans('retrograde.planet_theme.' . $key) ?? '';

        $this->put($this->row($this->spread(
            '  ' . $pd['glyph'] . ' ' . $name . ($theme ? '  ·  ' . $theme : ''),
            $countLabel . '  '
        )));

        // Periods
        if ($pd['has_rx']) {
            foreach ($pd['periods'] as $period) {
                $this->put($this->row(''));
                $this->renderPeriod($period, $pd, $year);
            }
        }

        // Planet description
        $desc = ui_trans('retrograde.planet_desc.' . $key) ?? '';
        if ($desc !== '') {
            $this->put($this->row(''));
            foreach ($this->wrap($desc, self::IW - 4) as $line) {
                $this->put($this->row('    ' . $line));
            }
        }

        $this->put($this->row(''));
    }

    private function renderPeriod(array $period, array $pd, int $year): void
    {
        $startFmt = $this->fmtDate($period['rx_start']);
        $endFmt   = $this->fmtDate($period['rx_end']);
        $srFmt    = $this->fmtDate($period['sr_date']);
        $sdFmt    = $this->fmtDate($period['sd_date']);

        $activeBadge = $period['is_active'] ? '  [ACTIVE]' : '';
        $extBadge    = $period['extends_next_year'] ? '  [→' . ($year + 1) . ']' : '';

        // Date range line
        $dateLine = '  · ' . $startFmt . ' – ' . $endFmt
                  . '  ' . $period['sign']
                  . $activeBadge . $extBadge;
        $this->put($this->row($dateLine));

        // SR / SD line
        $stationLine = '    ' . ui_trans('retrograde.sr') . ' ' . $srFmt
                     . '   ' . ui_trans('retrograde.sd') . ' ' . $sdFmt;
        $this->put($this->row($stationLine));

        // ASCII timeline bar
        $bar = $this->buildTimelineBar($period['rx_start'], $period['rx_end'], $year);
        $this->put($this->row('    ' . $bar));
    }

    // ── Timeline bar ─────────────────────────────────────────────────

    /**
     * Build a BAR_W-char timeline bar for Jan 1 – Dec 31.
     *
     *   ░ = background day
     *   ▓ = retrograde day
     *   │ = today marker (overrides ▓ or ░)
     */
    private function buildTimelineBar(string $rxStart, string $rxEnd, int $year): string
    {
        $yearStart = $year . '-01-01';
        $yearEnd   = $year . '-12-31';
        $daysInYear = (int) date('z', strtotime($yearEnd)) + 1; // 365 or 366

        $today = now()->toDateString();

        // Convert dates to day-of-year (1-based)
        $rxStartDay = $this->dayOfYear($rxStart, $year, $daysInYear);
        $rxEndDay   = $this->dayOfYear($rxEnd,   $year, $daysInYear);
        $todayDay   = (substr($today, 0, 4) == (string) $year)
            ? (int) date('z', strtotime($today)) + 1
            : null;

        $bar = '';
        for ($col = 0; $col < self::BAR_W; $col++) {
            // Map column to day-of-year (1-based)
            $day = (int) round(($col + 0.5) / self::BAR_W * $daysInYear);

            if ($todayDay !== null && $day === $todayDay) {
                $bar .= '│';
            } elseif ($day >= $rxStartDay && $day <= $rxEndDay) {
                $bar .= '▓';
            } else {
                $bar .= '░';
            }
        }

        return $bar;
    }

    /**
     * Convert a date string to a day-of-year position, clamped to [1, $daysInYear].
     */
    private function dayOfYear(string $date, int $year, int $daysInYear): int
    {
        $yearStart = $year . '-01-01';
        if ($date < $yearStart) {
            return 1;
        }
        if ($date > $year . '-12-31') {
            return $daysInYear;
        }
        return (int) date('z', strtotime($date)) + 1;
    }

    // ── Date formatting ───────────────────────────────────────────────

    /** Format Y-m-d as "Mar 2" */
    private function fmtDate(string $date): string
    {
        return date('M j', strtotime($date));
    }

    // ── Box-drawing helpers ───────────────────────────────────────────

    private function top(): string
    {
        return '┌' . str_repeat('─', self::W - 2) . '┐';
    }

    private function bottom(): string
    {
        return '└' . str_repeat('─', self::W - 2) . '┘';
    }

    private function divider(): string
    {
        return '├' . str_repeat('─', self::W - 2) . '┤';
    }

    private function row(string $content): string
    {
        return '│ ' . $this->mbPad($content, self::IW) . ' │';
    }

    private function spread(string $left, string $right, int $width = self::IW): string
    {
        $gap = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return $left . str_repeat(' ', $gap) . $right;
    }

    private function mbPad(string $str, int $width): string
    {
        $len = mb_strlen($str);
        if ($len >= $width) {
            return mb_substr($str, 0, $width);
        }
        return $str . str_repeat(' ', $width - $len);
    }

    private function wrap(string $text, int $width): array
    {
        $text  = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        $lines = [];
        while (mb_strlen($text) > $width) {
            $pos = mb_strrpos(mb_substr($text, 0, $width), ' ');
            if ($pos === false) {
                $pos = $width;
            }
            $lines[] = mb_substr($text, 0, $pos);
            $text    = ltrim(mb_substr($text, $pos));
        }
        if ($text !== '') {
            $lines[] = $text;
        }
        return $lines ?: [''];
    }

    private function put(string $line): void
    {
        $this->line($line);
    }
}
