<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the weekly horoscope.
 *
 * Renders what the user would see on the weekly horoscope page.
 *
 * Layout:
 *   ┌─ header (title + week range) ──────────────────────────────────┐
 *   │ profile name                                                    │
 *   │ bi-wheel placeholder                                            │
 *   │ planet positions (Monday)                                       │
 *   ├─ key transit factors (top 7 transit-to-natal, Monday orbs) ────┤
 *   ├─ lunation (if new/full moon this week) ────────────────────────┤
 *   ├─ key dates ─────────────────────────────────────────────────────┤
 *   ├─ areas of life (with best day) ────────────────────────────────┤
 *   └─ footer ───────────────────────────────────────────────────────┘
 */
class UiWeeklyReport extends Command
{
    private const W  = 72;
    private const IW = 68;

    private const BODY_GLYPHS = [
        0 => '☉', 1 => '☽',  2 => '☿', 3 => '♀',  4 => '♂',
        5 => '♃', 6 => '♄',  7 => '♅', 8 => '♆',  9 => '♇',
       10 => '⚷', 11 => '☊', 12 => '⚸',
    ];

    private const SIGN_GLYPHS = [
        0 => '♈', 1 => '♉', 2 => '♊',  3 => '♋',
        4 => '♌', 5 => '♍', 6 => '♎',  7 => '♏',
        8 => '♐', 9 => '♑', 10 => '♒', 11 => '♓',
    ];

    private const ASPECT_GLYPHS = [
        'conjunction'  => '☌', 'opposition'   => '☍',
        'trine'        => '△', 'square'        => '□',
        'sextile'      => '⚹', 'quincunx'      => '⚻',
        'semi_sextile' => '∠',
    ];

    private const MOON_PHASES = [
        [0,   45,  '🌑', 'New Moon'],
        [45,  90,  '🌒', 'Waxing Crescent'],
        [90,  135, '🌓', 'First Quarter'],
        [135, 180, '🌔', 'Waxing Gibbous'],
        [180, 225, '🌕', 'Full Moon'],
        [225, 270, '🌖', 'Waning Gibbous'],
        [270, 315, '🌗', 'Last Quarter'],
        [315, 360, '🌘', 'Waning Crescent'],
    ];

    // ── Life categories — house indices (0-based: H1=0 … H12=11) ────────
    private const CATEGORIES = [
        ['emoji' => '❤️',  'name' => 'Love',            'houses' => [4, 6]],
        ['emoji' => '🏠',  'name' => 'Home',             'houses' => [3]],
        ['emoji' => '🎨',  'name' => 'Creativity',       'houses' => [4]],
        ['emoji' => '🔮',  'name' => 'Spirituality',     'houses' => [8, 11]],
        ['emoji' => '💚',  'name' => 'Health',           'houses' => [5, 0]],
        ['emoji' => '💰',  'name' => 'Finance',          'houses' => [1, 7]],
        ['emoji' => '✈️',  'name' => 'Travel',           'houses' => [8, 2]],
        ['emoji' => '💼',  'name' => 'Career',           'houses' => [9]],
        ['emoji' => '🌱',  'name' => 'Personal Growth',  'houses' => [0]],
        ['emoji' => '💬',  'name' => 'Communication',    'houses' => [2]],
        ['emoji' => '📝',  'name' => 'Contracts',        'houses' => [6, 2]],
    ];

    // ── Sign rulers (traditional, body IDs) ──────────────────────────────
    private const SIGN_RULERS = [
         0 => 4,  // Aries   → Mars
         1 => 3,  // Taurus  → Venus
         2 => 2,  // Gemini  → Mercury
         3 => 1,  // Cancer  → Moon
         4 => 0,  // Leo     → Sun
         5 => 2,  // Virgo   → Mercury
         6 => 3,  // Libra   → Venus
         7 => 4,  // Scorpio → Mars
         8 => 5,  // Sagittarius → Jupiter
         9 => 6,  // Capricorn   → Saturn
        10 => 6,  // Aquarius    → Saturn
        11 => 5,  // Pisces      → Jupiter
    ];

    protected $signature = 'horoscope:ui-weekly
                            {profile : Profile ID}
                            {--date=       : Any date within the week (YYYY-MM-DD, default: today)}
                            {--simplified  : Show 1-sentence simplified texts (uses _short sections)}
                            {--ai          : Generate synthesis intro with Claude (AI L1)}';

    protected $description = 'Render a weekly horoscope in pseudo-browser console UI';

    public function handle(AspectCalculator $calculator): int
    {
        $date       = $this->option('date') ?: now()->toDateString();
        $simplified = $this->option('simplified');

        $profile = Profile::find($this->argument('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        // Week range: Monday–Sunday
        $monday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->endOfWeek(Carbon::SUNDAY);

        // Fetch positions for each day of the week
        $weekDates = [];
        for ($d = $monday->copy(); $d->lte($sunday); $d->addDay()) {
            $weekDates[] = $d->toDateString();
        }

        $weekPositions = [];
        foreach ($weekDates as $wd) {
            $pos = PlanetaryPosition::forDate($wd)->orderBy('body')->get();
            if ($pos->isNotEmpty()) {
                $weekPositions[$wd] = $pos;
            }
        }

        if (empty($weekPositions)) {
            $this->error("No planetary positions found for this week.");
            return self::FAILURE;
        }

        // Use Monday (or first available day) as representative
        $mondayDate     = array_key_first($weekPositions);
        $mondayPos      = $weekPositions[$mondayDate];
        $mondayPlanets  = $mondayPos->keyBy('body');

        $natalPlanets = $profile->natalChart?->planets ?? [];

        // Transit planets array for AspectCalculator
        $transitPlanets = $mondayPos->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();

        // Transit-to-natal aspects — 7-day merge, unique per combo, best orb day
        // Slow planets (body >= 5) are shown first; fast planets show peak day label.
        $transitNatalAspects = [];
        if (! empty($natalPlanets)) {
            $bestByKey = []; // key => ['asp' => ..., 'date' => ...]
            foreach ($weekDates as $dayDate) {
                $dayPos = $weekPositions[$dayDate] ?? null;
                if (! $dayPos) { continue; }
                $dayTransit = $dayPos->map(fn ($p) => [
                    'body' => $p->body, 'longitude' => $p->longitude,
                    'speed' => $p->speed, 'sign' => (int) floor($p->longitude / 30),
                    'is_retrograde' => $p->is_retrograde,
                ])->values()->all();
                foreach ($calculator->transitToNatal($dayTransit, $natalPlanets) as $asp) {
                    $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                    if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                        $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                    }
                }
            }
            // Sort: slow planets first (body >= 5), then fast; within each group by orb
            usort($bestByKey, function ($a, $b) {
                $aSlow = $a['asp']['transit_body'] >= 5 ? 0 : 1;
                $bSlow = $b['asp']['transit_body'] >= 5 ? 0 : 1;
                if ($aSlow !== $bSlow) { return $aSlow - $bSlow; }
                return $a['asp']['orb'] <=> $b['asp']['orb'];
            });
            $transitNatalAspects = array_slice($bestByKey, 0, 7);
        }

        // Notable retrogrades (Mercury–Saturn)
        $retrogrades = $mondayPos
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        $rxBodies = $retrogrades->pluck('body')->toArray();

        // Detect lunations within the week
        $lunation = $this->detectLunation($weekPositions);

        // Extract plain aspects + peak days from 7-day merge
        $plainAspects = array_map(fn ($item) => $item['asp'], $transitNatalAspects);
        $peakDays     = array_map(fn ($item) => ['orb' => $item['asp']['orb'], 'date' => $item['date']], $transitNatalAspects);

        // Key dates: peak day per selected top-7 aspect + lunation
        $keyDates = $this->buildKeyDates($peakDays, $lunation, $plainAspects, $weekDates);

        // Collect texts for AI synthesis
        $assembledTexts = [];
        foreach ($plainAspects as $asp) {
            $tName   = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName   = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $aspWord = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $key     = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
            $block   = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);
            if ($block) {
                $assembledTexts[] = "[Transit {$tName} {$aspWord} natal {$nName}]\n" . trim(strip_tags($block->text));
            }
        }
        foreach ($retrogrades as $p) {
            $signIdx  = (int) floor($p->longitude / 30);
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $bodyName = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
            $rxKey    = strtolower($bodyName) . '_rx_' . strtolower($signName);
            $block    = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);
            if ($block) {
                $assembledTexts[] = "[{$bodyName} Retrograde in {$signName}]\n" . trim(strip_tags($block->text));
            }
        }

        $weekLabel = $monday->format('j M') . ' – ' . $sunday->format('j M Y');

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ WEEKLY HOROSCOPE', '[' . $weekLabel . ']  ')));
        $this->put($this->row('  ' . $monday->format('j F') . ' – ' . $sunday->format('j F Y')));
        $this->put($this->row('  ' . ($profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id)));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center('NATAL + TRANSIT BI-WHEEL · ' . $weekLabel)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Transit subtitle + Rx legend ─────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->transitSubtitle($mondayPlanets)));
        $this->put($this->row('  * Rx = Retrograde (apparent backward motion)'));
        $this->put($this->row(''));

        // ── Transit planet list ───────────────────────────────────────────
        foreach ($this->transitLines($mondayPos->all()) as $line) {
            $this->put($this->row($line));
        }

        // ── AI synthesis ─────────────────────────────────────────────────
        if ($this->option('ai')) {
            $moonSignIdx  = (int) floor(($mondayPlanets->get(PlanetaryPosition::MOON)?->longitude ?? 0) / 30);
            $moonSignName = PlanetaryPosition::SIGN_NAMES[$moonSignIdx] ?? '';
            $synthesis    = $this->generateSynthesis(
                $assembledTexts,
                $natalPlanets,
                $monday,
                $sunday,
                $moonSignName,
                $simplified,
                $profile->id,
            );
            if ($synthesis) {
                $this->put($this->divider());
                $this->put($this->row($this->spread('  ✦  WEEK OVERVIEW', 'AI  ')));
                $this->put($this->row(''));
                foreach (preg_split('/\n{2,}/', trim($synthesis)) as $para) {
                    foreach ($this->wrap(trim($para), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                    $this->put($this->row(''));
                }
            }
        }

        // ── Key transit factors ───────────────────────────────────────────
        if (count($transitNatalAspects) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ◆  KEY TRANSIT FACTORS', '')));

            foreach ($transitNatalAspects as $item) {
                $asp     = $item['asp'];
                $peakDate = $item['date'];
                $tBody   = $asp['transit_body'];
                $nBody   = $asp['natal_body'];
                $tGlyph  = self::BODY_GLYPHS[$tBody] ?? '?';
                $nGlyph  = self::BODY_GLYPHS[$nBody] ?? '?';
                $tName   = PlanetaryPosition::BODY_NAMES[$tBody] ?? '';
                $nName   = PlanetaryPosition::BODY_NAMES[$nBody] ?? '';
                $aGlyph  = self::ASPECT_GLYPHS[$asp['aspect']] ?? $asp['aspect'];
                $aLabel  = ucfirst(str_replace('_', ' ', $asp['aspect']));
                $key     = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
                $block   = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);

                // Fast planets (body < 5): show peak day label
                $dayLabel = ($tBody < 5 && $peakDate)
                    ? '  · ' . \Carbon\Carbon::parse($peakDate)->format('l')
                    : '';
                $heading = '· ' . $tGlyph . ' ' . $tName . '  ' . $aGlyph . ' ' . $aLabel . '  ' . $nGlyph . ' natal ' . $nName . $dayLabel;
                $this->put($this->row(''));
                $this->put($this->row('  ' . $heading));

                if ($block) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }

            // ── Retrogrades ───────────────────────────────────────────────
            foreach ($retrogrades as $p) {
                $signIdx  = (int) floor($p->longitude / 30);
                $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
                $signGlyph = self::SIGN_GLYPHS[$signIdx] ?? '';
                $bodyName = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
                $bodyGlyph = self::BODY_GLYPHS[$p->body] ?? '';
                $rxKey    = strtolower($bodyName) . '_rx_' . strtolower($signName);
                $block    = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);

                $this->put($this->row(''));
                $this->put($this->row('  · ' . $bodyGlyph . ' ' . $bodyName . ' Retrograde  ·  in ' . $signGlyph . ' ' . $signName));

                if ($block) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }

            $this->put($this->row(''));
        }

        // ── Lunation ──────────────────────────────────────────────────────
        if ($lunation) {
            $this->put($this->divider());
            $lunLabel  = $lunation['emoji'] . '  ' . $lunation['name'] . ' in ' . $lunation['sign'];
            $lunDate   = Carbon::parse($lunation['date'])->format('l, j M');
            $this->put($this->row($this->spread('  ' . $lunLabel, $lunDate . '  ')));
            $this->put($this->row(''));
            $lunKey   = strtolower(str_replace(' ', '_', $lunation['type'])) . '_in_' . strtolower($lunation['sign']);
            $lunBlock = TextBlock::pick($lunKey, $simplified ? 'lunation_sign_short' : 'lunation_sign', 1);
            if ($lunBlock) {
                foreach ($this->wrap(trim(strip_tags($lunBlock->text)), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
            $this->put($this->row(''));
        }

        // ── Key dates ─────────────────────────────────────────────────────
        if (! empty($keyDates)) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  📅  KEY DATES', '')));
            $this->put($this->row(''));
            foreach ($keyDates as $kd) {
                $dateStr = Carbon::parse($kd['date'])->format('D j M');
                $line    = '  · ' . $dateStr . '  —  ' . $kd['label'];
                $this->put($this->row($line));
            }
            $this->put($this->row(''));
        }

        // ── Areas of life (house-based, 7-day average) ───────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ★  AREAS OF LIFE', '')));
        $this->put($this->row(''));

        $aspectWeights = [
            'trine' => +2, 'sextile' => +1, 'conjunction' => +1,
            'semi_sextile' => 0, 'quincunx' => -1, 'square' => -2, 'opposition' => -2,
        ];
        $houseCusps = $profile->natalChart?->houses ?? [];

        // Accumulate per-category score across 7 days
        $catScoreSums  = array_fill(0, count(self::CATEGORIES), 0);
        $catScoreDays  = 0;

        for ($d = 0; $d < 7; $d++) {
            $dayDate = $monday->copy()->addDays($d)->toDateString();
            $dayPositions = PlanetaryPosition::forDate($dayDate)->orderBy('body')->get();
            if ($dayPositions->isEmpty()) { continue; }

            $dayTransit = $dayPositions->map(fn($p) => [
                'body' => $p->body, 'longitude' => $p->longitude,
                'speed' => $p->speed, 'sign' => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            $dayRxBodies = $dayPositions
                ->filter(fn($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
                ->pluck('body')->toArray();

            $dayAspects = $calculator->transitToNatal($dayTransit, $natalPlanets);

            $nbs = [];
            foreach ($dayAspects as $asp) {
                $w = $aspectWeights[$asp['aspect']] ?? 0;
                $nbs[$asp['natal_body']] = ($nbs[$asp['natal_body']] ?? 0) + $w;
            }
            foreach ($dayRxBodies as $body) {
                $nbs[$body] = ($nbs[$body] ?? 0) - 1;
            }

            foreach (self::CATEGORIES as $i => $cat) {
                $score = 0;
                $rulerCount = 0;
                foreach ($cat['houses'] as $hIdx) {
                    $cuspLon = $houseCusps[$hIdx] ?? null;
                    if ($cuspLon === null) { continue; }
                    $signIdx = (int) floor(fmod($cuspLon, 360) / 30);
                    $rulerBody = self::SIGN_RULERS[$signIdx] ?? null;
                    if ($rulerBody !== null) {
                        $score += $nbs[$rulerBody] ?? 0;
                        $rulerCount++;
                    }
                }
                $catScoreSums[$i] += $rulerCount > 1 ? $score / $rulerCount : $score;
            }
            $catScoreDays++;
        }

        foreach (self::CATEGORIES as $i => $cat) {
            $avgScore = $catScoreDays > 0 ? $catScoreSums[$i] / $catScoreDays : 0;
            $score100 = max(1, min(100, 50 + (int) round($avgScore * 8)));
            if ($score100 >= 67) {
                $rating = '★★★     ';
            } elseif ($score100 >= 34) {
                $rating = '★★      ';
            } else {
                $rating = '⚠ wait  ';
            }
            $this->put($this->row($this->spread('  ' . $cat['emoji'] . ' ' . $cat['name'], $rating)));
        }
        $this->put($this->row(''));

        // ── Footer ────────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxLabel = $retrogrades->map(fn ($p) => (PlanetaryPosition::BODY_NAMES[$p->body] ?? '') . ' Rx')->implode(' · ');
        $this->put($this->row(
            '  weekly  ·  ' . $weekLabel
            . '  ·  ' . count($transitNatalAspects) . ' transits'
            . ($rxLabel ? '  ·  ' . $rxLabel : '')
        ));
        $this->put($this->bottom());

        $this->newLine();

        return self::SUCCESS;
    }

    // ── Lunation detection ────────────────────────────────────────────────

    private function detectLunation(array $weekPositions): ?array
    {
        $prev = null;
        foreach ($weekPositions as $date => $positions) {
            $planets = $positions->keyBy('body');
            $sun     = $planets->get(PlanetaryPosition::SUN);
            $moon    = $planets->get(PlanetaryPosition::MOON);
            if (! $sun || ! $moon) {
                $prev = null;
                continue;
            }
            $elong = fmod(($moon->longitude - $sun->longitude + 360), 360);

            // New moon: elongation < 20° (or transition from >340 to <20)
            if ($elong < 20 || ($prev !== null && $prev > 340 && $elong < 40)) {
                $signIdx = (int) floor($sun->longitude / 30);
                return [
                    'date'  => $date,
                    'type'  => 'new_moon',
                    'name'  => 'New Moon',
                    'emoji' => '🌑',
                    'sign'  => PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                ];
            }
            // Full moon: elongation 170–190°
            if ($elong >= 170 && $elong <= 190) {
                $signIdx = (int) floor($moon->longitude / 30);
                return [
                    'date'  => $date,
                    'type'  => 'full_moon',
                    'name'  => 'Full Moon',
                    'emoji' => '🌕',
                    'sign'  => PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                ];
            }
            $prev = $elong;
        }
        return null;
    }

    // ── Peak day per aspect ───────────────────────────────────────────────

    private function findPeakDays(
        array $weekPositions,
        array $weekDates,
        array $aspects,
        AspectCalculator $calculator,
        array $natalPlanets,
    ): array {
        if (empty($aspects) || empty($natalPlanets)) {
            return [];
        }

        // For each aspect, track minimum orb day
        $minOrbs = [];
        foreach ($aspects as $i => $asp) {
            $minOrbs[$i] = ['orb' => PHP_FLOAT_MAX, 'date' => null];
        }

        foreach ($weekPositions as $date => $positions) {
            $tp = $positions->map(fn ($p) => [
                'body'          => $p->body,
                'longitude'     => $p->longitude,
                'speed'         => $p->speed,
                'sign'          => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            $dayAspects = $calculator->transitToNatal($tp, $natalPlanets);

            // Index day aspects by transit_body + aspect + natal_body
            $indexed = [];
            foreach ($dayAspects as $da) {
                $k = $da['transit_body'] . '_' . $da['aspect'] . '_' . $da['natal_body'];
                $indexed[$k] = $da;
            }

            foreach ($aspects as $i => $asp) {
                $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                if (isset($indexed[$k]) && $indexed[$k]['orb'] < $minOrbs[$i]['orb']) {
                    $minOrbs[$i] = ['orb' => $indexed[$k]['orb'], 'date' => $date];
                }
            }
        }

        return $minOrbs;
    }

    // ── Key dates builder ─────────────────────────────────────────────────
    // Shows peak day for each of the top-7 selected aspects (orb < 1.0°) + lunation.

    private function buildKeyDates(array $peakDays, ?array $lunation, array $aspects, array $weekDates): array
    {
        $dates = [];

        // Lunation first
        if ($lunation) {
            $dates[$lunation['date']][] = $lunation['emoji'] . ' ' . $lunation['name'] . ' in ' . $lunation['sign'];
        }

        // Peak day per aspect (only if orb < 1.0° — truly tight)
        foreach ($peakDays as $i => $peak) {
            if ($peak['date'] === null || $peak['orb'] > 1.0) {
                continue;
            }
            $asp    = $aspects[$i];
            $tName  = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName  = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $tGlyph = self::BODY_GLYPHS[$asp['transit_body']] ?? '';
            $nGlyph = self::BODY_GLYPHS[$asp['natal_body']] ?? '';
            $aGlyph = self::ASPECT_GLYPHS[$asp['aspect']] ?? '';
            $aLabel = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $dates[$peak['date']][] = $tGlyph . ' ' . $tName . ' ' . $aGlyph . ' ' . $aLabel . ' ' . $nGlyph . ' natal ' . $nName;
        }

        if (empty($dates)) {
            return [];
        }

        ksort($dates);

        $result = [];
        foreach ($dates as $date => $labels) {
            $unique   = array_unique($labels);
            $result[] = ['date' => $date, 'label' => implode('  ·  ', $unique)];
        }
        return $result;
    }

    // ── AI synthesis ──────────────────────────────────────────────────────

    private function generateSynthesis(
        array $assembledTexts,
        array $natalPlanets,
        Carbon $monday,
        Carbon $sunday,
        string $moonSignName,
        bool $simplified = false,
        int $profileId = 0,
    ): ?string {
        // ── Cache check ───────────────────────────────────────────────────
        $cacheKey = 'weekly_' . $profileId . '_' . $monday->toDateString() . ($simplified ? '_short' : '');
        $cached   = TextBlock::where('key', $cacheKey)
            ->where('section', 'ai_synthesis')
            ->where('language', 'en')
            ->first();

        if ($cached) {
            $this->line("  <fg=gray>[AI synthesis: cached]</>");
            return $cached->text;
        }

        /** @var \App\Contracts\AiProvider $ai */
        $ai = app(\App\Contracts\AiProvider::class);

        $natalLines = [];
        foreach ($natalPlanets as $np) {
            if (! in_array($np['body'] ?? -1, [0, 1])) {
                continue;
            }
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Week: {$monday->format('j F')} – {$sunday->format('j F Y')}\n";
        $prompt .= "Moon sign at week start: {$moonSignName}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($assembledTexts) {
            $prompt .= "\nThe following pre-generated descriptions will be shown to the person below this intro:\n\n";
            $prompt .= implode("\n\n", $assembledTexts);
        }
        $prompt .= $simplified
            ? "\n\nWrite exactly 1 paragraph (2–3 sentences) as a short weekly horoscope intro that captures the key theme of the week."
            : "\n\nWrite exactly 2 paragraphs as a weekly horoscope intro that synthesizes and introduces what follows.";

        $paragraphRule = $simplified
            ? '- 1 paragraph only — 2–3 sentences total'
            : "- Exactly 2 paragraphs separated by a blank line — no headers, no bullets, no lists\n- Each paragraph: 3–4 sentences";

        $system = "You are writing a personalized weekly horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . ($simplified ? "- Cut all filler: no \"The good news is\", \"This week is about\", \"At the same time\" — every sentence states a fact or concrete action\n" : '')
            . "- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real situations — concrete behaviour only\n"
            . ($simplified ? '' : "- First paragraph: the overall theme of the week based on the sky\n- Second paragraph: the personal angle — what these transits activate for this specific person\n")
            . "- Do NOT start with \"This week...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $ai->generate($prompt, $system, maxTokens: 500);
            $cost     = number_format($response->costUsd, 5);
            $this->line("  <fg=gray>[AI synthesis: {$response->inputTokens} in / {$response->outputTokens} out / \${$cost}]</>");

            if ($profileId > 0) {
                $now = now();
                TextBlock::updateOrCreate(
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => 'en', 'variant' => 1],
                    [
                        'text'       => $response->text,
                        'tone'       => 'neutral',
                        'tokens_in'  => $response->inputTokens,
                        'tokens_out' => $response->outputTokens,
                        'cost_usd'   => $response->costUsd,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            return $response->text;
        } catch (\Exception $e) {
            $this->warn('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function transitSubtitle($planets): string
    {
        $parts = [];

        if ($sun = $planets->get(PlanetaryPosition::SUN)) {
            $signIdx = (int) floor($sun->longitude / 30);
            $parts[] = '☉ Sun in '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '');
        }

        if ($moon = $planets->get(PlanetaryPosition::MOON)) {
            $signIdx = (int) floor($moon->longitude / 30);
            $retro   = $moon->is_retrograde ? ' Rx' : '';
            $parts[] = '☽ Moon in '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '') . $retro;
        }

        if (($mercury = $planets->get(PlanetaryPosition::MERCURY)) && $mercury->is_retrograde) {
            $parts[] = '☿ Mercury Rx';
        }

        return implode('  ·  ', $parts);
    }

    private function transitLines(array $positions): array
    {
        $col = [];
        foreach ($positions as $p) {
            $signIdx = (int) floor($p->longitude / 30);
            $retro   = $p->is_retrograde ? ' Rx' : '';
            $col[]   = (self::BODY_GLYPHS[$p->body] ?? '?') . ' '
                     . (PlanetaryPosition::BODY_NAMES[$p->body] ?? '') . ' in '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '')
                     . $retro;
        }

        $lines = [];
        $count = count($col);
        for ($i = 0; $i < $count; $i += 2) {
            $left  = $col[$i] ?? '';
            $right = $col[$i + 1] ?? '';
            if ($right !== '') {
                $pad     = max(1, 34 - mb_strlen($left));
                $lines[] = '  ' . $left . str_repeat(' ', $pad) . $right;
            } else {
                $lines[] = '  ' . $left;
            }
        }

        return $lines;
    }

    private function wheelLines(): array
    {
        return [
            '          · ☉ ·         ',
            '       ♆   ·   ·   ♄    ',
            '     ♅   ·       ·   ♃  ',
            '    ♇  ·   · ✦ ·  ·  ♂  ',
            '     ⚸   ·       ·   ♀  ',
            '       ☽   ·   ·   ☿    ',
            '          · ☊ ·         ',
        ];
    }

    private function top(): string     { return '┌' . str_repeat('─', self::W - 2) . '┐'; }
    private function bottom(): string  { return '└' . str_repeat('─', self::W - 2) . '┘'; }
    private function divider(): string { return '├' . str_repeat('─', self::W - 2) . '┤'; }

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
