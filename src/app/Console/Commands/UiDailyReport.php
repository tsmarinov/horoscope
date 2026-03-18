<?php

namespace App\Console\Commands;

use App\DataTransfer\Horoscope\DailyHoroscopeDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\Horoscope\DailyHoroscopeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
                            {--profile= : Profile ID}
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

    private const AREA_EMOJIS = [
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
    ];

    private const MOON_PHASE_EMOJIS = [
        'new_moon'        => '🌑',
        'waxing_crescent' => '🌒',
        'first_quarter'   => '🌓',
        'waxing_gibbous'  => '🌔',
        'full_moon'       => '🌕',
        'waning_gibbous'  => '🌖',
        'last_quarter'    => '🌗',
        'waning_crescent' => '🌘',
    ];

    // ── Entry point ──────────────────────────────────────────────────────

    public function handle(DailyHoroscopeService $service): int
    {
        $date       = $this->option('date') ?: now()->toDateString();
        $simplified = $this->option('simplified');

        $profile = Profile::find($this->option('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        $gender = TextBlock::resolveGender($profile->gender?->value ?? null);

        try {
            $dto = $service->build($profile, $date);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $carbon = Carbon::parse($date);

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ DAILY HOROSCOPE', '[' . $date . ']  ')));
        $this->put($this->row('  ' . $carbon->format('l, j F Y')));
        $this->put($this->row('  ' . $dto->profileName));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center('NATAL + TRANSIT BI-WHEEL · ' . $date)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Transit subtitle (Sun · Moon · Mercury Rx) ───────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->transitSubtitle($dto)));
        $this->put($this->row('  * Rx = Retrograde (apparent backward motion)'));
        $this->put($this->row(''));

        // ── Transit planet list ──────────────────────────────────────────
        foreach ($this->transitLines($dto) as $line) {
            $this->put($this->row($line));
        }

        // ── Synthesis (AI L1) ────────────────────────────────────────────
        if ($this->option('ai')) {
            $natalPlanets = $profile->natalChart?->planets ?? [];
            /** @var \App\Services\Ai\HoroscopeSynthesisService $synthesisService */
            $synthesisService = app(\App\Services\Ai\HoroscopeSynthesisService::class);
            $aiResponse = $synthesisService->daily(
                transitNatalAspects: $dto->transitNatalAspects,
                retrogrades:         $dto->retrogrades,
                areasOfLife:         $dto->areasOfLife,
                natalPlanets:        $natalPlanets,
                date:                $carbon,
                moonSignName:        $dto->moon->signName,
                moonPhaseName:       $dto->moon->phaseName,
                lunarDay:            $dto->moon->lunarDay,
                simplified:          $simplified,
                profileId:           $profile->id,
                gender:              $gender,
            );
            if ($aiResponse) {
                $wasCached = $aiResponse->inputTokens === 0;
                if ($wasCached) {
                    $this->line("  <fg=gray>[AI synthesis: cached]</>");
                } else {
                    $cost = number_format($aiResponse->costUsd, 5);
                    $this->line("  <fg=gray>[AI synthesis: {$aiResponse->inputTokens} in / {$aiResponse->outputTokens} out / \${$cost}]</>");
                }
                $synthesis = $aiResponse->text;
            }
            if ($synthesis ?? null) {
                $this->put($this->divider());
                $this->put($this->row($this->spread('  ✦  ' . ui_trans('daily.ai_overview', $gender), 'AI  ')));
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
        foreach ($dto->transitNatalAspects as $asp) {
            $tGlyph   = self::BODY_GLYPHS[$asp->transitBody] ?? '?';
            $nGlyph   = self::BODY_GLYPHS[$asp->natalBody] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? '·';
            $aspWord  = ui_trans('aspects.' . $asp->aspect, $gender) ?: ucfirst(str_replace('_', ' ', $asp->aspect));

            $chip    = $tGlyph . ' ' . $asp->transitName . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $nGlyph . ' natal ' . $asp->natalName;
            $key     = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $section = $simplified ? 'transit_natal_short' : 'transit_natal';
            $block   = TextBlock::pickForProfile($key, $section, 'en', $gender, $profile->id);
            $text    = $block ? trim(strip_tags($block->text)) : null;

            $this->put($this->row(''));
            $this->put($this->row('  · ' . $chip));
            if ($text) {
                $this->put($this->row(''));
                foreach ($this->wrap($text, self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }

        // 2. Retrograde planets
        foreach ($dto->retrogrades as $rx) {
            $chip = (self::BODY_GLYPHS[$rx->body] ?? '?') . ' ' . $rx->name . ' ' . ui_trans('retrograde.label', $gender)
                  . '  ·  in '
                  . (self::SIGN_GLYPHS[$rx->signIndex] ?? '') . ' ' . $rx->signName;

            $rxKey = strtolower($rx->name) . '_rx_' . strtolower($rx->signName);
            $block = TextBlock::pickForProfile($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 'en', $gender, $profile->id);
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
        foreach ($dto->transitTransitAspects as $asp) {
            $glyphA   = self::BODY_GLYPHS[$asp->bodyA] ?? '?';
            $glyphB   = self::BODY_GLYPHS[$asp->bodyB] ?? '?';
            $aspGlyph = self::ASPECT_GLYPHS[$asp->aspect] ?? '·';
            $aspWord  = ui_trans('aspects.' . $asp->aspect, $gender) ?: ucfirst(str_replace('_', ' ', $asp->aspect));

            $chip  = $glyphA . ' ' . $asp->nameA . '  ' . $aspGlyph . ' ' . $aspWord . '  ' . $glyphB . ' ' . $asp->nameB;
            $key   = strtolower($asp->nameA) . '_' . $asp->aspect . '_' . strtolower($asp->nameB);
            $block = TextBlock::pickForProfile($key, $simplified ? 'transit_short' : 'transit', 'en', $gender, $profile->id);
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

        if (empty($dto->transitNatalAspects) && empty($dto->retrogrades) && empty($dto->transitTransitAspects)) {
            $this->put($this->row(''));
            $this->put($this->row('    No significant transit factors today.'));
        }

        $this->put($this->row(''));

        // ── Lunar block ──────────────────────────────────────────────────
        $this->put($this->divider());
        $moonSignG = self::SIGN_GLYPHS[$dto->moon->signIndex] ?? '';
        $phaseEmoji = self::MOON_PHASE_EMOJIS[$dto->moon->phaseSlug] ?? '🌑';
        $this->put($this->row(
            $this->spread(
                '  ' . $phaseEmoji . '  LUNAR DAY ' . $dto->moon->lunarDay,
                $dto->moon->phaseName . '  '
            )
        ));
        $this->put($this->row(''));
        $this->put($this->row(
            '  ' . $phaseEmoji . ' Moon in ' . $moonSignG . ' ' . $dto->moon->signName
            . '  ·  Day ' . $dto->moon->lunarDay . ' / 30  ·  ' . $dto->moon->phaseName
        ));
        $lunarKey   = 'moon_in_' . strtolower($dto->moon->signName);
        $lunarBlock = TextBlock::pickForProfile($lunarKey, $simplified ? 'lunar_day_short' : 'lunar_day', 'en', $gender, $profile->id);
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
        $tipKey   = strtolower($carbon->format('l')) . '_moon_in_' . strtolower($dto->moon->signName);
        $tipBlock = TextBlock::where('key', $tipKey)
            ->where('section', $simplified ? 'daily_tip_short' : 'daily_tip')
            ->where('language', 'en')
            ->where('gender', $gender)
            ->first();
        $this->put($this->divider());
        $this->put($this->row($this->spread('  💡  TIP OF THE DAY', 'Moon in ' . $dto->moon->signName . '  ')));
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

        foreach ($dto->areasOfLife as $area) {
            $emoji = self::AREA_EMOJIS[$area->slug] ?? '';
            $this->put($this->row($this->spread('  ' . $emoji . ' ' . $area->name, $this->ratingDisplay($area->rating, $area->maxRating))));
        }
        $this->put($this->row(''));

        // ── Day meta ─────────────────────────────────────────────────────
        $this->put($this->divider());
        $rg = self::BODY_GLYPHS[$dto->dayRuler->body] ?? '';
        $this->put($this->row(
            '  🗓 ' . $dto->dayRuler->weekday
            . '  ·  ' . $rg . ' ' . $dto->dayRuler->planet
            . '  ·  🎨 ' . $dto->dayRuler->color
        ));
        $this->put($this->row(
            '  💎 ' . $dto->dayRuler->gem
            . '  ·  🔢 ' . $dto->dayRuler->number
        ));

        // ── Clothing & Jewelry tip ───────────────────────────────────────
        if ($dto->natalVenusSign !== null) {
            $signNames   = PlanetaryPosition::SIGN_NAMES;
            $clothingKey = strtolower($carbon->format('l'))
                         . '_venus_in_' . strtolower($signNames[$dto->natalVenusSign] ?? '');
            $block = TextBlock::where('key', $clothingKey)
                ->where('section', $simplified ? 'weekday_clothing_short' : 'weekday_clothing')
                ->where('language', 'en')
                ->where('gender', $gender)
                ->first();
            if ($block) {
                $this->put($this->row(''));
                $this->put($this->row('  👗  Clothing & Jewelry  ·  Venus in ' . ($signNames[$dto->natalVenusSign] ?? '')));
                $this->put($this->row(''));
                foreach ($this->wrap(strip_tags($block->text), self::IW - 4) as $line) {
                    $this->put($this->row('    ' . $line));
                }
            }
        }
        $this->put($this->row(''));

        // ── Footer ────────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxLabel = ! empty($dto->retrogrades)
            ? implode(', ', array_map(fn ($rx) => $rx->name . ' Rx', $dto->retrogrades))
            : 'no Rx';
        $this->put($this->row('  daily  ·  ' . $date . '  ·  ' . count($dto->positions) . ' transits  ·  ' . $rxLabel));
        $this->put($this->bottom());
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Transit helpers ──────────────────────────────────────────────────

    private function transitSubtitle(DailyHoroscopeDTO $dto): string
    {
        $parts = [];
        foreach ($dto->positions as $pos) {
            if ($pos->body === PlanetaryPosition::SUN) {
                $parts[] = '☉ Sun in '
                         . (self::SIGN_GLYPHS[$pos->signIndex] ?? '') . ' '
                         . $pos->signName;
            }
            if ($pos->body === PlanetaryPosition::MOON) {
                $retro = $pos->isRetrograde ? ' Rx' : '';
                $parts[] = '☽ Moon in '
                         . (self::SIGN_GLYPHS[$pos->signIndex] ?? '') . ' '
                         . $pos->signName . $retro;
            }
            if ($pos->body === PlanetaryPosition::MERCURY && $pos->isRetrograde) {
                $parts[] = '☿ Mercury Rx';
            }
        }
        return implode('  ·  ', $parts);
    }

    private function transitLines(DailyHoroscopeDTO $dto): array
    {
        $col = [];
        foreach ($dto->positions as $pos) {
            $retro = $pos->isRetrograde ? ' Rx' : '';
            $col[] = (self::BODY_GLYPHS[$pos->body] ?? '?') . ' '
                   . $pos->name . ' in '
                   . (self::SIGN_GLYPHS[$pos->signIndex] ?? '') . ' '
                   . $pos->signName . $retro;
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

    private function ratingDisplay(int $rating, int $maxRating): string
    {
        if ($rating === 0) {
            return ui_trans('rating_wait') . '  ';
        }
        return str_repeat('★', $rating) . str_repeat('☆', $maxRating - $rating) . '  ';
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
