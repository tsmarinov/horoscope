<?php

namespace App\Console\Commands\Generate;

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

/**
 * Generates two types of lunar calendar text blocks:
 *
 *   lunar_day    — moon_in_{sign}      (12 keys)  — day-by-day Moon-in-sign descriptions
 *   lunation_house — new_moon_house_{n} / full_moon_house_{n}  (24 keys) — personalized lunation cards
 */
class LunarTexts extends Command
{
    protected $signature = 'horoscope:generate-lunar
                            {--type=lunar_day : Section type: lunar_day | lunation_house}
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate lunar calendar text blocks (moon-in-sign + lunation house)';

    private array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    private array $houseThemes = [
        1  => 'identity, self-image, new personal beginnings, physical body',
        2  => 'finances, possessions, personal values, material security',
        3  => 'communication, siblings, short trips, learning, immediate environment',
        4  => 'home, family, roots, inner foundations, domestic life',
        5  => 'creativity, romance, children, self-expression, pleasure',
        6  => 'health, daily routines, work habits, service, practical details',
        7  => 'relationships, partnerships, marriage, one-to-one connections',
        8  => 'shared resources, deep transformation, intimacy, joint finances',
        9  => 'beliefs, higher education, travel, philosophy, broadening horizons',
        10 => 'career, public reputation, long-term ambitions, authority',
        11 => 'friendships, groups, social goals, community, future visions',
        12 => 'solitude, hidden matters, endings, rest, spiritual retreat',
    ];

    public function handle(): int
    {
        $type     = $this->option('type');
        $variants = (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');

        if (! in_array($type, ['lunar_day', 'lunation_house', 'lunation_sign'])) {
            $this->error("Unknown type: {$type}. Use lunar_day, lunation_house, or lunation_sign.");
            return self::FAILURE;
        }

        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $keys   = $this->buildKeys($type);

        if ($onlyKey) {
            $keys = array_filter($keys, fn ($k) => $k === $onlyKey);
            if (empty($keys)) {
                $this->error("Key not found: {$onlyKey}");
                return self::FAILURE;
            }
        }

        if ($fromKey) {
            $found = false;
            $keys  = array_filter($keys, function ($k) use ($fromKey, &$found) {
                if ($k === $fromKey) $found = true;
                return $found;
            });
        }

        $keys      = array_values($keys);
        $total     = count($keys);
        $totalCost = 0.0;
        $prIn      = 0.80 / 1_000_000;
        $prOut     = 4.00 / 1_000_000;

        $this->info("Lunar texts | Type: {$type} | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

            if (! $dryRun && ! $onlyKey) {
                $existing = TextBlock::where('key', $key)
                    ->where('section', $type)
                    ->where('language', 'en')
                    ->count();

                if ($existing >= $variants) {
                    $this->line("  → already exists, skipping");
                    continue;
                }
            }

            $prompt = $this->buildPrompt($key, $type, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 1024,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $this->systemPrompt($type),
                    temperature: 1.0,
                );

                $raw  = $response->content[0]->text ?? '';
                $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
                $json = preg_replace('/```\s*$/m', '', $json);

                if ($dryRun) {
                    $this->line("  RESPONSE:\n" . $json);
                    return self::SUCCESS;
                }

                $blocks = json_decode($json, true);

                if (! is_array($blocks)) {
                    $this->error("  Invalid JSON response for {$key}");
                    continue;
                }

                $in  = $response->usage->inputTokens  ?? 0;
                $out = $response->usage->outputTokens ?? 0;
                $perBlock = $variants > 0 ? [
                    'tokens_in'  => (int) round($in  / $variants),
                    'tokens_out' => (int) round($out / $variants),
                    'cost_usd'   => round(($in * $prIn + $out * $prOut) / $variants, 8),
                ] : [];

                foreach ($blocks as $block) {
                    TextBlock::updateOrCreate(
                        [
                            'key'      => $key,
                            'section'  => $type,
                            'language' => 'en',
                            'variant'  => $block['variant'],
                        ],
                        array_merge([
                            'text' => $block['text'],
                            'tone' => $block['tone'] ?? 'neutral',
                        ], $perBlock)
                    );
                }

                $cost = $in * $prIn + $out * $prOut;
                $totalCost += $cost;
                $this->line(sprintf('  → saved %d variants | $%.4f | total $%.4f', $variants, $cost, $totalCost));

            } catch (\Exception $e) {
                $this->error("  ERROR: " . $e->getMessage());
            }
        }

        $this->info(sprintf('Done. Total cost: $%.4f', $totalCost));
        return self::SUCCESS;
    }

    private function systemPrompt(string $type): string
    {
        if ($type === 'lunar_day') {
            return <<<PROMPT
You are writing Moon-in-sign transit descriptions for a lunar calendar application.

Style rules:
- Write impersonally — NO "you", NO "your", NO direct address to the reader
- Describe the general mood and behaviour as observable facts: "People tend to...", "The mood is...", "Conversations become...", "Attention turns to..."
- Exactly 2 sentences — no more, no less
- Each sentence must carry real information — no filler, no restating the obvious, no "this is a good time to..."
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Time-framed language: "right now", "over the next day or two", "while this lasts", "these days", "for the next 48 hours", "during this transit"
- Vary the opening — do NOT start with "Right now" or "Over the next day" every time
- Do NOT start with "People become more..." or "Conversations become..." or "Conversations turn..." — these are weak filler openers
- Vary the sentence subject: "The mood...", "Attention shifts...", "Emotional tension...", "Impatience rises...", "A pull toward...", "Focus narrows...", "The social atmosphere..." — use different angles
- Describe concrete behaviour in real situations, not abstract tendencies
- NEVER mention sign names (Aries, Taurus, Gemini, Cancer, Leo, Virgo, Libra, Scorpio, Sagittarius, Capricorn, Aquarius, Pisces) or planet names (Moon, Sun, Mercury, Venus, Mars) in the text — they are shown in the header
- Do NOT start with "This placement...", "With Moon in ...", "The Moon in ...", or "While the Moon moves through..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for key behavioural traits; <em> for planet and sign names
PROMPT;
        }

        if ($type === 'lunation_sign') {
            return <<<PROMPT
You are writing one-line taglines for a lunar calendar application.

Style rules:
- Exactly 1 short phrase — not a full sentence, no verb required
- Plain everyday words — no spiritual or psychological jargon
- Comma-separated keywords or a short noun phrase describing the main themes of this lunation
- Maximum 10 words total
- No "you", no direct address, no metaphors
- Forbidden words: journey, path, soul, essence, force, portal, gateway, threshold, healing, wounds
- No HTML formatting — plain text only
PROMPT;
        }

        // lunation_house
        return <<<PROMPT
You are writing personalized lunation descriptions for a lunar calendar application.

Style rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Exactly 3 sentences — no more, no less
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the lunation activates in this area of life: what the person may feel, notice, or be called to do
- Time-framed language: "this new moon", "over the next two weeks", "in the coming month", "during this full moon", "in the days ahead"
- Vary the opening — do NOT use the same opener for every text
- NEVER mention house numbers, sign names, or planet names in the text — they are already shown in the label above
- Do NOT start with "This lunation...", "The New Moon in ...", or "With the Full Moon in..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves, portal, gateway, threshold
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for key behavioural traits or themes; <em> for planet and sign names
PROMPT;
    }

    private function buildPrompt(string $key, string $type, int $variants): string
    {
        $template = '';
        for ($i = 1; $i <= $variants; $i++) {
            $template .= "  {\"variant\": {$i}, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}";
            if ($i < $variants) $template .= ",\n";
        }

        $instruction = match ($type) {
            'lunar_day'      => $this->lunarDayInstruction($key, $variants),
            'lunation_house' => $this->lunationHouseInstruction($key, $variants),
            'lunation_sign'  => $this->lunationSignInstruction($key, $variants),
            default          => "Write {$variants} variants for: {$key}",
        };

        return <<<PROMPT
{$instruction}

Return only a JSON array, no extra text:
[
{$template}
]
PROMPT;
    }

    private function lunarDayInstruction(string $key, int $variants): string
    {
        // key format: moon_in_aries
        $sign = ucfirst(str_replace('moon_in_', '', $key));
        return "Write {$variants} variant(s) for: Moon transiting through {$sign} (lasts ~2 days). "
             . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
    }

    private function lunationHouseInstruction(string $key, int $variants): string
    {
        // key format: new_moon_house_5 or full_moon_house_7
        if (preg_match('/^(new_moon|full_moon)_house_(\d+)$/', $key, $m)) {
            $type  = $m[1] === 'new_moon' ? 'New Moon' : 'Full Moon';
            $house = (int) $m[2];
            $theme = $this->houseThemes[$house] ?? 'general life area';
            $ord   = $this->ordinal($house);
            return "Write {$variants} variant(s) for: {$type} activating the {$ord} house ({$theme}). "
                 . "Use <strong> for key themes. Use <em> for planet and sign names.";
        }

        return "Write {$variants} variants for: {$key}";
    }

    private function lunationSignInstruction(string $key, int $variants): string
    {
        // key format: new_moon_in_virgo or full_moon_in_aries
        if (preg_match('/^(new_moon|full_moon)_in_(\w+)$/', $key, $m)) {
            $type = $m[1] === 'new_moon' ? 'New Moon' : 'Full Moon';
            $sign = ucfirst($m[2]);
            return "Write {$variants} tagline(s) for: {$type} in {$sign}. Plain text only, no HTML.";
        }
        return "Write {$variants} tagline(s) for: {$key}";
    }

    private function ordinal(int $n): string
    {
        $suffix = match (true) {
            $n % 100 >= 11 && $n % 100 <= 13 => 'th',
            $n % 10 === 1                     => 'st',
            $n % 10 === 2                     => 'nd',
            $n % 10 === 3                     => 'rd',
            default                           => 'th',
        };
        return $n . $suffix;
    }

    private function buildKeys(string $type): array
    {
        if ($type === 'lunar_day') {
            return array_map(
                fn ($sign) => 'moon_in_' . strtolower($sign),
                $this->signNames
            );
        }

        if ($type === 'lunation_sign') {
            $keys = [];
            foreach (['new_moon_in', 'full_moon_in'] as $prefix) {
                foreach ($this->signNames as $sign) {
                    $keys[] = $prefix . '_' . strtolower($sign);
                }
            }
            return $keys;
        }

        // lunation_house
        $keys = [];
        foreach (['new_moon', 'full_moon'] as $lun) {
            for ($house = 1; $house <= 12; $house++) {
                $keys[] = $lun . '_house_' . $house;
            }
        }
        return $keys;
    }
}
