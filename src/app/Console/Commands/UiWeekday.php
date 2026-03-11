<?php

namespace App\Console\Commands;

use App\Models\PlanetaryPosition;
use App\Models\Profile;
use App\Models\TextBlock;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pseudo-browser UI for the "Days of the Week" reference page.
 *
 * Renders a standalone page describing all 7 weekdays — their planetary
 * rulers, colors, gemstones, numerology, themes, and a short description.
 * Today's day is highlighted. If a profile is supplied, a personalized
 * clothing & jewelry tip is shown for today based on natal Venus sign.
 *
 * Layout:
 *   ┌─ header ─────────────────────────────────────────────────────────┐
 *   │  card per weekday (today highlighted)                            │
 *   │    ruler · number · colors · gemstone · theme                    │
 *   │    2-sentence description                                        │
 *   │    (today only) personalized clothing tip if profile given       │
 *   └─ footer ─────────────────────────────────────────────────────────┘
 */
class UiWeekday extends Command
{
    protected $signature = 'horoscope:ui-weekday
                            {profile? : Profile ID (optional, for personalized clothing tip)}
                            {--date= : Date to highlight as "today" (YYYY-MM-DD, default: today)}';

    protected $description = 'Render the Days of the Week reference page in pseudo-browser console UI';

    private const W  = 72;
    private const IW = 68;

    // Weekday data — ISO day (1=Mon … 7=Sun)
    private const DAYS = [
        1 => [
            'name'    => 'Monday',
            'bg_name' => 'Понеделник',
            'icon'    => '🌙',
            'ruler'   => 'Moon',
            'number'  => 2,
            'colors'  => 'Silver · White · Green',
            'hex'     => '#b4b4c8',
            'gem'     => 'Moonstone',
            'theme'   => 'Intuition · Home · Memory',
            'border'  => '#8a8ab8',
            'text'    => 'Emotional receptivity is heightened and intuition runs closer to the surface than usual. Domestic matters and conversations with close family or friends feel more meaningful — this is a better day for care and attentiveness than for decisive action.',
        ],
        2 => [
            'name'    => 'Tuesday',
            'bg_name' => 'Вторник',
            'icon'    => '♂',
            'ruler'   => 'Mars',
            'number'  => 9,
            'colors'  => 'Red',
            'hex'     => '#c03030',
            'gem'     => 'Ruby',
            'theme'   => 'Action · Courage · Drive',
            'text'    => 'Physical energy and drive are more available today, making it well suited for tasks that require effort, initiative, or direct confrontation. Impatience and friction in interactions are more likely than usual — directness works better than diplomacy.',
        ],
        3 => [
            'name'    => 'Wednesday',
            'bg_name' => 'Сряда',
            'icon'    => '☿',
            'ruler'   => 'Mercury',
            'number'  => 5,
            'colors'  => 'Yellow',
            'hex'     => '#c8a820',
            'gem'     => 'Tiger\'s Eye',
            'theme'   => 'Communication · Learning · Wit',
            'text'    => 'Mental agility is at its peak and communication of all kinds flows more easily. Good for writing, negotiations, short trips, and tasks requiring sharp thinking — scattered attention is the main risk.',
        ],
        4 => [
            'name'    => 'Thursday',
            'bg_name' => 'Четвъртък',
            'icon'    => '♃',
            'ruler'   => 'Jupiter',
            'number'  => 3,
            'colors'  => 'Dark Blue',
            'hex'     => '#2a5098',
            'gem'     => 'Amethyst',
            'theme'   => 'Expansion · Optimism · Wisdom',
            'text'    => 'A natural sense of optimism and generosity characterizes the day, making it well suited for planning, teaching, travel, or anything involving expansion. Overconfidence is the shadow side — commitments made today can exceed what is realistic.',
        ],
        5 => [
            'name'    => 'Friday',
            'bg_name' => 'Петък',
            'icon'    => '♀',
            'ruler'   => 'Venus',
            'number'  => 6,
            'colors'  => 'Rose · Pink · Warm Cream',
            'hex'     => '#d8809a',
            'gem'     => 'Rose Quartz',
            'theme'   => 'Beauty · Relationships · Pleasure',
            'text'    => 'Social interactions and aesthetic sensibilities are sharpened today, making it the natural choice for meetings, events, purchases, or creative work. Relationships benefit from attention — small gestures of appreciation carry more weight than usual.',
        ],
        6 => [
            'name'    => 'Saturday',
            'bg_name' => 'Събота',
            'icon'    => '♄',
            'ruler'   => 'Saturn',
            'number'  => 8,
            'colors'  => 'Violet',
            'hex'     => '#602880',
            'gem'     => 'Obsidian',
            'theme'   => 'Discipline · Structure · Endurance',
            'text'    => 'Discipline and structure are the energies available today — tasks requiring sustained effort, planning, or dealing with practical responsibilities respond well. Solitude and focused work are often more productive than group activity.',
        ],
        7 => [
            'name'    => 'Sunday',
            'bg_name' => 'Неделя',
            'icon'    => '☀️',
            'ruler'   => 'Sun',
            'number'  => 1,
            'colors'  => 'Gold · Amber · Warm Orange',
            'hex'     => '#d89020',
            'gem'     => 'Sunstone',
            'theme'   => 'Vitality · Expression · Leadership',
            'text'    => 'Vitality and self-expression are naturally heightened, making this a good day for anything that requires confidence, visibility, or creative output. Pride and the need for recognition can surface more easily — both as strength and as sensitivity.',
        ],
    ];

    // Clothing tip is AI-generated (day ruler + natal Venus sign synthesis).
    // Placeholder shown here until AiProvider is wired in.
    // Prompt context: weekday name, ruler planet, day colors, natal Venus sign.

    private const SIGN_NAMES = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    public function handle(): int
    {
        $dateStr  = $this->option('date') ?? Carbon::today()->toDateString();
        $date     = Carbon::parse($dateStr);
        $todayIso = (int) $date->isoFormat('E'); // 1=Mon … 7=Sun

        // Load profile & natal Venus sign
        $profileId  = $this->argument('profile');
        $venusSign  = null;
        $profileLabel = '';

        if ($profileId) {
            $profile = Profile::find($profileId);
            if (! $profile) {
                $this->error("Profile #{$profileId} not found.");
                return self::FAILURE;
            }

            $chart = $profile->natalChart;
            if ($chart) {
                foreach ($chart->planets as $planet) {
                    if ($planet['body'] === 3) { // Venus = body index 3
                        $venusSign    = $planet['sign'];
                        break;
                    }
                }
            }

            $profileLabel = ' · Profile #' . $profileId;
        }

        // ── Render ───────────────────────────────────────────────────────────

        $this->line('┌' . str_repeat('─', self::W - 2) . '┐');
        $this->put($this->center('📅  DAYS OF THE WEEK'));
        $this->put($this->center($date->format('l, j F Y') . $profileLabel));
        $this->line($this->divider());

        foreach (self::DAYS as $iso => $day) {
            $isToday = ($iso === $todayIso);

            $this->put('');

            // Day header line
            $todayMark = $isToday ? '  ← today' : '';
            $header    = $day['icon'] . '  ' . mb_strtoupper($day['name'])
                       . '  ·  ' . $day['ruler']
                       . '  ·  № ' . $day['number']
                       . $todayMark;
            $this->put($isToday ? $this->spread('  ' . $header, '') : '  ' . $header);

            // Attrs line
            $this->put('  ' . '🎨 ' . $day['colors'] . '   💎 ' . $day['gem']);

            // Theme
            $this->put('  ' . $day['theme']);

            $this->put('');

            // Description
            foreach ($this->wrap($day['text'], self::IW - 2) as $line) {
                $this->put('  ' . $line);
            }

            // Personalized clothing tip for today (pre-generated, weekday_clothing section)
            if ($isToday && $venusSign !== null) {
                $venusName   = self::SIGN_NAMES[$venusSign];
                $clothingKey = strtolower($day['name']) . '_venus_in_' . strtolower($venusName);
                $block       = TextBlock::where('key', $clothingKey)
                    ->where('section', 'weekday_clothing')
                    ->where('language', 'en')
                    ->first();

                $this->put('');
                $this->put('  👗  Clothing & Jewelry  ·  Venus in ' . $venusName);
                if ($block) {
                    foreach ($this->wrap($block->text, self::IW - 4) as $line) {
                        $this->put('    ' . $line);
                    }
                } else {
                    $this->put('    [no text found for ' . $clothingKey . ']');
                }
            }

            $this->put('');

            if ($iso < 7) {
                $this->line($this->divider());
            }
        }

        // ── Footer ───────────────────────────────────────────────────────────
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

    private function row(string $content): string
    {
        return '│ ' . $this->mbPad($content, self::IW) . ' │';
    }

    private function put(string $line): void
    {
        $this->line($this->row($line));
    }

    private function center(string $text): string
    {
        $len = mb_strlen($text);
        if ($len >= self::IW) return $text;
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
        if ($len >= $width) return mb_substr($str, 0, $width);
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
                if ($line !== '') $lines[] = $line;
                $line = $word;
            }
        }

        if ($line !== '') $lines[] = $line;
        return $lines;
    }
}
