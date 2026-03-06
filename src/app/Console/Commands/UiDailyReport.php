<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Pseudo-browser UI for the daily horoscope — no actual browser needed.
 *
 * Renders what the user would see on the daily horoscope page, as a
 * 72-char wide ASCII UI block in the console. Intended for development
 * and content layout testing without a running web server.
 *
 * Layout:
 *   ┌─ header (date) ────────────────────────────────────────────────┐
 *   │ BI-WHEEL placeholder (transit outer ring + natal inner)        │
 *   ├─ transit planet list ──────────────────────────────────────────┤
 *   │ synthesis intro (placeholder)                                  │
 *   │ key transit factors (retrograde planets + placeholder text)    │
 *   │ lunar day block                                                │
 *   │ tip of the day (placeholder)                                   │
 *   │ areas of life / categories grid                                │
 *   │ day meta (weekday · ruler · color · gem · number)             │
 *   └─ footer ───────────────────────────────────────────────────────┘
 */
class UiDailyReport extends Command
{
    protected $signature = 'horoscope:ui-daily
                            {profile : Profile ID}
                            {--date=       : Date to show (YYYY-MM-DD, default: today)}
                            {--simplified  : Show 1-sentence simplified texts (uses _short sections)}
                            {--ai          : Generate synthesis intro with Claude (AI L1)}';

    protected $description = 'Render a daily horoscope in pseudo-browser console UI';

    // ── Layout constants ─────────────────────────────────────────────────
    private const W  = 72;
    private const IW = 68;

    // ── Glyph maps ───────────────────────────────────────────────────────
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
        'conjunction'      => '☌', 'opposition'       => '☍',
        'trine'            => '△', 'square'           => '□',
        'sextile'          => '⚹', 'quincunx'         => '⚻',
        'semi_sextile'     => '∠', 'mutual_reception' => '⇌',
    ];

    // ── Day rulers: indexed by Carbon dayOfWeek (0=Sun … 6=Sat) ─────────
    private const DAY_RULERS = [
        0 => ['planet' => 'Sun',     'body' => 0, 'color' => 'gold, amber',         'gem' => 'amber, citrine',      'number' => 1],
        1 => ['planet' => 'Moon',    'body' => 1, 'color' => 'silver, pearl white', 'gem' => 'moonstone, pearl',    'number' => 2],
        2 => ['planet' => 'Mars',    'body' => 4, 'color' => 'red, crimson',        'gem' => 'ruby, garnet',        'number' => 9],
        3 => ['planet' => 'Mercury', 'body' => 2, 'color' => 'yellow, orange',      'gem' => 'citrine, topaz',      'number' => 5],
        4 => ['planet' => 'Jupiter', 'body' => 5, 'color' => 'blue, violet',        'gem' => 'sapphire, amethyst',  'number' => 3],
        5 => ['planet' => 'Venus',   'body' => 3, 'color' => 'green, rose pink',    'gem' => 'emerald, rose quartz','number' => 6],
        6 => ['planet' => 'Saturn',  'body' => 6, 'color' => 'black, dark navy',    'gem' => 'obsidian, onyx',      'number' => 8],
    ];

    // ── Moon phases ──────────────────────────────────────────────────────
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

    // ── Life categories ──────────────────────────────────────────────────
    private const CATEGORIES = [
        ['emoji' => '❤️',  'name' => 'Love',              'affected_by' => ['venus']],
        ['emoji' => '🏠',  'name' => 'Home',              'affected_by' => ['moon']],
        ['emoji' => '🎨',  'name' => 'Creativity',        'affected_by' => ['venus', 'sun']],
        ['emoji' => '🔮',  'name' => 'Spirituality',      'affected_by' => []],
        ['emoji' => '💚',  'name' => 'Health',            'affected_by' => ['mars']],
        ['emoji' => '💰',  'name' => 'Finance',           'affected_by' => ['venus', 'jupiter']],
        ['emoji' => '✈️',  'name' => 'Travel',            'affected_by' => ['jupiter', 'mercury']],
        ['emoji' => '💼',  'name' => 'Career',            'affected_by' => ['saturn', 'mars']],
        ['emoji' => '🌱',  'name' => 'New Beginnings',    'affected_by' => ['mercury']],
        ['emoji' => '💬',  'name' => 'Communication',     'affected_by' => ['mercury']],
        ['emoji' => '📝',  'name' => 'Contracts',         'affected_by' => ['mercury']],
    ];

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(AspectCalculator $calculator): int
    {
        $date       = $this->option('date') ?: now()->toDateString();
        $simplified = $this->option('simplified');

        $profile = Profile::find($this->argument('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        $positions = PlanetaryPosition::forDate($date)->orderBy('body')->get();

        if ($positions->isEmpty()) {
            $this->error("No planetary positions found for {$date}.");
            return self::FAILURE;
        }

        $planets   = $positions->keyBy('body');
        $carbon    = Carbon::parse($date);
        $ruler     = self::DAY_RULERS[$carbon->dayOfWeek];

        // Moon data
        $moon       = $planets->get(PlanetaryPosition::MOON);
        $sun        = $planets->get(PlanetaryPosition::SUN);
        $elongation   = fmod(($moon?->longitude ?? 0) - ($sun?->longitude ?? 0) + 360, 360);
        $lunarDay     = max(1, (int) ceil($elongation / (360 / 29.53)));
        [$moonEmoji, $moonPhaseName] = $this->moonPhase($elongation);
        $moonSignIdx  = (int) floor(($moon?->longitude ?? 0) / 30);
        $moonSignName = PlanetaryPosition::SIGN_NAMES[$moonSignIdx] ?? '';

        // Notable retrogrades (Mercury–Saturn)
        $retrogrades = $positions
            ->filter(fn ($p) => $p->is_retrograde && $p->body >= 2 && $p->body <= 6)
            ->values();

        $rxBodies = $retrogrades->pluck('body')->toArray();

        // Build transit planet arrays for AspectCalculator
        $transitPlanets = $positions->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();

        // Transit-to-natal aspects (top 5 by orb)
        $natalPlanets = $profile->natalChart?->planets ?? [];
        $transitNatalAspects = [];
        if (! empty($natalPlanets)) {
            $transitNatalAspects = array_slice(
                $calculator->transitToNatal($transitPlanets, $natalPlanets),
                0, 5
            );
        }

        // Transit-to-transit aspects (top 3 by orb, skip mutual_reception)
        $transitAspects = array_slice(
            array_filter(
                $calculator->transitToTransit($transitPlanets),
                fn ($a) => $a['aspect'] !== 'mutual_reception'
            ),
            0, 3
        );

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ DAILY HOROSCOPE', '[' . $date . ']  ')));
        $this->put($this->row('  ' . $carbon->format('l, j F Y')));
        $this->put($this->row('  Profile: ' . ($profile->name ?? 'Profile #' . $profile->id)));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center('NATAL + TRANSIT BI-WHEEL · ' . $date)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Transit subtitle (Sun · Moon · Mercury Rx) ───────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->transitSubtitle($planets)));
        $this->put($this->row(''));

        // ── Transit planet list ──────────────────────────────────────────
        foreach ($this->transitLines($positions->all()) as $line) {
            $this->put($this->row($line));
        }

        // ── Collect TextBlock texts for AI L1 synthesis ──────────────────
        // (pre-generated texts that will be shown in KEY TRANSIT FACTORS)
        $assembledTexts = [];

        foreach (array_slice($transitNatalAspects, 0, 5) as $asp) {
            $tName = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $aspWord = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $key   = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
            $block = TextBlock::pick($key, 'transit_natal', 1);
            if ($block) {
                $assembledTexts[] = "[Transit {$tName} {$aspWord} natal {$nName}]\n" . trim(strip_tags($block->text));
            }
        }
        foreach ($retrogrades as $p) {
            $signIdx  = (int) floor($p->longitude / 30);
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $bodyName = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
            $rxKey = strtolower($bodyName) . '_rx_' . strtolower($signName);
            $block = TextBlock::pick($rxKey, 'retrograde', 1);
            if ($block) {
                $assembledTexts[] = "[{$bodyName} Retrograde in {$signName}]\n" . trim(strip_tags($block->text));
            }
        }
        foreach (array_slice($transitAspects, 0, 3) as $asp) {
            $nameA = PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '';
            $nameB = PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '';
            $aspWord = ucfirst(str_replace('_', ' ', $asp['aspect']));
            $key   = strtolower($nameA) . '_' . $asp['aspect'] . '_' . strtolower($nameB);
            $block = TextBlock::pick($key, 'transit', 1);
            if ($block) {
                $assembledTexts[] = "[{$nameA} {$aspWord} {$nameB}]\n" . trim(strip_tags($block->text));
            }
        }

        // ── Synthesis (AI L1) ────────────────────────────────────────────
        if ($this->option('ai')) {
            $synthesis = $this->generateSynthesis(
                $assembledTexts,
                $natalPlanets,
                $carbon,
                $moonSignName,
                $moonPhaseName,
                $lunarDay,
            );
            if ($synthesis) {
                $this->put($this->divider());
                $this->put($this->row($this->spread('  ✦  TODAY\'S OVERVIEW', 'AI  ')));
                $this->put($this->row(''));
                foreach (preg_split('/\n{2,}/', trim($synthesis)) as $para) {
                    foreach ($this->wrap(trim($para), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                    $this->put($this->row(''));
                }
            }
        }

        // ── Key Transit Factors ──────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ◆  KEY TRANSIT FACTORS', '')));

        // 1. Transit-to-natal aspects
        foreach ($transitNatalAspects as $asp) {
            $tName    = PlanetaryPosition::BODY_NAMES[$asp['transit_body']] ?? '';
            $nName    = PlanetaryPosition::BODY_NAMES[$asp['natal_body']] ?? '';
            $tGlyph   = self::BODY_GLYPHS[$asp['transit_body']] ?? '?';
            $nGlyph   = self::BODY_GLYPHS[$asp['natal_body']] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp['aspect']] ?? '·';
            $aspWord  = ucfirst(str_replace('_', '-', $asp['aspect']));
            $orb      = number_format($asp['orb'], 1) . '°';

            $chip    = $tGlyph . ' ' . $tName . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $nGlyph . ' natal ' . $nName;
            $key     = 'transit_' . strtolower($tName) . '_' . $asp['aspect'] . '_natal_' . strtolower($nName);
            $section = $simplified ? 'transit_natal_short' : 'transit_natal';
            $block   = TextBlock::pick($key, $section, 1);
            $text  = $block ? trim(strip_tags($block->text)) : null;

            $this->put($this->row(''));
            $this->put($this->row($this->spread('  · ' . $chip, $orb . '  ')));
            if ($text) {
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        // 2. Retrograde planets
        foreach ($retrogrades as $p) {
            $signIdx  = (int) floor($p->longitude / 30);
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $bodyName = PlanetaryPosition::BODY_NAMES[$p->body] ?? '';
            $chip     = (self::BODY_GLYPHS[$p->body] ?? '?') . ' ' . $bodyName . ' Retrograde'
                      . '  ·  '
                      . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' ' . $signName;

            $rxKey = strtolower($bodyName) . '_rx_' . strtolower($signName);
            $block = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);
            $text  = $block ? trim(strip_tags($block->text)) : null;

            $this->put($this->row(''));
            $this->put($this->row('  · ' . $chip));
            if ($text) {
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        // 3. Transit-to-transit aspects
        foreach ($transitAspects as $asp) {
            $nameA    = PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '';
            $nameB    = PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '';
            $glyphA   = self::BODY_GLYPHS[$asp['body_a']] ?? '?';
            $glyphB   = self::BODY_GLYPHS[$asp['body_b']] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp['aspect']] ?? '·';
            $aspWord  = ucfirst(str_replace('_', '-', $asp['aspect']));
            $orb      = number_format($asp['orb'], 1) . '°';

            $chip  = $glyphA . ' ' . $nameA . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $glyphB . ' ' . $nameB;
            $key   = strtolower($nameA) . '_' . $asp['aspect'] . '_' . strtolower($nameB);
            $block = TextBlock::pick($key, $simplified ? 'transit_short' : 'transit', 1);
            $text  = $block ? trim(strip_tags($block->text)) : null;

            $this->put($this->row(''));
            $this->put($this->row($this->spread('  · ' . $chip, $orb . '  ')));
            if ($text) {
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        if (empty($transitNatalAspects) && $retrogrades->isEmpty() && empty($transitAspects)) {
            $this->put($this->row(''));
            $this->put($this->row('    No significant transit factors today.'));
        }

        $this->put($this->row(''));

        // ── Lunar block ──────────────────────────────────────────────────
        $this->put($this->divider());
        $moonSignG = self::SIGN_GLYPHS[$moonSignIdx] ?? '';
        $this->put($this->row(
            $this->spread(
                '  ' . $moonEmoji . '  LUNAR DAY ' . $lunarDay,
                $moonPhaseName . '  '
            )
        ));
        $this->put($this->row(''));
        $this->put($this->row(
            '  ' . $moonEmoji . ' Moon in ' . $moonSignG . ' ' . $moonSignName
            . '  ·  Day ' . $lunarDay . ' / 30  ·  ' . $moonPhaseName
        ));
        $lunarKey   = 'moon_in_' . strtolower($moonSignName);
        $lunarBlock = TextBlock::pick($lunarKey, 'lunar_day', 1);
        $lunarText  = $lunarBlock ? trim(strip_tags($lunarBlock->text)) : null;
        if ($lunarText) {
            foreach ($this->wrap($lunarText, self::IW - 4) as $line) {
                $this->put($this->row('    ' . $line));
            }
        } else {
            $this->put($this->row('    [no lunar_day text for ' . $lunarKey . ']'));
        }
        $this->put($this->row(''));

        // ── Tip of the day ───────────────────────────────────────────────
        $tipKey   = strtolower($carbon->format('l')) . '_moon_in_' . strtolower($moonSignName);
        $tipBlock = TextBlock::where('key', $tipKey)
            ->where('section', 'daily_tip')
            ->where('language', 'en')
            ->first();
        $this->put($this->divider());
        $this->put($this->row($this->spread('  💡  TIP OF THE DAY', 'Moon in ' . $moonSignName . '  ')));
        $this->put($this->row(''));
        if ($tipBlock) {
            foreach ($this->wrap(trim(strip_tags($tipBlock->text)), self::IW - 4) as $line) {
                $this->put($this->row('    ' . $line));
            }
        } else {
            $this->put($this->row('    [no daily_tip text for ' . $tipKey . ']'));
        }
        $this->put($this->row(''));

        // ── Areas of life ────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ★  AREAS OF LIFE', '')));
        $this->put($this->row(''));

        $mercuryRx = in_array(PlanetaryPosition::MERCURY, $rxBodies);
        $venusRx   = in_array(PlanetaryPosition::VENUS,   $rxBodies);
        $marsRx    = in_array(PlanetaryPosition::MARS,    $rxBodies);

        foreach (self::CATEGORIES as $cat) {
            $affected = in_array('mercury', $cat['affected_by']) && $mercuryRx
                     || in_array('venus',   $cat['affected_by']) && $venusRx
                     || in_array('mars',    $cat['affected_by']) && $marsRx;

            $rating   = $affected ? '⚠ wait  ' : '★★★     ';
            $this->put($this->row($this->spread('  ' . $cat['emoji'] . ' ' . $cat['name'], $rating)));
        }
        $this->put($this->row(''));

        // ── Day meta ─────────────────────────────────────────────────────
        $this->put($this->divider());
        $rg = self::BODY_GLYPHS[$ruler['body']] ?? '';
        $this->put($this->row(
            '  🗓 ' . $carbon->format('l')
            . '  ·  ' . $rg . ' ' . $ruler['planet']
            . '  ·  🎨 ' . $ruler['color']
        ));
        $this->put($this->row(
            '  💎 ' . $ruler['gem']
            . '  ·  🔢 ' . $ruler['number']
        ));

        // ── Clothing & Jewelry tip (natal Venus × weekday) ────────────────
        $venusSign = null;
        foreach ($profile->natalChart?->planets ?? [] as $pl) {
            if ($pl['body'] === 3) { $venusSign = $pl['sign']; break; }
        }
        if ($venusSign !== null) {
            $signNames   = PlanetaryPosition::SIGN_NAMES;
            $clothingKey = strtolower($carbon->format('l'))
                         . '_venus_in_' . strtolower($signNames[$venusSign] ?? '');
            $block = TextBlock::where('key', $clothingKey)
                ->where('section', 'weekday_clothing')
                ->where('language', 'en')
                ->first();
            if ($block) {
                $this->put($this->row(''));
                $this->put($this->row('  👗  Clothing & Jewelry  ·  Venus in ' . ($signNames[$venusSign] ?? '')));
                $this->put($this->row(''));
                foreach ($this->wrap(strip_tags($block->text), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }
        $this->put($this->row(''));

        // ── Footer ────────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxLabel = $retrogrades->count() > 0
            ? $retrogrades->map(fn ($p) => (PlanetaryPosition::BODY_NAMES[$p->body] ?? '?') . ' Rx')->implode(', ')
            : 'no Rx';
        $this->put($this->row('  daily  ·  ' . $date . '  ·  ' . $positions->count() . ' transits  ·  ' . $rxLabel));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── AI synthesis ─────────────────────────────────────────────────────

    private function generateSynthesis(
        array $assembledTexts,
        array $natalPlanets,
        Carbon $carbon,
        string $moonSignName,
        string $moonPhaseName,
        int $lunarDay,
    ): ?string {
        /** @var \App\Contracts\AiProvider $ai */
        $ai = app(\App\Contracts\AiProvider::class);

        // Natal Sun + Moon context
        $natalLines = [];
        foreach ($natalPlanets as $np) {
            if (! in_array($np['body'] ?? -1, [0, 1])) continue;
            $name = PlanetaryPosition::BODY_NAMES[$np['body']] ?? '';
            $sign = PlanetaryPosition::SIGN_NAMES[$np['sign']] ?? '';
            $natalLines[] = "natal {$name} in {$sign}";
        }

        $prompt  = "Date: {$carbon->format('l, j F Y')}\n";
        $prompt .= "Moon: {$moonSignName}, {$moonPhaseName}, lunar day {$lunarDay}\n";
        if ($natalLines) {
            $prompt .= "Natal context: " . implode(', ', $natalLines) . "\n";
        }
        if ($assembledTexts) {
            $prompt .= "\nThe following pre-generated descriptions will be shown to the person below this intro:\n\n";
            $prompt .= implode("\n\n", $assembledTexts);
        }
        $prompt .= "\n\nWrite exactly 2 paragraphs as a daily horoscope intro that synthesizes and introduces what follows.";

        $system = <<<SYSTEM
You are writing a personalized daily horoscope intro for a single person.

Style rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Exactly 2 paragraphs separated by a blank line — no headers, no bullets, no lists
- Each paragraph: 3–4 sentences
- Short, simple sentences — one idea per sentence, no dashes, no semicolons
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the person actually notices or does in real situations — concrete behaviour only
- First paragraph: the overall tone of the day based on the sky (moon, transits, retrogrades)
- Second paragraph: the personal angle — what these transits activate for this specific person today
- Do NOT start with "Today is...", "This is...", or "With [planet]..."
- Forbidden words: journey, path, soul, essence, portal, gateway, threshold, healing, wounds, dance, dissolves, energy, forces
- No metaphors. No poetic language.
- No HTML — plain text only
SYSTEM;

        try {
            $response = $ai->generate($prompt, $system, maxTokens: 500);
            $cost     = number_format($response->costUsd, 5);
            $this->line("  <fg=gray>[AI synthesis: {$response->inputTokens} in / {$response->outputTokens} out / \${$cost}]</>");
            return $response->text;
        } catch (\Exception $e) {
            $this->warn('AI synthesis failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Transit helpers ──────────────────────────────────────────────────

    private function transitSubtitle(Collection $planets): string
    {
        $parts = [];

        if ($sun = $planets->get(PlanetaryPosition::SUN)) {
            $signIdx = (int) floor($sun->longitude / 30);
            $parts[] = '☉ Sun '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '');
        }

        if ($moon = $planets->get(PlanetaryPosition::MOON)) {
            $signIdx = (int) floor($moon->longitude / 30);
            $retro   = $moon->is_retrograde ? ' Rx' : '';
            $parts[] = '☽ Moon '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '') . $retro;
        }

        if (($mercury = $planets->get(PlanetaryPosition::MERCURY)) && $mercury->is_retrograde) {
            $parts[] = '☿ Mercury Rx';
        }

        return implode('  ·  ', $parts);
    }

    /** Two-column transit planet list with degree within sign. */
    private function transitLines(array $positions): array
    {
        $col = [];
        foreach ($positions as $p) {
            $signIdx = (int) floor($p->longitude / 30);
            $deg     = number_format(fmod($p->longitude, 30), 1) . '°';
            $retro   = $p->is_retrograde ? ' Rx' : '';
            $col[]   = (self::BODY_GLYPHS[$p->body] ?? '?') . ' '
                     . (PlanetaryPosition::BODY_NAMES[$p->body] ?? '') . ' '
                     . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' '
                     . (PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '') . ' '
                     . $deg . $retro;
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

    private function moonPhase(float $elongation): array
    {
        foreach (self::MOON_PHASES as [$from, $to, $emoji, $name]) {
            if ($elongation >= $from && $elongation < $to) {
                return [$emoji, $name];
            }
        }
        return ['🌑', 'New Moon'];
    }

    // ── Bi-wheel placeholder ─────────────────────────────────────────────

    /**
     * Static ASCII placeholder for the natal + transit bi-wheel.
     * Outer ring = transits, inner ring = natal; rendered as SVG in the real UI.
     */
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
