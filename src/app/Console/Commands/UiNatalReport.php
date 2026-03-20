<?php

namespace App\Console\Commands;

use App\Enums\ReportMode;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\ReportBuilder;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the natal report — no actual browser needed.
 *
 * Renders exactly what the user would see on the natal chart page,
 * but as a 72-char wide ASCII UI block in the console. Intended for
 * development / content testing without a running web server.
 *
 * Layout:
 *   ┌─ header (title + subject) ─────────────────────────────────────┐
 *   │ NATAL WHEEL (placeholder) │ PLANET LIST                        │
 *   ├─ full-width sections ──────────────────────────────────────────┤
 *   │ aspect sections with text blocks (or AI text)                  │
 *   └─ footer ───────────────────────────────────────────────────────┘
 */
class UiNatalReport extends Command
{
    protected $signature = 'horoscope:ui-natal-report
                            {--profile=? : Profile ID (default: 1)}
                            {--demo=  : Demo profile slug}
                            {--birth-date= : Anonymous guest birth date (YYYY-MM-DD)}
                            {--simplified  : Show simplified texts (uses _short sections)}
                            {--ai          : Generate AI portrait (ai_l1; combine with --simplified for ai_l1_haiku)}
                            {--lang=en : Language code}
                            {--generate : Generate report if not cached (may call AI and cost money)}';

    protected $description = 'Render a natal chart report in pseudo-browser console UI';

    // ── Layout constants ─────────────────────────────────────────────────
    private const W  = 72;  // total line width (including border chars │)
    private const IW = 68;  // single-col inner width: W - 4 (two │ + two spaces)
    private const LC = 29;  // left column inner content width (wheel)
    private const RC = 36;  // right column inner content width (planets)
    // Verify: row2 = │+sp+LC+sp+│+sp+RC+sp+│ = 1+1+LC+1+1+1+RC+1+1 = LC+RC+7 = 72 ✓
    // Dividers: 1 + (LC+2) + 1 + (RC+2) + 1 = LC+RC+7 = 72 ✓

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
        'conjunction'      => '☌', 'opposition'   => '☍',
        'trine'            => '△', 'square'        => '□',
        'sextile'          => '⚹', 'quincunx'     => '⚻',
        'semi_sextile'     => '∠', 'mutual_reception' => '⇌',
    ];

    private const SIGN_ELEMENTS = [
        0 => 'fire',  1 => 'earth', 2 => 'air',   3 => 'water',
        4 => 'fire',  5 => 'earth', 6 => 'air',   7 => 'water',
        8 => 'fire',  9 => 'earth', 10 => 'air',  11 => 'water',
    ];

    private const ELEMENT_LABELS = [
        'fire' => 'Fire', 'earth' => 'Earth', 'air' => 'Air', 'water' => 'Water',
    ];

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(ReportBuilder $builder): int
    {
        /** @var Profile|null $subject */
        $subject = $this->resolveSubject();

        if ($subject === null) {
            $this->error('Subject not found. Use a profile ID, --demo, or --birth-date.');
            return self::FAILURE;
        }

        $mode = match(true) {
            $this->option('ai') && $this->option('simplified') => ReportMode::AiL1Haiku,
            $this->option('ai')                                 => ReportMode::AiL1,
            $this->option('simplified')                         => ReportMode::Simplified,
            default                                             => ReportMode::Organic,
        };
        $language = $this->option('lang');

        $isAiMode = $mode->isAi();

        if ($isAiMode && ! $this->option('generate')) {
            $report = $builder->loadCached($subject, $mode, $language);
            if ($report === null) {
                $this->warn('No cached report found. Run with --generate to create one.');
                return self::FAILURE;
            }
        } else {
            $report = $builder->buildNatalReport($subject, $mode, $language);
        }

        $chart    = $report->chart;

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row(
            $this->spread('  ☽ NATAL CHART REPORT', '')
        ));
        $name     = $subject->name ?? $subject->user?->name ?? 'Anonymous Guest';
        $born     = 'Born ' . $subject->getBirthDate()
                  . ($subject->getBirthTime() ? '  ·  ' . $subject->getBirthTime() : '');
        $cityName = $subject->getBirthCity()?->name ?? '—';
        $this->put($this->row($this->spread("  {$name}", "{$born}  ")));
        $this->put($this->row("  {$cityName}"));
        $this->put($this->divider());

        // ── Natal wheel (centered placeholder) ───────────────────────────
        $this->put($this->row($this->center('NATAL CHART')));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Subtitle line: ASC · Sun · Moon ──────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->chartSubtitle($chart->planets ?? [], $chart->ascendant)));
        $this->put($this->row(''));
        foreach ($this->planetListLines($chart->planets ?? []) as $line) {
            $this->put($this->row($line));
        }
        $this->put($this->row(''));
        $this->put($this->row('  * Rx = Retrograde (apparent backward motion)'));

        // Split into 4 groups (support both full and _short section variants)
        $ascSections              = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_ascendant')));
        $posSections              = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_positions')));
        $houseLordPreSections     = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_house_lords') && !str_starts_with($s->section, 'natal_house_lord_aspects')));
        $houseLordAspectSections  = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_house_lord_aspects')));
        $angleAspectSections      = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_angles')));
        $aspectSections           = array_values(array_filter($report->sections, fn ($s) =>
            !str_starts_with($s->section, 'natal_ascendant') &&
            !str_starts_with($s->section, 'natal_positions') &&
            !str_starts_with($s->section, 'natal_house_lords') &&
            !str_starts_with($s->section, 'natal_house_lord_aspects') &&
            !str_starts_with($s->section, 'natal_angles')
        ));
        $sectionCount    = count($report->sections);

        if ($sectionCount === 0) {
            $this->put($this->divider());
            $this->put($this->row(''));
            $this->put($this->row('  (no text blocks yet — run text generation first)'));
            $this->put($this->row(''));
        }

        // ── AI Introduction ───────────────────────────────────────────────
        if ($report->introduction !== null) {
            $this->put($this->divider());
            foreach ($this->renderHtml($report->introduction, self::IW - 4, '    ') as $line) {
                $this->put($this->row($line));
            }
        }

        // ── Ascendant ─────────────────────────────────────────────────────
        if (count($ascSections) > 0) {
            $this->put($this->divider());
            foreach ($ascSections as $section) {
                $text    = trim(strip_tags($section->text));
                $heading = $this->sectionHeading($section->key, $chart->planets ?? []);
                $this->put($this->row($this->spread('  ◉  ASCENDANT  ·  ' . $heading, '')));
                if ($text === '') continue;
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
            $this->put($this->row(''));
        }
        $gender = TextBlock::resolveGender($subject->gender?->value ?? null);
        $this->renderSingletonSection($chart->planets ?? [], $mode === ReportMode::Simplified, $gender, $subject->exists ? $subject->id : null);

        // ── House Lords (pre-generated) ───────────────────────────────────
        if (count($houseLordPreSections) > 0) {
            $houseLabels = [
                1  => 'ASC — Self & Identity',
                2  => '2nd House — Money & Resources',
                3  => '3rd House — Communication & Short Travel',
                4  => '4th House — Home & Family',
                5  => '5th House — Creativity & Romance',
                6  => '6th House — Work & Health',
                7  => '7th House — Partnerships',
                8  => '8th House — Transformation & Shared Resources',
                9  => '9th House — Philosophy & Long Travel',
                10 => '10th House — Career & Public Life',
                11 => '11th House — Friends & Aspirations',
                12 => '12th House — Hidden Matters & Solitude',
            ];
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ◎  HOUSE LORDS', '')));
            foreach ($houseLordPreSections as $section) {
                // Extract house number from key: house_{N}_cusp_...
                preg_match('/^house_(\d+)_/', $section->key, $m);
                $houseNum = isset($m[1]) ? (int) $m[1] : null;
                $label    = $houseLabels[$houseNum] ?? ('House ' . ($houseNum ?? '?'));
                $text     = trim(strip_tags($section->text));
                $this->put($this->row(''));
                $this->put($this->row('  · ' . $label));
                if ($text !== '') {
                    foreach ($this->wrap($text, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        } elseif (empty($chart->houses)) {
            $this->put($this->divider());
            $this->put($this->row('  🔒 House Lords — add birth time & place to unlock'));
            $this->put($this->row(''));
        }

        // ── AI House Lords ────────────────────────────────────────────────
        if (isset($report->houseLords) && $report->houseLords !== null) {
            $houseLordsData = json_decode($report->houseLords, true);
            if (is_array($houseLordsData)) {
                $houseLabels = [
                    2  => '2nd House — Money & Resources',
                    3  => '3rd House — Communication & Short Travel',
                    4  => '4th House — Home & Family',
                    5  => '5th House — Creativity & Romance',
                    6  => '6th House — Work & Health',
                    7  => '7th House — Partnerships',
                    8  => '8th House — Transformation & Shared Resources',
                    9  => '9th House — Philosophy & Long Travel',
                    10 => '10th House — Career & Public Life',
                    11 => '11th House — Friends & Aspirations',
                    12 => '12th House — Hidden Matters & Solitude',
                ];
                $this->put($this->divider());
                $this->put($this->row($this->spread('  ◎  HOUSE LORDS', '')));
                foreach ($houseLordsData as $hl) {
                    $houseNum = $hl['house'] ?? null;
                    $label    = $houseLabels[$houseNum] ?? ('House ' . ($houseNum ?? '?'));
                    $text     = trim(strip_tags($hl['text'] ?? ''));
                    $this->put($this->row(''));
                    $this->put($this->row('  · ' . $label));
                    if ($text !== '') {
                        foreach ($this->wrap($text, self::IW - 4) as $line) {
                            $this->put($this->row('    ' . $line));
                        }
                    }
                }
                $this->put($this->row(''));
            }
        }

        // ── Planet Positions ──────────────────────────────────────────────
        if (count($posSections) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ◎  PLANET POSITIONS', '')));
            foreach ($posSections as $section) {
                $text    = trim(strip_tags($section->text));
                $heading = $this->sectionHeading($section->key, $chart->planets ?? []);
                $this->put($this->row(''));
                $this->put($this->row('  · ' . $heading));
                if ($text === '') continue;
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
            $this->put($this->row(''));
        }

        // ── House Lord Aspects ────────────────────────────────────────────
        if (count($houseLordAspectSections) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ◎  HOUSE LORD ASPECTS', '')));
            foreach ($houseLordAspectSections as $section) {
                $heading = $this->sectionHeading($section->key, $chart->planets ?? []);
                $toneTag = match ($section->tone) {
                    'positive' => '▲',
                    'negative' => '▼',
                    default    => '◆',
                };
                $text = trim(strip_tags($section->text));
                $this->put($this->row(''));
                $this->put($this->row('  ' . $toneTag . '  ' . $heading));
                if ($text !== '') {
                    foreach ($this->wrap($text, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        }

        // ── Angle Aspects ─────────────────────────────────────────────────────
        if (count($angleAspectSections) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ✦  ASPECTS TO ANGLES', '')));

            foreach ($angleAspectSections as $section) {
                $heading = $this->sectionHeading($section->key, $chart->planets ?? []);
                $toneTag = match ($section->tone) {
                    'positive' => '▲',
                    'negative' => '▼',
                    default    => '◆',
                };
                $text = trim(strip_tags($section->text));
                $this->put($this->row(''));
                $this->put($this->row('  ' . $toneTag . '  ' . $heading));
                if ($text !== '') {
                    foreach ($this->wrap($text, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        }

        // ── Aspects ───────────────────────────────────────────────────────
        if (count($aspectSections) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ✦  ASPECTS', '')));

            foreach ($aspectSections as $section) {
                $planets  = $chart->planets ?? [];
                $heading  = $this->sectionHeading($section->key, $planets);
                $toneTag  = match ($section->tone) {
                    'positive' => '▲',
                    'negative' => '▼',
                    default    => '◆',
                };

                $this->put($this->row(''));
                $this->put($this->row('  ' . $toneTag . '  ' . $heading));

                $text = trim(strip_tags($section->text));
                if ($text !== '') {
                    foreach ($this->wrap($text, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }

                if ($section->transition !== null) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(strip_tags($section->transition), self::IW - 8) as $line) {
                        $this->put($this->row('      → ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        }

        // ── Forecast links ────────────────────────────────────────────────
        $this->put($this->divider());
        foreach (['→ Daily forecast', '→ Weekly forecast', '→ Monthly forecast', '→ Yearly forecast', '→ Lunar calendar'] as $link) {
            $this->put($this->row('  ' . $link));
        }

        // ── Footer ────────────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row(
            "  {$report->mode->value}  ·  {$report->language}  ·  {$sectionCount} sections"
        ));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Subject resolution ───────────────────────────────────────────────

    private function resolveSubject(): ?Profile
    {
        if ($id = $this->option('profile')) {
            return Profile::find($id);
        }

        if ($slug = $this->option('demo')) {
            return Profile::where('slug', $slug)->where('is_demo', true)->first();
        }

        if ($birthDate = $this->option('birth-date')) {
            // Transient (unsaved) profile — chart is computed but not persisted
            return new Profile(['birth_date' => $birthDate]);
        }

        return Profile::find(1);
    }

    // ── Natal wheel placeholder ──────────────────────────────────────────

    /**
     * Static ASCII wheel showing the 12 zodiac signs in a circular arrangement.
     * Clockwise from top: ♈ ♉ ♊ (right side) ♋ ♌ ♍ (bottom) ♎ ♏ ♐ (left) ♑ ♒ ♓ (top-left).
     * Planet positions are NOT rendered here — a real interactive wheel is a UI concern.
     */
    private function wheelLines(): array
    {
        return [
            '         · ♈ ·        ',
            '      ♓   ·   ·   ♉   ',
            '    ♒   ·       ·   ♊ ',
            '   ♑  ·   · ✦ ·  ·  ♋ ',
            '    ♐   ·       ·   ♌ ',
            '      ♏   ·   ·   ♍   ',
            '         · ♎ ·        ',
        ];
    }

    // ── Chart helpers ────────────────────────────────────────────────────

    /** "ASC ♈ Aries  ·  ☉ Sun ♊ Gemini  ·  ☽ Moon ♓ Pisces" subtitle line. */
    private function chartSubtitle(array $planets, ?float $ascendant): string
    {
        $indexed = collect($planets)->keyBy('body');
        $parts   = [];

        // ASC
        if ($ascendant !== null) {
            $signIdx = (int) floor($ascendant / 30);
            $sign    = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $parts[] = 'ASC ' . (self::SIGN_GLYPHS[$signIdx] ?? '') . ' ' . $sign;
        }

        // Sun (0)
        if ($p = $indexed->get(0)) {
            $sign    = PlanetaryPosition::SIGN_NAMES[$p['sign']] ?? '';
            $parts[] = self::BODY_GLYPHS[0] . ' Sun in ' . self::SIGN_GLYPHS[$p['sign']] . ' ' . $sign;
        }

        // Moon (1)
        if ($p = $indexed->get(1)) {
            $sign    = PlanetaryPosition::SIGN_NAMES[$p['sign']] ?? '';
            $retro   = ($p['is_retrograde'] ?? false) ? ' Rx' : '';
            $parts[] = self::BODY_GLYPHS[1] . ' Moon in ' . self::SIGN_GLYPHS[$p['sign']] . ' ' . $sign . $retro;
        }

        return implode('  ·  ', $parts);
    }

    /** Full planet list — all bodies with sign, house, Rx flag. Two per line. */
    private function planetListLines(array $planets): array
    {
        $lines = [];
        $col   = [];

        foreach ($planets as $p) {
            $glyph = self::BODY_GLYPHS[$p['body']] ?? '?';
            $name  = PlanetaryPosition::BODY_NAMES[$p['body']] ?? '';
            $sign  = PlanetaryPosition::SIGN_NAMES[$p['sign']] ?? '';
            $sg    = self::SIGN_GLYPHS[$p['sign']] ?? '';
            $house = $p['house'] ? ' H' . $p['house'] : '';
            $retro = ($p['is_retrograde'] ?? false) ? ' Rx' : '';
            $deg   = (int) ($p['degree'] ?? 0);
            $min   = (int) round((($p['degree'] ?? 0) - $deg) * 60);
            $col[] = $glyph . ' ' . $name . ' ' . $deg . "\u{00B0}" . sprintf('%02d', $min) . "' " . $sg . ' ' . $sign . $house . $retro;
        }

        // Two columns
        $count = count($col);
        for ($i = 0; $i < $count; $i += 2) {
            $left  = $col[$i] ?? '';
            $right = $col[$i + 1] ?? '';
            if ($right !== '') {
                $pad   = max(1, 34 - mb_strlen($left));
                $lines[] = '  ' . $left . str_repeat(' ', $pad) . $right;
            } else {
                $lines[] = '  ' . $left;
            }
        }

        return $lines;
    }

    /** Human-readable heading for a section key, e.g. "sun_trine_moon" → "☉ Sun △ ☽ Moon". */
    private function sectionHeading(string $key, array $planets): string
    {
        $indexed = collect($planets)->keyBy('body');

        // Map body name (lowercase) → body id for glyph lookup
        $nameToId = collect(PlanetaryPosition::BODY_NAMES)
            ->mapWithKeys(fn ($name, $id) => [strtolower($name) => $id]);

        // Merge compound aspect tokens before iterating
        $raw    = explode('_', $key);
        $tokens = [];
        for ($i = 0; $i < count($raw); $i++) {
            $compound = $raw[$i] . '_' . ($raw[$i + 1] ?? '');
            if (isset(self::ASPECT_GLYPHS[$compound])) {
                $tokens[] = $compound;
                $i++;
            } else {
                $tokens[] = $raw[$i];
            }
        }
        $rendered = [];

        foreach ($tokens as $token) {
            if (isset(self::ASPECT_GLYPHS[$token])) {
                $word       = ucfirst(str_replace('_', '-', $token));
                $rendered[] = self::ASPECT_GLYPHS[$token] . ' ' . $word;
            } elseif ($nameToId->has($token)) {
                $bodyId     = $nameToId->get($token);
                $glyph      = self::BODY_GLYPHS[$bodyId] ?? '';
                $name       = PlanetaryPosition::BODY_NAMES[$bodyId] ?? ucfirst($token);
                $rendered[] = $glyph . ' ' . $name;
            } else {
                $rendered[] = ucfirst($token);
            }
        }

        return implode(' ', $rendered);
    }

    // ── Box-drawing helpers ──────────────────────────────────────────────

    // ── Singleton / Missing element ──────────────────────────────────────

    private function renderSingletonSection(array $planets, bool $simplified = false, ?string $gender = null, ?int $profileId = null): void
    {
        // Count planets per element — only bodies 0–9 (Sun–Pluto)
        $elements = ['fire' => [], 'earth' => [], 'air' => [], 'water' => []];
        foreach ($planets as $p) {
            $body = (int) ($p['body'] ?? -1);
            if ($body < 0 || $body > 9) continue;
            $el = self::SIGN_ELEMENTS[(int) ($p['sign'] ?? 0)] ?? null;
            if ($el) $elements[$el][] = $p;
        }

        $section = $simplified ? 'singleton_short' : 'singleton';

        foreach ($elements as $element => $list) {
            $count = count($list);
            if ($count !== 0 && $count !== 1) continue;

            $label = self::ELEMENT_LABELS[$element];

            if ($count === 1) {
                $body   = (int) $list[0]['body'];
                $pGlyph = self::BODY_GLYPHS[$body] ?? '';
                $pName  = PlanetaryPosition::BODY_NAMES[$body] ?? '';
                $header = "  \u{2605}  Singleton: {$pGlyph} {$pName} ({$label})";
                $key    = 'singleton_' . $element;
            } else {
                $header = "  \u{25CB}  Missing element: {$label}";
                $key    = 'missing_' . $element;
            }

            $this->put($this->divider());
            $this->put($this->row($header));
            $block = TextBlock::pickForProfile($key, $section, 'en', $gender, $profileId);
            if ($block) {
                $this->put($this->row(''));
                foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
            $this->put($this->row(''));
        }
    }

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

    /** Single-column bordered row. Content truncated/padded to IW. */
    private function row(string $content): string
    {
        return '│ ' . $this->mbPad($content, self::IW) . ' │';
    }

    /** Center $text within IW. */
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

    /** Left-align $left, right-align $right within $width (default IW). */
    private function spread(string $left, string $right, int $width = self::IW): string
    {
        $gap = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return $left . str_repeat(' ', $gap) . $right;
    }

    /** mb_str_pad — pad string to $width using display length. */
    private function mbPad(string $str, int $width): string
    {
        $len = mb_strlen($str);
        if ($len >= $width) {
            return mb_substr($str, 0, $width);
        }
        return $str . str_repeat(' ', $width - $len);
    }

    /**
     * Render HTML text as wrapped console lines with paragraph breaks.
     * Preserves <p> / \n\n paragraph structure; strips all other tags.
     */
    private function renderHtml(string $html, int $width, string $indent = ''): array
    {
        // Normalise paragraph breaks: </p> and \n\n → sentinel
        $text = preg_replace('/<\/p\s*>/i', "\n\n", $html) ?? $html;
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);

        $paragraphs = preg_split('/\n{2,}/', trim($text)) ?? [trim($text)];

        $lines = [''];
        foreach ($paragraphs as $para) {
            $para = trim(preg_replace('/[ \t]+/', ' ', $para) ?? $para);
            if ($para === '') continue;
            foreach ($this->wrap($para, $width) as $line) {
                $lines[] = $indent . $line;
            }
            $lines[] = '';
        }

        return $lines;
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

    private function prettyKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    private function subjectLine(Profile $subject): string
    {
        $name = $subject->name ?? $subject->user?->name ?? 'Anonymous Guest';

        $parts = array_filter([
            $name,
            'Born ' . $subject->getBirthDate(),
            $subject->getBirthTime(),
            $subject->getBirthCity()?->name,
        ]);

        return implode('  ·  ', $parts);
    }

    private function put(string $line): void
    {
        $this->line($line);
    }
}
