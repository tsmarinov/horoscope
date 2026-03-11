<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the monthly horoscope.
 *
 * Renders what the user would see on the monthly horoscope page.
 *
 * Transit selection:
 *   - Slow planets (body >= 5): always included
 *   - Fast planets (body < 5): only if aspect active >= 14 days in the month
 *   - Focus on natal personal planets (Sun–Mars, bodies 0–4)
 *   - Top 10 aspects, slow first
 *
 * Progressed Moon: shown only when entering a new sign, new house,
 * or forming an exact aspect (orb <= 1°) to a natal planet.
 *
 * Layout:
 *   ┌─ header (month + year) ─────────────────────────────────────────┐
 *   │ profile name                                                      │
 *   │ bi-wheel placeholder                                              │
 *   │ transit planet list (1st of month, no degrees)                   │
 *   ├─ key transit factors (top 10, slow first) ──────────────────────┤
 *   ├─ progressed moon (only on sign/house change or exact aspect) ────┤
 *   ├─ lunations (new + full moon with sign + house text) ─────────────┤
 *   ├─ key dates ──────────────────────────────────────────────────────┤
 *   ├─ areas of life (30-day average) ────────────────────────────────┤
 *   └─ footer ────────────────────────────────────────────────────────┘
 */
class UiMonthlyReport extends Command
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

    // Aspect angles for progressed Moon exact aspect detection
    private const ASPECT_ANGLES = [
        0 => 'conjunction', 60 => 'sextile', 90 => 'square',
        120 => 'trine', 150 => 'quincunx', 180 => 'opposition',
    ];

    // Personal natal planet bodies (Sun–Mars)
    private const PERSONAL_BODIES = [0, 1, 2, 3, 4];

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
         0 => 4,  // Aries        → Mars
         1 => 3,  // Taurus       → Venus
         2 => 2,  // Gemini       → Mercury
         3 => 1,  // Cancer       → Moon
         4 => 0,  // Leo          → Sun
         5 => 2,  // Virgo        → Mercury
         6 => 3,  // Libra        → Venus
         7 => 4,  // Scorpio      → Mars
         8 => 5,  // Sagittarius  → Jupiter
         9 => 6,  // Capricorn    → Saturn
        10 => 6,  // Aquarius     → Saturn
        11 => 5,  // Pisces       → Jupiter
    ];

    // Progressed Moon mean motion per year (secondary progressions)
    private const PROG_MOON_DEG_PER_YEAR = 13.1764;

    // Max orb per aspect type (used for orb-weighted scoring)
    private const MAX_ORBS = [
        'conjunction'  => 8.0, 'opposition'   => 8.0,
        'trine'        => 8.0, 'square'        => 8.0,
        'sextile'      => 6.0, 'quincunx'      => 5.0,
        'semi_sextile' => 3.0,
    ];

    protected $signature = 'horoscope:ui-monthly
                            {profile : Profile ID}
                            {--date=       : Any date within the month (YYYY-MM-DD, default: today)}
                            {--simplified  : Show 1-sentence simplified texts (uses _short sections)}
                            {--ai          : Generate synthesis intro with Claude (AI L1)}';

    protected $description = 'Render a monthly horoscope in pseudo-browser console UI';

    public function handle(AspectCalculator $calculator): int
    {
        $date       = $this->option('date') ?: now()->toDateString();
        $simplified = $this->option('simplified');

        $profile = Profile::find($this->argument('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        // Month range
        $monthStart = Carbon::parse($date)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        // Collect all dates in month
        $monthDates = [];
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $monthDates[] = $d->toDateString();
        }

        // Fetch positions for each day
        $monthPositions = [];
        foreach ($monthDates as $md) {
            $pos = PlanetaryPosition::forDate($md)->orderBy('body')->get();
            if ($pos->isNotEmpty()) {
                $monthPositions[$md] = $pos;
            }
        }

        if (empty($monthPositions)) {
            $this->error("No planetary positions found for {$monthStart->format('F Y')}.");
            return self::FAILURE;
        }

        // 1st available day as representative
        $firstDate    = array_key_first($monthPositions);
        $firstPos     = $monthPositions[$firstDate];
        $firstPlanets = $firstPos->keyBy('body');

        $natalPlanets = $profile->natalChart?->planets ?? [];
        $houseCusps   = $profile->natalChart?->houses ?? [];

        // ── Transit-to-natal: 30-day merge ───────────────────────────────
        // Personal natal planets only; fast planets require >= 14 active days.
        $aspectWeights = [
            'trine' => +2, 'sextile' => +1, 'conjunction' => +1,
            'semi_sextile' => 0, 'quincunx' => -1, 'square' => -2, 'opposition' => -2,
        ];

        $bestByKey  = []; // key => ['asp' => ..., 'date' => ...]
        $countByKey = []; // key => number of days active

        foreach ($monthDates as $dayDate) {
            $dayPos = $monthPositions[$dayDate] ?? null;
            if (! $dayPos) { continue; }

            $dayTransit = $dayPos->map(fn ($p) => [
                'body'          => $p->body,
                'longitude'     => $p->longitude,
                'speed'         => $p->speed,
                'sign'          => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            foreach ($calculator->transitToNatal($dayTransit, $natalPlanets) as $asp) {
                // Focus on natal personal planets (Sun–Mars)
                if (! in_array($asp['natal_body'], self::PERSONAL_BODIES, true)) {
                    continue;
                }
                $k = $asp['transit_body'] . '_' . $asp['aspect'] . '_' . $asp['natal_body'];
                $countByKey[$k] = ($countByKey[$k] ?? 0) + 1;
                if (! isset($bestByKey[$k]) || $asp['orb'] < $bestByKey[$k]['asp']['orb']) {
                    $bestByKey[$k] = ['asp' => $asp, 'date' => $dayDate];
                }
            }
        }

        // Filter: fast planets only if active >= 14 days; slow always pass
        $filtered = [];
        foreach ($bestByKey as $k => $item) {
            $isFast = $item['asp']['transit_body'] < 5;
            if ($isFast && ($countByKey[$k] ?? 0) < 14) {
                continue;
            }
            $item['days'] = $countByKey[$k] ?? 1;
            $filtered[$k] = $item;
        }

        // Sort: slow first, then fast; within each group by orb
        usort($filtered, function ($a, $b) {
            $aSlow = $a['asp']['transit_body'] >= 5 ? 0 : 1;
            $bSlow = $b['asp']['transit_body'] >= 5 ? 0 : 1;
            if ($aSlow !== $bSlow) { return $aSlow - $bSlow; }
            return $a['asp']['orb'] <=> $b['asp']['orb'];
        });

        $transitNatalAspects = array_slice(array_values($filtered), 0, 15);
        $plainAspects        = array_map(fn ($item) => $item['asp'], $transitNatalAspects);

        // Notable retrogrades (Mercury–Saturn) based on 1st of month
        $retrogrades = $firstPos
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        // ── Detect lunations (both NM and FM) ────────────────────────────
        $lunations = $this->detectLunations($monthPositions);

        // Resolve natal house for each lunation
        foreach ($lunations as &$lun) {
            $lun['house'] = $this->findHouse($lun['longitude'], $houseCusps);
        }
        unset($lun);

        // ── Progressed Moon ───────────────────────────────────────────────
        $progressedMoon = $this->calculateProgressedMoon(
            $profile, $monthStart, $natalPlanets, $houseCusps
        );

        // ── Key dates ────────────────────────────────────────────────────
        $keyDates = $this->buildKeyDates($transitNatalAspects, $lunations);

        $monthLabel = $monthStart->format('F Y');

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ MONTHLY HOROSCOPE', '[' . $monthLabel . ']  ')));
        $this->put($this->row('  ' . $monthStart->format('j F') . ' – ' . $monthEnd->format('j F Y')));
        $this->put($this->row('  ' . ($profile->name ?? $profile->user?->name ?? 'Profile #' . $profile->id)));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center('NATAL + TRANSIT BI-WHEEL · ' . $monthLabel)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Transit subtitle + Rx legend ─────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->transitSubtitle($firstPlanets)));
        $this->put($this->row('  * Rx = Retrograde · as of 1 ' . $monthStart->format('F Y')));
        $this->put($this->row(''));

        // ── Transit planet list (1st of month, no degrees) ───────────────
        foreach ($this->transitLines($firstPos->all()) as $line) {
            $this->put($this->row($line));
        }

        // ── AI synthesis ─────────────────────────────────────────────────
        if ($this->option('ai')) {
            $assembledTexts = $this->collectTexts($plainAspects, $retrogrades, $simplified);
            $moonSignIdx    = (int) floor(($firstPlanets->get(PlanetaryPosition::MOON)?->longitude ?? 0) / 30);
            $moonSignName   = PlanetaryPosition::SIGN_NAMES[$moonSignIdx] ?? '';
            $synthesis      = $this->generateSynthesis(
                $assembledTexts,
                $natalPlanets,
                $monthStart,
                $monthEnd,
                $moonSignName,
                $simplified,
                $profile->id,
            );
            if ($synthesis) {
                $this->put($this->divider());
                $this->put($this->row($this->spread('  ✦  MONTH OVERVIEW', 'AI  ')));
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
                $asp      = $item['asp'];
                $peakDate = $item['date'];
                $tBody    = $asp['transit_body'];
                $nBody    = $asp['natal_body'];
                $tGlyph   = self::BODY_GLYPHS[$tBody] ?? '?';
                $nGlyph   = self::BODY_GLYPHS[$nBody] ?? '?';
                $tName    = PlanetaryPosition::BODY_NAMES[$tBody] ?? '';
                $nName    = PlanetaryPosition::BODY_NAMES[$nBody] ?? '';
                $aGlyph   = self::ASPECT_GLYPHS[$asp['aspect']] ?? $asp['aspect'];
                $aLabel   = ucfirst(str_replace('_', ' ', $asp['aspect']));
                $key      = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
                $block    = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);

                // Fast planets: show peak day; slow: no day label
                $dayLabel = ($tBody < 5 && $peakDate)
                    ? '  · peak ' . Carbon::parse($peakDate)->format('j M')
                    : '';
                $heading  = '· ' . $tGlyph . ' ' . $tName . '  ' . $aGlyph . ' ' . $aLabel
                          . '  ' . $nGlyph . ' natal ' . $nName . $dayLabel;

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
                $signIdx   = (int) floor($p->longitude / 30);
                $signName  = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
                $signGlyph = self::SIGN_GLYPHS[$signIdx] ?? '';
                $bodyName  = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
                $bodyGlyph = self::BODY_GLYPHS[$p->body] ?? '';
                $rxKey     = strtolower($bodyName) . '_rx_' . strtolower($signName);
                $block     = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);

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

        // ── Progressed Moon ───────────────────────────────────────────────
        if ($progressedMoon) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ○  PROGRESSED MOON', '')));
            $this->put($this->row(''));
            $this->put($this->row('  ' . $progressedMoon['line']));
            if (! empty($progressedMoon['notes'])) {
                $this->put($this->row(''));
                foreach ($progressedMoon['notes'] as $note) {
                    foreach ($this->wrap($note, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        }

        // ── Lunations ────────────────────────────────────────────────────
        if (! empty($lunations)) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  🌙  LUNATIONS', '')));

            foreach ($lunations as $lun) {
                $this->put($this->row(''));
                $lunDate  = Carbon::parse($lun['date'])->format('l, j M');
                $lunLabel = $lun['emoji'] . '  ' . $lun['name'] . ' in ' . $lun['sign'];
                $this->put($this->row($this->spread('  ' . $lunLabel, $lunDate . '  ')));

                // Sign text
                $lunSignKey   = strtolower(str_replace(' ', '_', $lun['type'])) . '_in_' . strtolower($lun['sign']);
                $lunSignBlock = TextBlock::pick($lunSignKey, $simplified ? 'lunation_sign_short' : 'lunation_sign', 1);
                if ($lunSignBlock) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($lunSignBlock->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }

                // Natal house text (personalised)
                if (! empty($lun['house'])) {
                    $houseKey     = strtolower(str_replace(' ', '_', $lun['type'])) . '_house_' . $lun['house'];
                    $houseSection = $simplified ? 'lunation_house_short' : 'lunation_house';
                    $houseBlock   = TextBlock::pick($houseKey, $houseSection, 1);
                    if ($houseBlock) {
                        $this->put($this->row(''));
                        $this->put($this->row('  H' . $lun['house'] . ' — ' . $this->houseName($lun['house'])));
                        $this->put($this->row(''));
                        foreach ($this->wrap(trim(strip_tags($houseBlock->text)), self::IW - 4) as $line) {
                            $this->put($this->row('    ' . $line));
                        }
                    }
                }
            }

            $this->put($this->row(''));
        }

        // ── Key dates ────────────────────────────────────────────────────
        if (! empty($keyDates)) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  📅  KEY DATES', '')));
            $this->put($this->row(''));
            foreach ($keyDates as $kd) {
                $dateStr = Carbon::parse($kd['date'])->format('D j M');
                $this->put($this->row('  · ' . $dateStr . '  —  ' . $kd['label']));
            }
            $this->put($this->row(''));
        }

        // ── Areas of life (house-based, 30-day average) ──────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ★  AREAS OF LIFE', '')));
        $this->put($this->row(''));

        $catScoreSums = array_fill(0, count(self::CATEGORIES), 0);
        $catScoreDays = 0;

        foreach ($monthDates as $dayDate) {
            $dayPos = $monthPositions[$dayDate] ?? null;
            if (! $dayPos) { continue; }

            $dayTransit = $dayPos->map(fn ($p) => [
                'body'          => $p->body,
                'longitude'     => $p->longitude,
                'speed'         => $p->speed,
                'sign'          => (int) floor($p->longitude / 30),
                'is_retrograde' => $p->is_retrograde,
            ])->values()->all();

            $dayRxBodies = $dayPos
                ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
                ->pluck('body')->toArray();

            $dayAspects = $calculator->transitToNatal($dayTransit, $natalPlanets);

            $nbs = [];
            foreach ($dayAspects as $asp) {
                $baseW     = $aspectWeights[$asp['aspect']] ?? 0;
                $maxOrb    = self::MAX_ORBS[$asp['aspect']] ?? 8.0;
                $orbFactor = max(0.0, 1.0 - ($asp['orb'] / $maxOrb));
                $nbs[$asp['natal_body']] = ($nbs[$asp['natal_body']] ?? 0) + $baseW * $orbFactor;
            }
            foreach ($dayRxBodies as $body) {
                $nbs[$body] = ($nbs[$body] ?? 0) - 1;
            }

            foreach (self::CATEGORIES as $i => $cat) {
                $score      = 0;
                $rulerCount = 0;
                foreach ($cat['houses'] as $hIdx) {
                    $cuspLon = $houseCusps[$hIdx] ?? null;
                    if ($cuspLon === null) { continue; }
                    $signIdx   = (int) floor(fmod($cuspLon, 360) / 30);
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
            // max_score = 4.0: one trine = 75% → ★★★★★; two trines = 100%
            $score100 = max(0, min(100, (int) round(50.0 + ($avgScore / 4.0) * 50.0)));
            if ($score100 >= 75) {
                $rating = '★★★★★  ';
            } elseif ($score100 >= 55) {
                $rating = '★★★★☆  ';
            } elseif ($score100 >= 42) {
                $rating = '★★★☆☆  ';
            } elseif ($score100 >= 30) {
                $rating = '★★☆☆☆  ';
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
            '  monthly  ·  ' . $monthLabel
            . '  ·  ' . count($transitNatalAspects) . ' transits'
            . ($rxLabel ? '  ·  ' . $rxLabel : '')
        ));
        $this->put($this->bottom());

        $this->newLine();

        return self::SUCCESS;
    }

    // ── Lunation detection (all NM + FM in the month) ────────────────────

    private function detectLunations(array $monthPositions): array
    {
        $lunations = [];
        $prev      = null;
        $found     = []; // track 'new_moon' / 'full_moon' already found (one per month)

        foreach ($monthPositions as $date => $positions) {
            $planets = $positions->keyBy('body');
            $sun     = $planets->get(PlanetaryPosition::SUN);
            $moon    = $planets->get(PlanetaryPosition::MOON);
            if (! $sun || ! $moon) { $prev = null; continue; }

            $elong = fmod(($moon->longitude - $sun->longitude + 360), 360);

            // New moon: elongation < 20° (or wrap from >340°)
            if (
                ! isset($found['new_moon']) &&
                ($elong < 20 || ($prev !== null && $prev > 340 && $elong < 40))
            ) {
                $signIdx             = (int) floor($sun->longitude / 30);
                $found['new_moon']   = true;
                $lunations[$date]    = [
                    'date'      => $date,
                    'type'      => 'new_moon',
                    'name'      => 'New Moon',
                    'emoji'     => '🌑',
                    'sign'      => PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                    'longitude' => $sun->longitude,
                    'house'     => null,
                ];
            } elseif (! isset($found['full_moon']) && $elong >= 170 && $elong <= 190) {
                $signIdx             = (int) floor($moon->longitude / 30);
                $found['full_moon']  = true;
                $lunations[$date]    = [
                    'date'      => $date,
                    'type'      => 'full_moon',
                    'name'      => 'Full Moon',
                    'emoji'     => '🌕',
                    'sign'      => PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '',
                    'longitude' => $moon->longitude,
                    'house'     => null,
                ];
            }

            $prev = $elong;
        }

        return array_values($lunations);
    }

    // ── Find natal house number (1-indexed) for a given longitude ─────────

    private function findHouse(float $longitude, array $houseCusps): ?int
    {
        if (count($houseCusps) < 12) { return null; }

        $lon = fmod($longitude + 360, 360);

        for ($h = 0; $h < 12; $h++) {
            $cusp     = fmod($houseCusps[$h] + 360, 360);
            $nextCusp = fmod($houseCusps[($h + 1) % 12] + 360, 360);

            if ($cusp <= $nextCusp) {
                if ($lon >= $cusp && $lon < $nextCusp) {
                    return $h + 1;
                }
            } else {
                // Cusp straddles 0° (e.g. 355° → 5°)
                if ($lon >= $cusp || $lon < $nextCusp) {
                    return $h + 1;
                }
            }
        }

        return 1;
    }

    // ── House name labels ─────────────────────────────────────────────────

    private function houseName(int $house): string
    {
        return [
             1 => 'Self & Identity',
             2 => 'Resources & Values',
             3 => 'Communication & Learning',
             4 => 'Home & Family',
             5 => 'Creativity & Romance',
             6 => 'Health & Service',
             7 => 'Partnerships',
             8 => 'Transformation',
             9 => 'Expansion & Beliefs',
            10 => 'Career & Reputation',
            11 => 'Community & Goals',
            12 => 'Inner Life & Solitude',
        ][$house] ?? '';
    }

    // ── Progressed Moon (secondary progressions, mean motion) ────────────
    // 1 day after birth = 1 year of life; Moon moves ~13.18°/year.
    // Show only when: entering new sign, new house, or exact aspect (orb <= 1°).

    private function calculateProgressedMoon(
        Profile $profile,
        Carbon  $monthStart,
        array   $natalPlanets,
        array   $houseCusps,
    ): ?array {
        $birthDate = $profile->birth_date;
        if (! $birthDate) { return null; }

        // Find natal Moon longitude
        $natalMoonLon = null;
        foreach ($natalPlanets as $p) {
            if (($p['body'] ?? -1) === 1) {
                $natalMoonLon = (float) ($p['longitude'] ?? 0);
                break;
            }
        }
        if ($natalMoonLon === null) { return null; }

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
        $houseChange = $houseCusps && $houseNow !== $housePrev;

        // Exact aspects to natal planets (orb <= 1°)
        $exactAspects = [];
        foreach ($natalPlanets as $np) {
            $nLon = (float) ($np['longitude'] ?? 0);
            $diff = fmod(abs($progLonNow - $nLon) + 360, 360);
            if ($diff > 180) { $diff = 360 - $diff; }
            foreach (self::ASPECT_ANGLES as $angle => $aspName) {
                $orb = abs($diff - $angle);
                if ($orb <= 1.0) {
                    $nName  = PlanetaryPosition::BODY_NAMES[$np['body'] ?? -1] ?? '';
                    $nGlyph = self::BODY_GLYPHS[$np['body'] ?? -1] ?? '';
                    $aGlyph = self::ASPECT_GLYPHS[$aspName] ?? '';
                    $aLabel = ucfirst(str_replace('_', ' ', $aspName));
                    $exactAspects[] = '○ Progressed Moon ' . $aGlyph . ' ' . $aLabel
                                    . '  ' . $nGlyph . ' natal ' . $nName;
                }
            }
        }

        // Nothing notable — skip the section
        if (! $signChange && ! $houseChange && empty($exactAspects)) {
            return null;
        }

        $signName  = PlanetaryPosition::SIGN_NAMES[$signNow] ?? '';
        $signGlyph = self::SIGN_GLYPHS[$signNow] ?? '';
        $deg       = number_format(fmod($progLonNow, 30), 1);
        $housePart = $houseNow ? '  H' . $houseNow : '';
        $line      = '○ Progressed Moon in ' . $signGlyph . ' ' . $signName . ' ' . $deg . '°' . $housePart;

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

        return ['line' => $line, 'notes' => $notes];
    }

    // ── Key dates: peak day of each selected top-10 aspect + lunations ───
    // Same approach as weekly: peak day per already-selected transit, orb < 1.0°.

    private function buildKeyDates(array $transitNatalAspects, array $lunations): array
    {
        $dates = [];

        // Lunations first
        foreach ($lunations as $lun) {
            $dates[$lun['date']][] = $lun['emoji'] . ' ' . $lun['name'] . ' in ' . $lun['sign'];
        }

        // Peak day per selected aspect (only if orb < 1.0°)
        foreach ($transitNatalAspects as $item) {
            $asp      = $item['asp'];
            $peakDate = $item['date'];
            if ($asp['orb'] > 1.0 || ! $peakDate) { continue; }

            $tName  = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName  = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $tGlyph = self::BODY_GLYPHS[$asp['transit_body']] ?? '';
            $nGlyph = self::BODY_GLYPHS[$asp['natal_body']] ?? '';
            $aGlyph = self::ASPECT_GLYPHS[$asp['aspect']] ?? '';
            $aLabel = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $dates[$peakDate][] = $tGlyph . ' ' . $tName . ' ' . $aGlyph . ' ' . $aLabel
                                 . ' ' . $nGlyph . ' natal ' . $nName;
        }

        if (empty($dates)) { return []; }

        ksort($dates);

        $result = [];
        foreach ($dates as $date => $labels) {
            $unique   = array_unique($labels);
            $result[] = ['date' => $date, 'label' => implode('  ·  ', $unique)];
        }
        return $result;
    }

    // ── Collect TextBlock texts for AI synthesis ──────────────────────────

    private function collectTexts(array $aspects, $retrogrades, bool $simplified): array
    {
        $texts = [];

        foreach ($aspects as $asp) {
            $tName   = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName   = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $aspWord = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $key     = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
            $block   = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);
            if ($block) {
                $texts[] = "[Transit {$tName} {$aspWord} natal {$nName}]\n" . trim(strip_tags($block->text));
            }
        }

        foreach ($retrogrades as $p) {
            $signIdx  = (int) floor($p->longitude / 30);
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $bodyName = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
            $rxKey    = strtolower($bodyName) . '_rx_' . strtolower($signName);
            $block    = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);
            if ($block) {
                $texts[] = "[{$bodyName} Retrograde in {$signName}]\n" . trim(strip_tags($block->text));
            }
        }

        return $texts;
    }

    // ── AI synthesis ──────────────────────────────────────────────────────

    private function generateSynthesis(
        array  $assembledTexts,
        array  $natalPlanets,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $moonSignName,
        bool   $simplified = false,
        int    $profileId  = 0,
    ): ?string {
        $cacheKey = 'monthly_' . $profileId . '_' . $monthStart->format('Y-m') . ($simplified ? '_short' : '');
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
            if (! in_array($np['body'] ?? -1, [0, 1], true)) { continue; }
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Month: {$monthStart->format('F Y')}\n";
        $prompt .= "Moon sign at month start: {$moonSignName}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($assembledTexts) {
            $prompt .= "\nThe following pre-generated descriptions will be shown to the person below this intro:\n\n";
            $prompt .= implode("\n\n", $assembledTexts);
        }
        $prompt .= $simplified
            ? "\n\nWrite exactly 1 paragraph (2–3 sentences) as a short monthly horoscope intro capturing the key theme."
            : "\n\nWrite exactly 3 paragraphs as a monthly horoscope intro that synthesizes and introduces what follows.";

        $paragraphRule = $simplified
            ? '- 1 paragraph only — 2–3 sentences total'
            : "- Exactly 3 paragraphs separated by blank lines — no headers, no bullets, no lists\n- Each paragraph: 3–4 sentences";

        $system = "You are writing a personalized monthly horoscope intro for a single person.\n\n"
            . "Style rules:\n"
            . "- Write like a psychologist giving honest feedback — not an astrologer\n"
            . "- Address the person as \"you\" (gender-neutral, no he/she)\n"
            . "{$paragraphRule}\n"
            . "- Short, simple sentences — one idea per sentence, no dashes, no semicolons\n"
            . ($simplified ? "- Cut all filler — every sentence states a fact or concrete action\n" : '')
            . "- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon\n"
            . "- Describe what the person actually notices or does in real situations — concrete behaviour only\n"
            . ($simplified ? '' : "- First paragraph: the overall theme of the month based on the sky\n- Second paragraph: the personal angle — what these transits activate for this specific person\n- Third paragraph: practical focus — key period or what to pay attention to this month\n")
            . "- Do NOT start with \"This month...\", \"This is...\", or \"With [planet]...\"\n"
            . "- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces\n"
            . "- No metaphors. No poetic language.\n"
            . "- No HTML — plain text only";

        try {
            $response = $ai->generate($prompt, $system, maxTokens: 800);
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

    // ── Transit display helpers ───────────────────────────────────────────

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

    /** Two-column planet list, no degrees (positions change daily). */
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

    // ── Bi-wheel placeholder ─────────────────────────────────────────────

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

    // ── Box-drawing helpers ──────────────────────────────────────────────

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
        if ($len >= self::IW) { return $text; }
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
        if ($len >= $width) { return mb_substr($str, 0, $width); }
        return $str . str_repeat(' ', $width - $len);
    }

    private function wrap(string $text, int $width): array
    {
        $text  = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        $lines = [];
        while (mb_strlen($text) > $width) {
            $pos = mb_strrpos(mb_substr($text, 0, $width), ' ');
            if ($pos === false) { $pos = $width; }
            $lines[] = mb_substr($text, 0, $pos);
            $text    = ltrim(mb_substr($text, $pos));
        }
        if ($text !== '') { $lines[] = $text; }
        return $lines ?: [''];
    }

    private function put(string $line): void
    {
        $this->line($line);
    }
}
