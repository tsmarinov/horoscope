<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use App\Services\AspectCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the universal Planet Positions page (4.10).
 *
 * No birth data required — shows all transit planet positions and
 * transit-to-transit aspects for a given date, with orbs.
 *
 * This is a public/SEO page: no personal layer, no AI synthesis.
 */
class UiPlanetPositions extends Command
{
    protected $signature = 'horoscope:ui-planet-positions
                            {--date= : Date to show (YYYY-MM-DD, default: today)}';

    protected $description = 'Render planet positions + transit aspects for a date (universal, no birth data)';

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

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(AspectCalculator $calculator): int
    {
        $date   = $this->option('date') ?: now()->toDateString();
        $carbon = Carbon::parse($date);

        $positions = PlanetaryPosition::forDate($date)->orderBy('body')->get();

        if ($positions->isEmpty()) {
            $this->error("No planetary positions found for {$date}.");
            return self::FAILURE;
        }

        // Build planet arrays for AspectCalculator
        $transitPlanets = $positions->map(fn ($p) => [
            'body'          => $p->body,
            'longitude'     => $p->longitude,
            'speed'         => $p->speed,
            'sign'          => (int) floor($p->longitude / 30),
            'is_retrograde' => $p->is_retrograde,
        ])->values()->all();

        $aspects = $calculator->transitToTransit($transitPlanets);
        usort($aspects, fn ($a, $b) => $a['orb'] <=> $b['orb']);

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread(
            "  \u{2609} PLANET POSITIONS",
            $carbon->format('j F Y') . '  '
        )));
        $prev = $carbon->copy()->subDay()->format('j M');
        $next = $carbon->copy()->addDay()->format('j M');
        $this->put($this->row($this->spread(
            "  \u{2190} {$prev}",
            "{$next} \u{2192}  "
        )));
        $this->put($this->divider());

        // ── Transit wheel placeholder ─────────────────────────────────────
        $this->put($this->row($this->center('TRANSIT WHEEL · ' . $date)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->divider());

        // ── Day note (top 2 aspects) ──────────────────────────────────────
        $noteLines = $this->buildDayNote($aspects);
        if ($noteLines) {
            foreach ($noteLines as $line) {
                $this->put($this->row('  ' . $line));
            }
            $this->put($this->row(''));
            $this->put($this->divider());
        }

        // ── Planet table ─────────────────────────────────────────────────
        $this->put($this->row($this->spread(
            "  \u{25C6}  TRANSIT PLANETS",
            $date . " \u{00B7} 12:00 UTC  "
        )));
        $this->put($this->row(''));

        foreach ($positions as $pos) {
            $glyph    = self::BODY_GLYPHS[$pos->body] ?? '?';
            $name     = PlanetaryPosition::BODY_NAMES[$pos->body] ?? '';
            $signIdx  = (int) floor($pos->longitude / 30);
            $signG    = self::SIGN_GLYPHS[$signIdx] ?? '';
            $signName = PlanetaryPosition::SIGN_NAMES[$signIdx] ?? '';
            $degInSign = fmod($pos->longitude, 30);
            $deg      = (int) $degInSign;
            $min      = (int) round(($degInSign - $deg) * 60);
            $degStr   = sprintf("%d\u{00B0}%02d'", $deg, $min);
            $rx       = $pos->is_retrograde ? '  Rx' : '';

            $left  = "  {$glyph} {$name}";
            $right = "{$signG} {$signName}  {$degStr}{$rx}  ";
            $this->put($this->row($this->spread($left, $right)));
        }
        $this->put($this->row(''));

        // ── Transit aspects ───────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row("  \u{25C6}  TRANSIT ASPECTS"));
        $this->put($this->row(''));

        if (empty($aspects)) {
            $this->put($this->row('    No significant aspects today.'));
        } else {
            foreach ($aspects as $asp) {
                $gA      = self::BODY_GLYPHS[$asp['body_a']] ?? '?';
                $gB      = self::BODY_GLYPHS[$asp['body_b']] ?? '?';
                $nameA   = PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '';
                $nameB   = PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '';
                $aspG    = self::ASPECT_GLYPHS[$asp['aspect']] ?? "\u{00B7}";
                $aspWord = ui_trans('aspects.' . $asp['aspect']) ?: ucfirst(str_replace('_', ' ', $asp['aspect']));
                $orb     = number_format($asp['orb'], 1) . "\u{00B0}";

                $chip = "  {$gA} {$nameA}  {$aspG} {$aspWord}  {$gB} {$nameB}  \u{00B7} {$orb}";
                $this->put($this->row($chip));

                // Load text from transit section
                $key   = strtolower($nameA) . '_' . $asp['aspect'] . '_' . strtolower($nameB);
                $block = TextBlock::pick($key, 'transit', 1);
                $text  = $block ? trim(strip_tags($block->text)) : null;

                if ($text) {
                    $this->put($this->row(''));
                    foreach ($this->wrap($text, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
                $this->put($this->row(''));
            }
        }

        // ── Footer ───────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxPlanets = $positions->filter(fn ($p) => $p->is_retrograde)
            ->map(fn ($p) => (self::BODY_GLYPHS[$p->body] ?? '') . 'Rx')
            ->implode(" \u{00B7} ");
        $rxStr = $rxPlanets ?: ui_trans('no_rx');
        $this->put($this->row("  planet-positions  \u{00B7}  {$date}  \u{00B7}  {$rxStr}"));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Day note (1-2 sentences from top 2 aspects) ───────────────────

    private function buildDayNote(array $aspects): array
    {
        $sentences = [];
        foreach (array_slice($aspects, 0, 2) as $asp) {
            $nameA = PlanetaryPosition::BODY_NAMES[$asp['body_a']] ?? '';
            $nameB = PlanetaryPosition::BODY_NAMES[$asp['body_b']] ?? '';
            $key   = strtolower($nameA) . '_' . $asp['aspect'] . '_' . strtolower($nameB);
            $block = TextBlock::pick($key, 'transit', 1);
            if ($block) {
                // Take first sentence only for the day note
                $text = trim(strip_tags($block->text));
                $end  = strpos($text, '.') ?: strlen($text);
                $sentences[] = rtrim(mb_substr($text, 0, $end + 1));
            }
        }

        if (empty($sentences)) {
            return [];
        }

        return $this->wrap(implode(' ', $sentences), self::IW - 2);
    }

    // ── Transit wheel placeholder ────────────────────────────────────────

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

    // ── Box-drawing helpers ──────────────────────────────────────────────

    private function top(): string
    {
        return "\u{250C}" . str_repeat("\u{2500}", self::W - 2) . "\u{2510}";
    }

    private function bottom(): string
    {
        return "\u{2514}" . str_repeat("\u{2500}", self::W - 2) . "\u{2518}";
    }

    private function divider(): string
    {
        return "\u{251C}" . str_repeat("\u{2500}", self::W - 2) . "\u{2524}";
    }

    private function row(string $content): string
    {
        return "\u{2502} " . $this->mbPad($content, self::IW) . " \u{2502}";
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
