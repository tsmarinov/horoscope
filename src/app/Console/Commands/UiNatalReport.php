<?php

namespace App\Console\Commands;

use App\Enums\ReportMode;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
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
                            {profile? : Profile ID}
                            {--user=  : User email (default: test@horo.test)}
                            {--demo=  : Demo profile slug}
                            {--birth-date= : Anonymous guest birth date (YYYY-MM-DD)}
                            {--mode=organic : Report mode: organic / simplified / ai_l1 / ai_l1_haiku}
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

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(ReportBuilder $builder): int
    {
        /** @var Profile|null $subject */
        $subject = $this->resolveSubject();

        if ($subject === null) {
            $this->error('Subject not found. Use --user, --demo, or --birth-date.');
            return self::FAILURE;
        }

        $mode     = ReportMode::from($this->option('mode'));
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
            $this->spread('  ☽ NATAL CHART REPORT', '[' . $report->mode->value . ']  ')
        ));
        $this->put($this->row('  ' . $this->subjectLine($subject)));
        $this->put($this->divider());

        // ── Natal wheel (centered placeholder) ───────────────────────────
        $this->put($this->row($this->center('НАТАЛНА КАРТА')));
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

        // Split into 3 groups (support both full and _short section variants)
        $ascSections     = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_ascendant')));
        $posSections     = array_values(array_filter($report->sections, fn ($s) => str_starts_with($s->section, 'natal_positions')));
        $aspectSections  = array_values(array_filter($report->sections, fn ($s) => !str_starts_with($s->section, 'natal_ascendant') && !str_starts_with($s->section, 'natal_positions')));
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
                $this->put($this->row($this->spread(
                    '  ' . $toneTag . '  ' . $heading,
                    $section->tone . '  ',
                )));

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
        if ($id = $this->argument('profile')) {
            return Profile::find($id);
        }

        if ($slug = $this->option('demo')) {
            return Profile::where('slug', $slug)->where('is_demo', true)->first();
        }

        if ($birthDate = $this->option('birth-date')) {
            // Transient (unsaved) profile — chart is computed but not persisted
            return new Profile(['birth_date' => $birthDate]);
        }

        $email = trim($this->option('user') ?: 'test@horo.test');
        $user  = \App\Models\User::where('email', $email)->first();

        if ($user === null) {
            $this->warn("No user found for email: {$email}");
            return null;
        }

        $profile = $user->profile;

        if ($profile === null) {
            $this->warn("User {$email} has no profile yet.");
        }

        return $profile;
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
            $parts[] = self::BODY_GLYPHS[0] . ' Sun ' . self::SIGN_GLYPHS[$p['sign']] . ' ' . $sign;
        }

        // Moon (1)
        if ($p = $indexed->get(1)) {
            $sign    = PlanetaryPosition::SIGN_NAMES[$p['sign']] ?? '';
            $retro   = ($p['is_retrograde'] ?? false) ? ' Rx' : '';
            $parts[] = self::BODY_GLYPHS[1] . ' Moon ' . self::SIGN_GLYPHS[$p['sign']] . ' ' . $sign . $retro;
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
            $col[] = $glyph . ' ' . $name . ' ' . $sg . ' ' . $sign . $house . $retro;
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
        $name = $subject->name
            ?? ($subject->isFull() ? $subject->user?->email : null)
            ?? 'Anonymous Guest';

        $parts = array_filter([
            $name,
            'Born ' . $subject->getBirthDate(),
            $subject->getBirthTime(),
            $subject->getBirthCity()?->name,
            'Tier ' . $subject->getChartTier(),
        ]);

        return implode('  ·  ', $parts);
    }

    private function put(string $line): void
    {
        $this->line($line);
    }
}
