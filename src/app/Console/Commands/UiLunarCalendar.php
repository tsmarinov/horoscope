<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\HouseCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the lunar calendar — no actual browser needed.
 *
 * Renders what the user would see on the lunar calendar page, as a
 * 72-char wide ASCII UI block in the console. Intended for development
 * and content layout testing without a running web server.
 *
 * Layout:
 *   ┌─ header (month) ───────────────────────────────────────────────────┐
 *   │ month navigation (← prev | Month YYYY | next →)                   │
 *   │ calendar grid (7-col, 2 rows per week: day numbers + phase/sign)   │
 *   │ legend (new moon / full moon / VoC)                                │
 *   │ personalized lunation card (if profile supplied)                   │
 *   │ day-by-day list (phase + sign + lunar day + placeholder text)      │
 *   │ upcoming lunations                                                 │
 *   │ bottom links                                                       │
 *   └─ footer ────────────────────────────────────────────────────────────┘
 */
class UiLunarCalendar extends Command
{
    protected $signature = 'horoscope:ui-lunar-calendar
                            {profile? : Profile ID (optional, for personalized lunation card)}
                            {--month= : Month to show (YYYY-MM, default: current month)}';

    protected $description = 'Render a lunar calendar in pseudo-browser console UI';

    // ── Layout constants ──────────────────────────────────────────────────
    private const W  = 72;
    private const IW = 68;

    // ── Phase chars (single-width for calendar grid rows) ─────────────────
    // Row-1 markers: day number row
    private const PHASE_MARKERS = [
        'new'      => 'N',
        'full'     => 'F',
        'default'  => ' ',
    ];

    // ── Moon phases (elongation ranges) ──────────────────────────────────
    private const MOON_PHASES = [
        [0,     22.5,  '🌑', 'New Moon'],
        [22.5,  67.5,  '🌒', 'Waxing Crescent'],
        [67.5,  112.5, '🌓', 'First Quarter'],
        [112.5, 157.5, '🌔', 'Waxing Gibbous'],
        [157.5, 202.5, '🌕', 'Full Moon'],
        [202.5, 247.5, '🌖', 'Waning Gibbous'],
        [247.5, 292.5, '🌗', 'Last Quarter'],
        [292.5, 360,   '🌘', 'Waning Crescent'],
    ];

    // ── Sign abbreviations (3 chars, for calendar grid) ───────────────────
    private const SIGN_SHORT = [
        0 => 'Ari', 1 => 'Tau', 2 => 'Gem', 3 => 'Can',
        4 => 'Leo', 5 => 'Vir', 6 => 'Lib', 7 => 'Sco',
        8 => 'Sag', 9 => 'Cap', 10 => 'Aqu', 11 => 'Pis',
    ];

    private const SIGN_GLYPHS = [
        0 => '♈', 1 => '♉', 2 => '♊',  3 => '♋',
        4 => '♌', 5 => '♍', 6 => '♎',  7 => '♏',
        8 => '♐', 9 => '♑', 10 => '♒', 11 => '♓',
    ];

    private const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    // ── Entry point ───────────────────────────────────────────────────────

    public function handle(): int
    {
        $monthStr = $this->option('month') ?: now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            $this->error("Invalid month format. Use YYYY-MM (e.g. 2026-03).");
            return self::FAILURE;
        }

        $start = Carbon::parse($monthStr . '-01')->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $today = now()->toDateString();

        $profileId = $this->argument('profile');
        $profile   = $profileId ? Profile::find($profileId) : null;

        // ── Load Sun + Moon positions for the whole month ─────────────────
        $allPositions = PlanetaryPosition::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])
            ->whereIn('body', [PlanetaryPosition::SUN, PlanetaryPosition::MOON])
            ->orderBy('date')
            ->orderBy('body')
            ->get()
            ->groupBy('date');

        if ($allPositions->isEmpty()) {
            $this->error("No planetary positions found for {$monthStr}.");
            return self::FAILURE;
        }

        // ── Build per-day data ────────────────────────────────────────────
        $days = [];
        for ($d = 1; $d <= $end->day; $d++) {
            $dateStr = $start->format('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayPos  = $allPositions->get($dateStr, collect());
            $moon    = $dayPos->firstWhere('body', PlanetaryPosition::MOON);
            $sun     = $dayPos->firstWhere('body', PlanetaryPosition::SUN);

            $moonLon    = $moon?->longitude ?? 0.0;
            $sunLon     = $sun?->longitude ?? 0.0;
            $elongation = fmod($moonLon - $sunLon + 360, 360);

            [$phaseEmoji, $phaseName] = $this->moonPhase($elongation);

            $signIdx  = (int) floor($moonLon / 30);
            $lunarDay = max(1, (int) ceil($elongation / (360 / 29.53)));

            // Raw distance to exact lunation — resolved to nearest-day winner below
            $distNew  = min($elongation, 360 - $elongation); // distance to 0°
            $distFull = abs($elongation - 180);              // distance to 180°

            $days[$d] = [
                'date'        => $dateStr,
                'carbon'      => Carbon::parse($dateStr),
                'moon_lon'    => $moonLon,
                'elongation'  => $elongation,
                'phase'       => $phaseEmoji,
                'phase_name'  => $phaseName,
                'sign_idx'    => $signIdx,
                'sign_short'  => self::SIGN_SHORT[$signIdx] ?? '???',
                'lunar_day'   => $lunarDay,
                'new_moon'    => false, // resolved below
                'full_moon'   => false, // resolved below
                'dist_new'    => $distNew,
                'dist_full'   => $distFull,
                'is_today'    => $dateStr === $today,
            ];
        }

        // Resolve lunation days: among all candidates, pick the single day
        // closest to exact 0° (new moon) and 180° (full moon) per cycle.
        $this->resolveLunations($days);

        $prevMonth     = $start->copy()->subMonth();
        $nextMonth     = $start->copy()->addMonth();
        $firstWeekday  = $start->dayOfWeekIso; // 1=Mon … 7=Sun
        $daysInMonth   = $end->day;

        // Find lunation days
        $lunations = array_filter($days, fn ($d) => $d['new_moon'] || $d['full_moon']);

        $this->newLine();

        // ── Header ─────────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ LUNAR CALENDAR', '[' . $monthStr . ']  ')));
        $this->put($this->row($this->center($start->format('F Y'))));
        $this->put($this->divider());

        // ── Month navigation ───────────────────────────────────────────────
        $prevLabel = '  ◄ ' . $prevMonth->format('F Y');
        $nextLabel = $nextMonth->format('F Y') . ' ►  ';
        $this->put($this->row($this->spread($prevLabel, $nextLabel)));
        $this->put($this->divider());

        // ── Calendar grid ──────────────────────────────────────────────────
        $this->put($this->row(''));
        $this->renderCalendarGrid($days, $firstWeekday, $daysInMonth);
        $this->put($this->row(''));

        // ── Legend ─────────────────────────────────────────────────────────
        $this->put($this->row('  N = New Moon   F = Full Moon   * = Today   VoC = Void-of-Course'));
        $this->put($this->row('  (Moon emoji + sign shown in phase row; grid markers in day-number row)'));
        $this->put($this->row(''));
        $this->put($this->divider());

        // ── Personalized lunation card (logged-in with natal chart) ────────
        if ($profile !== null) {
            if ($profile->natalChart && is_array($profile->natalChart->houses)) {
                $houseCalculator = new HouseCalculator();
                $cusps           = $profile->natalChart->houses;
                $natalPlanets    = $profile->natalChart->planets ?? [];
                $ordinals        = [
                    1=>'1st',2=>'2nd',3=>'3rd',4=>'4th',5=>'5th',6=>'6th',
                    7=>'7th',8=>'8th',9=>'9th',10=>'10th',11=>'11th',12=>'12th',
                ];

                foreach ($lunations as $lun) {
                    $type     = $lun['new_moon'] ? 'New Moon' : 'Full Moon';
                    $icon     = $lun['new_moon'] ? '🌑' : '🌕';
                    $lunKey   = $lun['new_moon'] ? 'new_moon' : 'full_moon';
                    $signName = PlanetaryPosition::SIGN_NAMES[$lun['sign_idx']] ?? '';
                    $c        = $lun['carbon'];

                    // Which house does the lunation fall in?
                    [$house]  = $houseCalculator->assignHouses($cusps, [$lun['moon_lon']]);
                    $houseOrd = $ordinals[$house] ?? $house;
                    $textKey  = $lunKey . '_house_' . $house;
                    $block    = TextBlock::pick($textKey, 'lunation_house', 1);
                    $text     = $block ? trim(strip_tags($block->text)) : null;

                    // Natal planet conjunctions (≤ 5°)
                    $conjunctions = [];
                    foreach ($natalPlanets as $np) {
                        $diff = abs($lun['moon_lon'] - $np['longitude']);
                        if ($diff > 180) $diff = 360 - $diff;
                        if ($diff <= 5.0) {
                            $pName          = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '?';
                            $conjunctions[] = '☌ natal ' . $pName . ' ' . number_format($diff, 1) . '°';
                        }
                    }

                    $this->put($this->row(''));
                    $this->put($this->row(
                        $this->spread(
                            '  ' . $icon . ' ' . $type . ' in ' . $signName . ' · Personal',
                            $c->format('j M') . '  '
                        )
                    ));
                    $this->put($this->row(''));
                    $this->put($this->row('    ' . $houseOrd . ' house  ·  ' . ($conjunctions ? implode('  ', $conjunctions) : 'no close conjunctions')));

                    if ($text) {
                        $this->put($this->row(''));
                        foreach ($this->wrap($text, self::IW - 4) as $line) {
                            $this->put($this->row('    ' . $line));
                        }
                    } else {
                        $this->put($this->row('    [text not yet generated — run horoscope:generate-lunar --type=lunation_house]'));
                    }

                    $this->put($this->row(''));
                    $this->put($this->row('    → See daily horoscope for ' . $c->format('j F Y')));
                    $this->put($this->row(''));
                }
            } else {
                $this->put($this->row(''));
                $this->put($this->row('  Profile #' . $profile->id . ' · ' . ($profile->name ?? 'unnamed')));
                $this->put($this->row('  [No natal chart — add birth time and city for personalized lunations]'));
                $this->put($this->row(''));
            }
            $this->put($this->divider());
        }

        // ── AD placeholder ─────────────────────────────────────────────────
        $this->put($this->row($this->center('[AD PLACEHOLDER]')));
        $this->put($this->divider());

        // ── Day-by-day list ────────────────────────────────────────────────
        $this->put($this->row($this->spread('  📅  DAY BY DAY', '')));
        $this->put($this->row(''));

        $prevSignIdx = -1;
        foreach ($days as $d => $day) {
            $c        = $day['carbon'];
            $wdName   = self::WEEKDAYS[$c->dayOfWeekIso - 1];
            $signName = PlanetaryPosition::SIGN_NAMES[$day['sign_idx']] ?? '';
            $signG    = self::SIGN_GLYPHS[$day['sign_idx']] ?? '';
            $lunarOrd = $this->ordinal($day['lunar_day']);

            // Special tag line
            if ($day['new_moon']) {
                $this->put($this->row('  🌑 New Moon'));
            } elseif ($day['full_moon']) {
                $this->put($this->row('  🌕 Full Moon'));
            }

            // VoC bar placeholder (would need aspect data to compute properly)
            // $this->put($this->row('  ⚠ Void-of-Course HH:MM – HH:MM'));

            // Day header
            $todayMark  = $day['is_today'] ? ' ◄' : '';
            $dateLabel  = $c->format('j') . ' ' . $c->format('M') . ' ' . $wdName . $todayMark;
            $moonStr    = $day['phase'] . ' Moon in ' . $signG . ' ' . $signName;
            $lunarLabel = $lunarOrd . ' lunar day';

            $this->put($this->row(
                $this->spread('  ' . $dateLabel . '  ' . $moonStr, $lunarLabel . '  ')
            ));

            // Day text — show only on the first day the Moon enters a sign
            if ($day['sign_idx'] !== $prevSignIdx) {
                $dayBlock = TextBlock::pick('moon_in_' . strtolower($signName), 'lunar_day', 1);
                if ($dayBlock) {
                    $dayText = trim(strip_tags($dayBlock->text));
                    foreach ($this->wrap($dayText, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                } else {
                    $this->put($this->row('    [moon_in_' . strtolower($signName) . ' · not yet generated]'));
                }
                $prevSignIdx = $day['sign_idx'];
            }
            $this->put($this->row(''));
        }

        // ── Upcoming lunations ─────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ☽  LUNATIONS THIS MONTH', '')));
        $this->put($this->row(''));

        if (empty($lunations)) {
            $this->put($this->row('    No lunations detected for ' . $monthStr . '.'));
        } else {
            foreach ($lunations as $lun) {
                $type     = $lun['new_moon'] ? 'New Moon' : 'Full Moon';
                $icon     = $lun['new_moon'] ? '🌑' : '🌕';
                $c        = $lun['carbon'];
                $signName = PlanetaryPosition::SIGN_NAMES[$lun['sign_idx']] ?? '';
                $tagKey   = ($lun['new_moon'] ? 'new_moon_in_' : 'full_moon_in_') . strtolower($signName);
                $tagBlock = TextBlock::pick($tagKey, 'lunation_sign', 1);
                $tagText  = $tagBlock ? ' — ' . $tagBlock->text : '';
                $this->put($this->row(
                    '  ' . $icon . '  ' . $c->format('j M') . '  '
                    . $type . ' in ' . $signName . $tagText
                ));
            }
        }

        $this->put($this->row(''));

        // ── AD placeholder ─────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->center('[AD PLACEHOLDER]')));

        // ── Bottom links ───────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  → Monthly horoscope for ' . $start->format('F Y')));
        $this->put($this->row('  → Lunar calendar for ' . $prevMonth->format('F Y')));
        $this->put($this->row('  → Lunar calendar for ' . $nextMonth->format('F Y')));

        // ── Footer ─────────────────────────────────────────────────────────
        $this->put($this->divider());
        $newMoonCount  = count(array_filter($days, fn ($d) => $d['new_moon']));
        $fullMoonCount = count(array_filter($days, fn ($d) => $d['full_moon']));
        $this->put($this->row(
            '  lunar  ·  ' . $monthStr . '  ·  ' . $daysInMonth . ' days'
            . '  ·  ' . $newMoonCount . ' new moon  ·  ' . $fullMoonCount . ' full moon'
        ));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Calendar grid renderer ────────────────────────────────────────────

    /**
     * Renders the 7-column calendar grid as two rows per calendar week:
     *   Row 1 (day numbers): " DD " with N/F/star/dot markers
     *   Row 2 (phase+sign):  emoji + 3-letter sign abbreviation
     *
     * Cell width = 9 chars in the day-number row (pure ASCII → perfect alignment).
     * Phase row has emoji (displays as 2 terminal columns but 1 mb_strlen),
     * so alignment may shift ±1 col per emoji — acceptable for a design tool.
     */
    private function renderCalendarGrid(array $days, int $firstWeekday, int $daysInMonth): void
    {
        $cellW  = 9;
        $indent = '  ';

        // Weekday header
        $hdrLine = $indent;
        foreach (self::WEEKDAYS as $wd) {
            $hdrLine .= str_pad($wd, $cellW);
        }
        $this->put($this->row(rtrim($hdrLine)));
        $this->put($this->row(''));

        // Build flat slot array: index 1..N, first $firstWeekday-1 slots are empty
        $totalSlots = 7 * (int) ceil(($daysInMonth + $firstWeekday - 1) / 7);
        $slots      = [];

        for ($slot = 1; $slot <= $totalSlots; $slot++) {
            $dayNum     = $slot - ($firstWeekday - 1);
            $slots[$slot] = ($dayNum >= 1 && $dayNum <= $daysInMonth) ? $days[$dayNum] : null;
        }

        $rows = (int) ($totalSlots / 7);
        for ($r = 0; $r < $rows; $r++) {
            $numLine   = $indent;
            $phaseLine = $indent;

            for ($col = 0; $col < 7; $col++) {
                $slot = $r * 7 + $col + 1;
                $day  = $slots[$slot] ?? null;

                if ($day === null) {
                    $numLine   .= str_repeat(' ', $cellW);
                    $phaseLine .= str_repeat(' ', $cellW);
                } else {
                    $d      = $day['carbon']->day;
                    $marker = $day['new_moon']  ? 'N'
                            : ($day['full_moon'] ? 'F'
                            : ($day['is_today']  ? '*'
                            : ' '));

                    // Day-number row: " DD M    " (2+1+1+5 = 9 padded to cellW)
                    $numCell = sprintf('%2d%s', $d, $marker);
                    $numLine .= str_pad($numCell, $cellW);

                    // Phase row: emoji + 3-letter sign + trailing spaces
                    // emoji = 1 mb_strlen but 2 terminal cols → pad = cellW - 1 emoji - 3 sign - 1 extra
                    // We use cellW-1 extra spaces so alignment roughly matches
                    $phaseCell  = $day['phase'] . $day['sign_short'];
                    $phaseLine .= $phaseCell . str_repeat(' ', $cellW - 4); // emoji 1 + sign 3 + spaces (cellW-4)
                }
            }

            $this->put($this->row(rtrim($numLine)));
            $this->put($this->row(rtrim($phaseLine)));
            $this->put($this->row(''));
        }
    }

    // ── Lunation resolver ─────────────────────────────────────────────────

    /**
     * Marks each day as new_moon or full_moon using local-minimum detection.
     * Among consecutive days within 22.5° of exact 0°/180°, only the closest
     * day is marked — handles months with two lunations of the same type.
     */
    private function resolveLunations(array &$days): void
    {
        foreach (['new' => 'dist_new', 'full' => 'dist_full'] as $type => $distKey) {
            $field      = $type . '_moon';
            $threshold  = 22.5;
            $candidates = [];

            foreach ($days as $d => $day) {
                if ($day[$distKey] < $threshold) {
                    $candidates[] = $d;
                }
            }

            // Split candidates into consecutive runs; pick winner per run
            $runs = [];
            $run  = [];
            foreach ($candidates as $d) {
                if (empty($run) || $d === end($run) + 1) {
                    $run[] = $d;
                } else {
                    $runs[] = $run;
                    $run    = [$d];
                }
            }
            if (! empty($run)) {
                $runs[] = $run;
            }

            foreach ($runs as $run) {
                $winner = array_reduce($run, function ($best, $d) use ($days, $distKey) {
                    return ($best === null || $days[$d][$distKey] < $days[$best][$distKey])
                        ? $d : $best;
                }, null);

                if ($winner !== null) {
                    $days[$winner][$field] = true;
                }
            }
        }
    }

    // ── Moon phase ────────────────────────────────────────────────────────

    private function moonPhase(float $elongation): array
    {
        foreach (self::MOON_PHASES as [$from, $to, $emoji, $name]) {
            if ($elongation >= $from && $elongation < $to) {
                return [$emoji, $name];
            }
        }
        return ['🌑', 'New Moon'];
    }

    // ── Ordinal helper ────────────────────────────────────────────────────

    private function ordinal(int $n): string
    {
        $suffix = match (true) {
            $n % 100 >= 11 && $n % 100 <= 13 => 'th',
            $n % 10 === 1                     => 'st',
            $n % 10 === 2                     => 'nd',
            $n % 10 === 3                     => 'rd',
            default                           => 'th',
        };
        return $n . $suffix;
    }

    // ── Box-drawing helpers (same pattern as UiDailyReport) ───────────────

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

    private function center(string $text): string
    {
        $len = mb_strlen($text);
        if ($len >= self::IW) {
            return $text;
        }
        $pad   = self::IW - $len;
        $left  = (int) floor($pad / 2);
        $right = $pad - $left;
        return str_repeat(' ', $left) . $text . str_repeat(' ', $right);
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

    /** Word-wrap $text to $width, return array of lines. */
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
