<?php

namespace App\Console\Commands;

use App\DataTransfer\Horoscope\MonthlyHoroscopeDTO;
use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use App\Services\Horoscope\MonthlyHoroscopeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the monthly horoscope.
 *
 * Renders what the user would see on the monthly horoscope page.
 *
 * Layout:
 *   ┌─ header (month + year) ─────────────────────────────────────────┐
 *   │ profile name                                                      │
 *   │ bi-wheel placeholder                                              │
 *   │ transit planet list (1st of month, no degrees)                   │
 *   ├─ key transit factors (top 15, slow first) ──────────────────────┤
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

    private const LUNATION_EMOJIS = [
        'new_moon'  => '🌑',
        'full_moon' => '🌕',
    ];

    protected $signature = 'horoscope:ui-monthly
                            {profile : Profile ID}
                            {--date=       : Any date within the month (YYYY-MM-DD, default: today)}
                            {--simplified  : Show 1-sentence simplified texts (uses _short sections)}
                            {--ai          : Generate synthesis intro with Claude (AI L1)}';

    protected $description = 'Render a monthly horoscope in pseudo-browser console UI';

    public function handle(MonthlyHoroscopeService $service): int
    {
        $date       = $this->option('date') ?: now()->toDateString();
        $simplified = $this->option('simplified');

        $profile = Profile::find($this->argument('profile'));
        if ($profile === null) {
            $this->error('Profile not found.');
            return self::FAILURE;
        }

        try {
            $dto = $service->build($profile, $date);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $monthStart = Carbon::parse($dto->monthStart);
        $monthEnd   = Carbon::parse($dto->monthEnd);
        $monthLabel = $monthStart->format('F Y');

        $this->newLine();

        // ── Header ───────────────────────────────────────────────────────
        $this->put($this->top());
        $this->put($this->row($this->spread('  ☽ MONTHLY HOROSCOPE', '[' . $monthLabel . ']  ')));
        $this->put($this->row('  ' . $monthStart->format('j F') . ' – ' . $monthEnd->format('j F Y')));
        $this->put($this->row('  ' . $dto->profileName));
        $this->put($this->divider());

        // ── Bi-wheel placeholder ─────────────────────────────────────────
        $this->put($this->row($this->center('NATAL + TRANSIT BI-WHEEL · ' . $monthLabel)));
        foreach ($this->wheelLines() as $line) {
            $this->put($this->row($this->center($line)));
        }
        $this->put($this->row(''));

        // ── Transit subtitle + Rx legend ─────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row('  ' . $this->transitSubtitle($dto)));
        $this->put($this->row('  * Rx = Retrograde · as of 1 ' . $monthStart->format('F Y')));
        $this->put($this->row(''));

        // ── Transit planet list (1st of month, no degrees) ───────────────
        foreach ($this->transitLines($dto) as $line) {
            $this->put($this->row($line));
        }

        // ── AI synthesis ─────────────────────────────────────────────────
        if ($this->option('ai')) {
            $assembledTexts = $this->collectTexts($dto, $simplified);
            $natalPlanets   = $profile->natalChart?->planets ?? [];
            $moonSignName   = '';
            foreach ($dto->positions as $pos) {
                if ($pos->body === PlanetaryPosition::MOON) {
                    $moonSignName = $pos->signName;
                    break;
                }
            }
            $synthesis = $this->generateSynthesis(
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

        // ── Key transit factors ──────────────────────────────────────────
        if (count($dto->transitNatalAspects) > 0) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ◆  KEY TRANSIT FACTORS', '')));

            foreach ($dto->transitNatalAspects as $asp) {
                $tGlyph   = self::BODY_GLYPHS[$asp->transitBody] ?? '?';
                $nGlyph   = self::BODY_GLYPHS[$asp->natalBody] ?? '?';
                $aGlyph   = self::ASPECT_GLYPHS[$asp->aspect] ?? $asp->aspect;
                $aLabel   = __('ui.aspects.' . $asp->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $asp->aspect));
                $key      = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
                $block    = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);

                // Fast planets: show peak day; slow: no day label
                $dayLabel = ($asp->transitBody < 5 && $asp->peakDate)
                    ? '  · peak ' . Carbon::parse($asp->peakDate)->format('j M')
                    : '';
                $heading  = '· ' . $tGlyph . ' ' . $asp->transitName . '  ' . $aGlyph . ' ' . $aLabel
                          . '  ' . $nGlyph . ' natal ' . $asp->natalName . $dayLabel;

                $this->put($this->row(''));
                $this->put($this->row('  ' . $heading));

                if ($block) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }

            // ── Retrogrades ──────────────────────────────────────────────
            foreach ($dto->retrogrades as $rx) {
                $bodyGlyph = self::BODY_GLYPHS[$rx->body] ?? '';
                $signGlyph = self::SIGN_GLYPHS[$rx->signIndex] ?? '';
                $rxKey     = strtolower($rx->name) . '_rx_' . strtolower($rx->signName);
                $block     = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);

                $this->put($this->row(''));
                $this->put($this->row('  · ' . $bodyGlyph . ' ' . $rx->name . ' ' . __('ui.retrograde') . '  ·  in ' . $signGlyph . ' ' . $rx->signName));

                if ($block) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($block->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }

            $this->put($this->row(''));
        }

        // ── Progressed Moon ──────────────────────────────────────────────
        if ($dto->progressedMoon) {
            $pm = $dto->progressedMoon;
            $this->put($this->divider());
            $this->put($this->row($this->spread('  ○  PROGRESSED MOON', '')));
            $this->put($this->row(''));
            $this->put($this->row('  ' . $pm->summaryLine));
            if (! empty($pm->notes)) {
                $this->put($this->row(''));
                foreach ($pm->notes as $note) {
                    foreach ($this->wrap($note, self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }
            }
            $this->put($this->row(''));
        }

        // ── Lunations ────────────────────────────────────────────────────
        if (! empty($dto->lunations)) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  🌙  LUNATIONS', '')));

            foreach ($dto->lunations as $lun) {
                $this->put($this->row(''));
                $lunDate  = Carbon::parse($lun->date)->format('l, j M');
                $lunLabel = (self::LUNATION_EMOJIS[$lun->type] ?? '') . '  ' . $lun->name . ' in ' . $lun->signName;
                $this->put($this->row($this->spread('  ' . $lunLabel, $lunDate . '  ')));

                // Sign text
                $lunSignKey   = strtolower(str_replace(' ', '_', $lun->type)) . '_in_' . strtolower($lun->signName);
                $lunSignBlock = TextBlock::pick($lunSignKey, $simplified ? 'lunation_sign_short' : 'lunation_sign', 1);
                if ($lunSignBlock) {
                    $this->put($this->row(''));
                    foreach ($this->wrap(trim(strip_tags($lunSignBlock->text)), self::IW - 4) as $line) {
                        $this->put($this->row('    ' . $line));
                    }
                }

                // Natal house text (personalised)
                if ($lun->house) {
                    $houseKey     = strtolower(str_replace(' ', '_', $lun->type)) . '_house_' . $lun->house;
                    $houseSection = $simplified ? 'lunation_house_short' : 'lunation_house';
                    $houseBlock   = TextBlock::pick($houseKey, $houseSection, 1);
                    if ($houseBlock) {
                        $this->put($this->row(''));
                        $this->put($this->row('  H' . $lun->house . ' — ' . $this->houseName($lun->house)));
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
        if (! empty($dto->keyDates)) {
            $this->put($this->divider());
            $this->put($this->row($this->spread('  📅  KEY DATES', '')));
            $this->put($this->row(''));
            foreach ($dto->keyDates as $kd) {
                $dateStr = Carbon::parse($kd->date)->format('D j M');
                $this->put($this->row('  · ' . $dateStr . '  —  ' . $kd->label));
            }
            $this->put($this->row(''));
        }

        // ── Areas of life ────────────────────────────────────────────────
        $this->put($this->divider());
        $this->put($this->row($this->spread('  ★  AREAS OF LIFE', '')));
        $this->put($this->row(''));

        foreach ($dto->areasOfLife as $area) {
            $emoji = self::AREA_EMOJIS[$area->slug] ?? '';
            $this->put($this->row($this->spread('  ' . $emoji . ' ' . $area->name, $this->ratingDisplay($area->rating, $area->maxRating))));
        }
        $this->put($this->row(''));

        // ── Footer ───────────────────────────────────────────────────────
        $this->put($this->divider());
        $rxLabel = ! empty($dto->retrogrades)
            ? implode(' · ', array_map(fn ($rx) => $rx->name . ' Rx', $dto->retrogrades))
            : '';
        $this->put($this->row(
            '  monthly  ·  ' . $monthLabel
            . '  ·  ' . count($dto->transitNatalAspects) . ' transits'
            . ($rxLabel ? '  ·  ' . $rxLabel : '')
        ));
        $this->put($this->bottom());

        $this->newLine();

        return self::SUCCESS;
    }

    // ── House name labels ────────────────────────────────────────────────

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

    // ── Collect TextBlock texts for AI synthesis ─────────────────────────

    private function collectTexts(MonthlyHoroscopeDTO $dto, bool $simplified): array
    {
        $texts = [];

        foreach ($dto->transitNatalAspects as $asp) {
            $aspWord = __('ui.aspects.' . $asp->aspect, [], null) ?: ucfirst(str_replace('_', ' ', $asp->aspect));
            $key     = 'transit_' . strtolower($asp->transitName) . '_' . $asp->aspect . '_natal_' . strtolower($asp->natalName);
            $block   = TextBlock::pick($key, $simplified ? 'transit_natal_short' : 'transit_natal', 1);
            if ($block) {
                $texts[] = "[Transit {$asp->transitName} {$aspWord} natal {$asp->natalName}]\n" . trim(strip_tags($block->text));
            }
        }

        foreach ($dto->retrogrades as $rx) {
            $rxKey = strtolower($rx->name) . '_rx_' . strtolower($rx->signName);
            $block = TextBlock::pick($rxKey, $simplified ? 'retrograde_short' : 'retrograde', 1);
            if ($block) {
                $texts[] = "[{$rx->name} " . __('ui.retrograde') . " in {$rx->signName}]\n" . trim(strip_tags($block->text));
            }
        }

        return $texts;
    }

    // ── AI synthesis ─────────────────────────────────────────────────────

    private function generateSynthesis(
        array  $assembledTexts,
        array  $natalPlanets,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $moonSignName,
        bool   $simplified = false,
        int    $profileId  = 0,
        string $language   = 'en',
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

        $langNote = $language !== 'en' ? "Write in language code: {$language}." : 'Write in English.';
        $system = "{$langNote}\n\nYou are writing a personalized monthly horoscope intro for a single person.\n\n"
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
                    ['key' => $cacheKey, 'section' => 'ai_synthesis', 'language' => $language, 'variant' => 1],
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

    // ── Transit display helpers ──────────────────────────────────────────

    private function transitSubtitle(MonthlyHoroscopeDTO $dto): string
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

    private function transitLines(MonthlyHoroscopeDTO $dto): array
    {
        $col = [];
        foreach ($dto->positions as $pos) {
            $retro = $pos->isRetrograde ? ' Rx' : '';
            $col[] = (self::BODY_GLYPHS[$pos->body] ?? '?') . ' '
                   . $pos->name . ' in '
                   . (self::SIGN_GLYPHS[$pos->signIndex] ?? '') . ' '
                   . $pos->signName
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

    private function ratingDisplay(int $rating, int $maxRating): string
    {
        if ($rating === 0) {
            return __('ui.rating_wait') . '  ';
        }
        return str_repeat('★', $rating) . str_repeat('☆', $maxRating - $rating) . '  ';
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
