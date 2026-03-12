<?php

namespace App\Console\Commands;

use App\DataTransfer\Horoscope\TransitAspectDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\KeyDatesBuilder;
use App\Services\Horoscope\Shared\LunationDetector;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the Key Dates view (4.5).
 *
 * Uses KeyDatesBuilder (same logic as WeeklyHoroscopeService / MonthlyHoroscopeService)
 * to detect transit-to-natal aspects and lunations within the requested period.
 */
class UiKeyDates extends Command
{
    private const W  = 72;
    private const IW = 68;

    // Personal natal planet bodies (Sun-Mars)
    private const PERSONAL_BODIES = [0, 1, 2, 3, 4];

    protected $signature = 'horoscope:ui-keydates
                            {profile : Profile ID}
                            {--view=month : week|month|year}
                            {--date=      : Any date in period (YYYY-MM-DD, default today)}';

    protected $description = 'Render key dates (transit aspects + lunations) in pseudo-browser console UI';

    public function handle(
        AspectCalculator $calculator,
        LunationDetector $lunationDetector,
        KeyDatesBuilder  $keyDatesBuilder,
    ): int {
        $profileId = (int) $this->argument('profile');
        $view      = $this->option('view') ?: 'month';
        $dateStr   = $this->option('date') ?: now()->toDateString();

        // ── Load profile + natal chart ────────────────────────────────────
        $profile = Profile::with('natalChart')->find($profileId);
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        $natalPlanets = $profile->natalChart?->planets ?? [];

        if (empty($natalPlanets)) {
            $this->error('Profile has no natal chart.');
            return self::FAILURE;
        }

        // ── Determine period boundaries ───────────────────────────────────
        $anchor = Carbon::parse($dateStr);

        switch ($view) {
            case 'week':
                $periodStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
                $periodEnd   = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
                break;
            case 'year':
                $periodStart = $anchor->copy()->startOfYear();
                $periodEnd   = $anchor->copy()->endOfYear();
                break;
            default: // month
                $periodStart = $anchor->copy()->startOfMonth();
                $periodEnd   = $anchor->copy()->endOfMonth();
                break;
        }

        // ── Render ────────────────────────────────────────────────────────
        $this->newLine();

        $periodLabel = match ($view) {
            'week'  => $periodStart->format('j M') . ' – ' . $periodEnd->format('j M Y'),
            'year'  => $periodStart->format('Y'),
            default => $periodStart->format('F Y'),
        };

        $this->put($this->top());
        $this->put($this->row($this->spread(
            '  📅  KEY DATES · ' . $periodLabel,
            ''
        )));
        $this->put($this->divider());

        if ($view === 'year') {
            $this->renderYear($periodStart, $calculator, $lunationDetector, $keyDatesBuilder, $natalPlanets);
        } elseif ($view === 'week') {
            $keyDates = $this->buildWeek($periodStart, $calculator, $lunationDetector, $keyDatesBuilder, $natalPlanets);
            if (empty($keyDates)) {
                $this->put($this->row(''));
                $this->put($this->row('  No significant key dates found for this period.'));
                $this->put($this->row(''));
            } else {
                $this->put($this->row(''));
                foreach ($keyDates as $kd) {
                    $this->put($this->row($this->buildDayLine($kd->date, $kd->label, $kd->priority)));
                    $this->renderKeyDateText($kd->textKey, $kd->section);
                }
                $this->put($this->row(''));
            }
        } else {
            // month
            $keyDates = $this->buildMonth($periodStart, $calculator, $lunationDetector, $keyDatesBuilder, $natalPlanets);
            if (empty($keyDates)) {
                $this->put($this->row(''));
                $this->put($this->row('  No significant key dates found for this period.'));
                $this->put($this->row(''));
            } else {
                $this->renderGroupedByIsoWeek($keyDates, $periodStart, $periodEnd);
            }
        }

        // ── Footer ────────────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row(
            '  key-dates · ' . $view . ' · '
            . $periodStart->toDateString() . ' – ' . $periodEnd->toDateString()
        ));
        $this->put($this->bottom());

        $this->newLine();

        return self::SUCCESS;
    }

    // ── Period builders ──────────────────────────────────────────────────

    /**
     * Week view: Monday–Sunday, best-orb per unique key, lunation max 1.
     *
     * @return \App\DataTransfer\Horoscope\KeyDateDTO[]
     */
    private function buildWeek(
        Carbon          $monday,
        AspectCalculator $calculator,
        LunationDetector $lunationDetector,
        KeyDatesBuilder  $keyDatesBuilder,
        array            $natalPlanets,
    ): array {
        $sunday = $monday->copy()->endOfWeek(Carbon::SUNDAY);

        $weekDates = [];
        for ($d = $monday->copy(); $d->lte($sunday); $d->addDay()) {
            $weekDates[] = $d->toDateString();
        }

        $weekPositions = $this->loadPositions($weekDates);

        if (empty($weekPositions)) {
            return [];
        }

        $bestByKey = [];
        foreach ($weekDates as $dayDate) {
            $dayPos = $weekPositions[$dayDate] ?? null;
            if (! $dayPos) {
                continue;
            }
            $dayTransit = $this->toTransitArray($dayPos);

            foreach ($calculator->transitToNatal($dayTransit, $natalPlanets) as $asp) {
                $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                    $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                }
            }
        }

        $transitNatalAspects = array_map(
            fn ($item) => new TransitAspectDTO(
                transitBody: $item['asp']['transit_body'],
                transitName: PlanetaryPosition::BODY_NAMES[$item['asp']['transit_body']] ?? '',
                natalBody:   $item['asp']['natal_body'],
                natalName:   PlanetaryPosition::BODY_NAMES[$item['asp']['natal_body']] ?? '',
                aspect:      $item['asp']['aspect'],
                orb:         $item['asp']['orb'],
                peakDate:    $item['date'],
            ),
            array_values($bestByKey),
        );

        $lunations = $lunationDetector->detect($weekPositions, 1);

        return $keyDatesBuilder->build($transitNatalAspects, $lunations);
    }

    /**
     * Month view: full calendar month, slow always / fast only if active >= 14 days.
     *
     * @return \App\DataTransfer\Horoscope\KeyDateDTO[]
     */
    private function buildMonth(
        Carbon          $monthStart,
        AspectCalculator $calculator,
        LunationDetector $lunationDetector,
        KeyDatesBuilder  $keyDatesBuilder,
        array            $natalPlanets,
    ): array {
        $monthEnd = $monthStart->copy()->endOfMonth();

        $monthDates = [];
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $monthDates[] = $d->toDateString();
        }

        $monthPositions = $this->loadPositions($monthDates);

        if (empty($monthPositions)) {
            return [];
        }

        $bestByKey  = [];
        $countByKey = [];

        foreach ($monthDates as $dayDate) {
            $dayPos = $monthPositions[$dayDate] ?? null;
            if (! $dayPos) {
                continue;
            }
            $dayTransit = $this->toTransitArray($dayPos);

            foreach ($calculator->transitToNatal($dayTransit, $natalPlanets) as $asp) {
                $isSlowTransit = $asp['transit_body'] >= 5;
                if (! $isSlowTransit && ! in_array($asp['natal_body'], self::PERSONAL_BODIES, true)) {
                    continue;
                }
                $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                $countByKey[$k] = ($countByKey[$k] ?? 0) + 1;
                if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                    $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                }
            }
        }

        // Filter: fast planets only if active >= 14 days
        $filtered = [];
        foreach ($bestByKey as $k => $item) {
            $isFast = $item['asp']['transit_body'] < 5;
            if ($isFast && ($countByKey[$k] ?? 0) < 14) {
                continue;
            }
            $item['days'] = $countByKey[$k] ?? 1;
            $filtered[$k] = $item;
        }

        $transitNatalAspects = array_map(
            fn ($item) => new TransitAspectDTO(
                transitBody: $item['asp']['transit_body'],
                transitName: PlanetaryPosition::BODY_NAMES[$item['asp']['transit_body']] ?? '',
                natalBody:   $item['asp']['natal_body'],
                natalName:   PlanetaryPosition::BODY_NAMES[$item['asp']['natal_body']] ?? '',
                aspect:      $item['asp']['aspect'],
                orb:         $item['asp']['orb'],
                peakDate:    $item['date'],
                activeDays:  $item['days'],
            ),
            array_values($filtered),
        );

        $lunations = $lunationDetector->detect($monthPositions, 0);

        return $keyDatesBuilder->build($transitNatalAspects, $lunations);
    }

    // ── Rendering ────────────────────────────────────────────────────────

    private function renderYear(
        Carbon          $yearStart,
        AspectCalculator $calculator,
        LunationDetector $lunationDetector,
        KeyDatesBuilder  $keyDatesBuilder,
        array            $natalPlanets,
    ): void {
        $anyFound = false;

        for ($m = 0; $m < 12; $m++) {
            $monthStart = $yearStart->copy()->addMonths($m)->startOfMonth();
            $keyDates   = $this->buildMonth($monthStart, $calculator, $lunationDetector, $keyDatesBuilder, $natalPlanets);

            if (empty($keyDates)) {
                continue;
            }

            $anyFound = true;
            $this->put($this->row(''));
            $this->put($this->row('  ' . $monthStart->format('F Y')));
            foreach ($keyDates as $kd) {
                $this->put($this->row($this->buildDayLine($kd->date, $kd->label, $kd->priority)));
                $this->renderKeyDateText($kd->textKey, $kd->section);
            }
        }

        if (! $anyFound) {
            $this->put($this->row(''));
            $this->put($this->row('  No significant key dates found for this period.'));
        }

        $this->put($this->row(''));
    }

    /**
     * Month view: group KeyDateDTOs by ISO week.
     *
     * @param \App\DataTransfer\Horoscope\KeyDateDTO[] $keyDates
     */
    private function renderGroupedByIsoWeek(array $keyDates, Carbon $periodStart, Carbon $periodEnd): void
    {
        $byWeek = [];
        foreach ($keyDates as $kd) {
            $weekNum          = Carbon::parse($kd->date)->isoWeek();
            $byWeek[$weekNum][] = $kd;
        }

        foreach ($byWeek as $weekNum => $weekKeyDates) {
            $firstDay       = Carbon::parse($weekKeyDates[0]->date)->startOfWeek(Carbon::MONDAY);
            $lastDay        = $firstDay->copy()->endOfWeek(Carbon::SUNDAY);
            $wStart         = $firstDay < $periodStart ? $periodStart : $firstDay;
            $wEnd           = $lastDay  > $periodEnd   ? $periodEnd   : $lastDay;
            $weekLabel      = 'Week ' . $weekNum . '  (' . $wStart->format('j M') . ' – ' . $wEnd->format('j M') . ')';

            $this->put($this->row(''));
            $this->put($this->row('  ' . $weekLabel));
            foreach ($weekKeyDates as $kd) {
                $this->put($this->row($this->buildDayLine($kd->date, $kd->label, $kd->priority)));
                $this->renderKeyDateText($kd->textKey, $kd->section);
            }
        }

        $this->put($this->row(''));
    }

    /**
     * Build a single key-date row line (without the │ border — added by row()).
     */
    private function buildDayLine(string $date, string $label, int $priority = 1): string
    {
        $bolt    = match ($priority) {
            0       => '⚡⚡⚡',
            1       => '⚡⚡ ',
            default => '⚡  ',
        };
        $dateStr = Carbon::parse($date)->format('D j M');
        $line    = '  ' . $bolt . '  ' . $dateStr . '  —  ' . $label;

        if (mb_strlen($line) > self::IW) {
            $line = mb_substr($line, 0, self::IW);
        }

        return $line;
    }

    private function renderKeyDateText(?string $textKey, string $section): void
    {
        if (! $textKey) {
            return;
        }
        $block = TextBlock::pick($textKey, $section, 1);
        if (! $block) {
            return;
        }
        $text = trim(strip_tags($block->text));
        // First sentence only
        $dot = mb_strpos($text, '.');
        $sentence = $dot !== false ? mb_substr($text, 0, $dot + 1) : mb_substr($text, 0, 120);
        foreach ($this->wrapText($sentence, self::IW - 6) as $line) {
            $this->put($this->row('      ' . $line));
        }
        $this->put($this->row(''));
    }

    private function wrapText(string $text, int $width): array
    {
        $words  = explode(' ', $text);
        $lines  = [];
        $current = '';
        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($test) > $width) {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        return $lines;
    }

    // ── Shared helpers ───────────────────────────────────────────────────

    /**
     * Load planetary positions for each date from DB.
     *
     * @param  string[]  $dates
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function loadPositions(array $dates): array
    {
        $positions = [];
        foreach ($dates as $date) {
            $pos = PlanetaryPosition::forDate($date)->orderBy('body')->get();
            if ($pos->isNotEmpty()) {
                $positions[$date] = $pos;
            }
        }
        return $positions;
    }

    /**
     * Convert a Collection<PlanetaryPosition> to the transit array format AspectCalculator expects.
     */
    private function toTransitArray(\Illuminate\Support\Collection $positions): array
    {
        return $positions->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();
    }

    // ── Box-drawing helpers ──────────────────────────────────────────────

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

    private function put(string $line): void
    {
        $this->line($line);
    }
}
