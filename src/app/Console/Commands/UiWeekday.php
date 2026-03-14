<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\Horoscope\WeekdayHoroscopeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the "Days of the Week" reference page.
 *
 * All business logic lives in WeekdayHoroscopeService.
 * This command handles only rendering.
 */
class UiWeekday extends Command
{
    protected $signature = 'horoscope:ui-weekday
                            {--profile=? : Profile ID (optional, for personalized clothing tip)}
                            {--date= : Date to highlight as "today" (YYYY-MM-DD, default: today)}';

    protected $description = 'Render the Days of the Week reference page in pseudo-browser console UI';

    private const W  = 72;
    private const IW = 68;

    private const RULER_GLYPHS = [
        'Moon'    => '🌙',
        'Mars'    => '♂',
        'Mercury' => '☿',
        'Jupiter' => '♃',
        'Venus'   => '♀',
        'Saturn'  => '♄',
        'Sun'     => '☀️',
    ];

    public function handle(WeekdayHoroscopeService $service): int
    {
        $date = $this->option('date') ?? Carbon::today()->toDateString();

        $profile = null;
        if ($profileId = $this->option('profile')) {
            $profile = Profile::find($profileId);
            if (! $profile) {
                $this->error("Profile #{$profileId} not found.");
                return self::FAILURE;
            }
        }

        $dto = $service->build($profile, $date);

        // ── Render ────────────────────────────────────────────────────────────
        $this->line('┌' . str_repeat('─', self::W - 2) . '┐');
        $this->put($this->center('📅  DAYS OF THE WEEK'));
        $label = Carbon::parse($date)->format('l, j F Y')
               . ($dto->profileName ? ' · ' . $dto->profileName : '');
        $this->put($this->center($label));
        $this->line($this->divider());

        foreach ($dto->days as $iso => $day) {
            $this->put('');

            $todayMark = $day->isToday ? '  ← today' : '';
            $icon      = self::RULER_GLYPHS[$day->ruler] ?? $day->ruler;
            $header    = $icon . '  ' . mb_strtoupper($day->name)
                       . '  ·  ' . $day->ruler
                       . '  ·  № ' . $day->number
                       . $todayMark;
            $this->put($day->isToday ? $this->spread('  ' . $header, '') : '  ' . $header);

            $this->put('  🎨 ' . $day->colors . '   💎 ' . $day->gem);
            $this->put('  ' . $day->theme);
            $this->put('');

            foreach ($this->wrap($day->description, self::IW - 2) as $line) {
                $this->put('  ' . $line);
            }

            // Personalised clothing tip (today only)
            if ($day->clothingTipKey !== null) {
                $block = TextBlock::where('key', $day->clothingTipKey)
                    ->where('section', 'weekday_clothing')
                    ->where('language', 'en')
                    ->first();

                $this->put('');
                $signNames   = \App\Models\PlanetaryPosition::SIGN_NAMES;
                $venusName   = $signNames[$dto->natalVenusSign] ?? '';
                $this->put('  👗  Clothing & Jewelry  ·  Venus in ' . $venusName);
                if ($block) {
                    foreach ($this->wrap($block->text, self::IW - 4) as $line) {
                        $this->put('    ' . $line);
                    }
                } else {
                    $this->put('    [no text found for ' . $day->clothingTipKey . ']');
                }
            }

            $this->put('');

            if ($iso < 7) {
                $this->line($this->divider());
            }
        }

        // ── Footer ────────────────────────────────────────────────────────────
        $this->line($this->divider());
        $this->put($this->center('→ Lunar calendar   → Daily horoscope   → Weekly horoscope'));
        $this->line('└' . str_repeat('─', self::W - 2) . '┘');

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function divider(): string
    {
        return '├' . str_repeat('─', self::W - 2) . '┤';
    }

    private function put(string $line): void
    {
        $this->line($this->row($line));
    }

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
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';
        foreach ($words as $word) {
            $test = $line === '' ? $word : $line . ' ' . $word;
            if (mb_strlen($test) <= $width) {
                $line = $test;
            } else {
                if ($line !== '') { $lines[] = $line; }
                $line = $word;
            }
        }
        if ($line !== '') { $lines[] = $line; }
        return $lines;
    }
}
