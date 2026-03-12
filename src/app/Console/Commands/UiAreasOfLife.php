<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Services\AspectCalculator;
use App\Services\Horoscope\Shared\AreasOfLifeScorer;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI — Areas of Life daily scores (section 4.5).
 *
 * Shows score100 for each of the 11 life categories for every day
 * in the selected period. Raw values — no aggregation, no inflection logic.
 * The monthly chart will be built on the UI layer later; this command
 * produces the raw per-day data to verify correctness.
 *
 * Usage:
 *   php artisan horoscope:ui-areas-of-life {profile}
 *   php artisan horoscope:ui-areas-of-life {profile} --date=2026-03-01
 *   php artisan horoscope:ui-areas-of-life {profile} --date=2026-03-01 --view=week
 */
class UiAreasOfLife extends Command
{
    private const W  = 78;
    private const IW = 74;

    protected $signature = 'horoscope:ui-areas-of-life
                            {profile   : Profile ID}
                            {--view=month : month|week}
                            {--date=      : Any date in period (YYYY-MM-DD, default today)}';

    protected $description = 'Areas of Life score per day — raw daily values (section 4.5)';

    public function handle(
        AspectCalculator  $calculator,
        AreasOfLifeScorer $scorer,
    ): int {
        $profileId = (int) $this->argument('profile');
        $view      = $this->option('view') ?: 'month';
        $dateStr   = $this->option('date') ?: now()->toDateString();
        $anchor    = Carbon::parse($dateStr);

        // ── Load profile ─────────────────────────────────────────────────
        $profile = Profile::with('natalChart')->find($profileId);
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        $natalPlanets = $profile->natalChart?->planets ?? [];
        $houseCusps   = $profile->natalChart?->houses  ?? [];

        if (empty($natalPlanets) || count($houseCusps) < 12) {
            $this->error('Profile has no complete natal chart (planets + 12 house cusps required).');
            return self::FAILURE;
        }

        // ── Period ────────────────────────────────────────────────────────
        [$start, $end] = match ($view) {
            'week'  => [$anchor->copy()->startOfWeek(Carbon::MONDAY), $anchor->copy()->endOfWeek(Carbon::SUNDAY)],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        // ── Load positions + compute daily scores ─────────────────────────
        // $rows[date]  = [catIndex => score100, ...]
        // $hasRx[date] = bool (any Mercury–Saturn retrograde that day)
        $rows  = [];
        $hasRx = [];

        foreach ($dates as $date) {
            $pos = PlanetaryPosition::forDate($date)->orderBy('body')->get();
            if ($pos->isEmpty()) {
                continue;
            }

            $transit = $pos->map(fn ($p) => [
                'body'          => $p->body,
                'longitude'     => $p->longitude,
                'speed'         => $p->speed,
                'sign'          => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            $rxBodies = $pos
                ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
                ->pluck('body')
                ->toArray();

            $hasRx[$date] = ! empty($rxBodies);

            $nbs   = $scorer->buildDayScores($calculator->transitToNatal($transit, $natalPlanets), $rxBodies);
            $areas = $scorer->score($nbs, $houseCusps);

            $rows[$date] = [];
            foreach ($areas as $i => $dto) {
                $rows[$date][$i] = $dto->score100;
            }
        }

        if (empty($rows)) {
            $this->error('No planetary positions found for this period.');
            return self::FAILURE;
        }

        // ── Render ────────────────────────────────────────────────────────
        $profileName = $profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id;
        $periodLabel = $view === 'week'
            ? $start->format('j M') . ' – ' . $end->format('j M Y')
            : $start->format('F Y');

        $cats = AreasOfLifeScorer::CATEGORIES;
        $n    = count($cats); // 11

        // Column widths: date = 12, each score = 4 chars ("  72" right-aligned)
        // Total data: 12 + 11*4 = 56 — fits in IW=74

        $this->newLine();
        $this->put($this->top());
        $this->put($this->row($this->spread(
            '  📊  AREAS OF LIFE · ' . $periodLabel,
            $profileName,
        )));
        $this->put($this->divider());
        $this->put($this->row(''));

        // Header: emojis
        $hdr = str_pad('  Date', 14);
        foreach ($cats as $cat) {
            $hdr .= str_pad($this->catEmoji($cat['slug']), 4);
        }
        $this->put($this->row(mb_substr($hdr, 0, self::IW)));

        // Header: abbreviated names
        $hdr2 = str_pad('', 14);
        foreach ($cats as $cat) {
            $hdr2 .= str_pad($this->catAbbr($cat['slug']), 4);
        }
        $this->put($this->row(mb_substr($hdr2, 0, self::IW)));
        $this->put($this->row('  ' . str_repeat('─', 12 + $n * 4)));
        $this->put($this->row(''));

        // Data rows
        foreach ($rows as $date => $scores) {
            $dateCol = str_pad(Carbon::parse($date)->format('D j M'), 12) . ($hasRx[$date] ? 'R ' : '  ');
            $line    = $dateCol;
            for ($i = 0; $i < $n; $i++) {
                $s     = $scores[$i] ?? 50;
                $line .= str_pad($s, 4, ' ', STR_PAD_LEFT);
            }
            $this->put($this->row(mb_substr($line, 0, self::IW)));
        }

        $this->put($this->row(''));
        $this->put($this->row('  R = has retrograde planet(s)  ·  score = 0–100  (50 = neutral)'));
        $this->put($this->row(''));
        $this->put($this->divider());

        // ── Sparklines ────────────────────────────────────────────────────
        $this->put($this->row('  ◆  TREND'));
        $this->put($this->row(''));

        $todayDate  = $anchor->toDateString();
        $dateKeys   = array_keys($rows);
        $todayIdx   = array_search($todayDate, $dateKeys, true);

        foreach ($cats as $i => $cat) {
            $catScores  = array_map(fn ($r) => $r[$i] ?? 50, array_values($rows));
            $todayScore = $rows[$todayDate][$i] ?? 50;
            $spark      = $this->sparkline($catScores, $todayIdx !== false ? (int) $todayIdx : null);
            $stars      = $this->scoreStars($todayScore);
            $emoji      = $this->catEmoji($cat['slug']);
            $abbr       = $this->catAbbr($cat['slug']);
            $line       = "  {$emoji} {$abbr}  {$spark}  {$stars} {$todayScore}";
            $this->put($this->row($line));
        }

        $this->put($this->row(''));
        $this->put($this->divider());
        $this->put($this->row('  areas-of-life · ' . $view . ' · ' . $start->toDateString() . ' – ' . $end->toDateString()));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Label helpers ────────────────────────────────────────────────────

    private function catEmoji(string $slug): string
    {
        return match ($slug) {
            'love'            => '❤️',
            'home'            => '🏠',
            'creativity'      => '🎨',
            'spirituality'    => '🔮',
            'health'          => '💚',
            'finance'         => '💰',
            'travel'          => '✈️',
            'career'          => '💼',
            'personal_growth' => '🌱',
            'communication'   => '💬',
            'contracts'       => '📝',
            default           => '  ',
        };
    }

    private function catAbbr(string $slug): string
    {
        return match ($slug) {
            'love'            => 'Lv',
            'home'            => 'Hm',
            'creativity'      => 'Cr',
            'spirituality'    => 'Sp',
            'health'          => 'Hl',
            'finance'         => 'Fn',
            'travel'          => 'Tr',
            'career'          => 'Ca',
            'personal_growth' => 'Pg',
            'communication'   => 'Co',
            'contracts'       => 'Ct',
            default           => '??',
        };
    }

    // ── Sparkline helpers ────────────────────────────────────────────────

    private function sparkline(array $scores, ?int $todayIdx): string
    {
        $bars  = ['▁','▂','▃','▄','▅','▆','▇','█'];
        $min   = min($scores);
        $max   = max($scores);
        $range = $max - $min;
        $result = '';
        foreach ($scores as $s) {
            $level   = $range > 0 ? (int) round(($s - $min) / $range * 7) : 3;
            $result .= $bars[max(0, min(7, $level))];
        }
        return $result;
    }

    private function scoreStars(int $score): string
    {
        if ($score >= 75) return '★★★★★';
        if ($score >= 55) return '★★★★ ';
        if ($score >= 42) return '★★★  ';
        if ($score >= 30) return '★★   ';
        return '⚠    ';
    }

    // ── Box-drawing ──────────────────────────────────────────────────────

    private function top(): string     { return '┌' . str_repeat('─', self::W - 2) . '┐'; }
    private function bottom(): string  { return '└' . str_repeat('─', self::W - 2) . '┘'; }
    private function divider(): string { return '├' . str_repeat('─', self::W - 2) . '┤'; }
    private function put(string $line): void { $this->line($line); }

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
        return $len >= $width ? mb_substr($str, 0, $width) : $str . str_repeat(' ', $width - $len);
    }
}
