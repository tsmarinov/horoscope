<?php

namespace App\Console\Commands\Generate;

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class NatalAscLordTexts extends Command
{
    protected $signature = 'horoscope:generate-natal-asc-lord
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--short : Generate 1-sentence simplified variants (_short sections)}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}
                            {--gender= : Gender variant (male, female, or omit for neutral)}';

    protected $description = 'Generate natal ASC lord placement text blocks (ruler of ASC in sign + house)';

    // Cost reference: 1728 keys × 1 variant ≈ $1.20

    private array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    // Modern rulerships only (used for key generation)
    private array $rulers = [
        'aries'       => 'Mars',
        'taurus'      => 'Venus',
        'gemini'      => 'Mercury',
        'cancer'      => 'Moon',
        'leo'         => 'Sun',
        'virgo'       => 'Mercury',
        'libra'       => 'Venus',
        'scorpio'     => 'Pluto',
        'sagittarius' => 'Jupiter',
        'capricorn'   => 'Saturn',
        'aquarius'    => 'Uranus',
        'pisces'      => 'Neptune',
    ];

    private array $ordinals = [
        1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th', 6 => '6th',
        7 => '7th', 8 => '8th', 9 => '9th', 10 => '10th', 11 => '11th', 12 => '12th',
    ];

    public function handle(): int
    {
        $short    = $this->option('short');
        $variants = $short ? 1 : (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');
        $gender   = $this->option('gender') ?: null;

        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $keys   = $this->buildKeys();

        if ($onlyKey) {
            $keys = array_filter($keys, fn($k) => $k === $onlyKey);
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
        $prIn      = 0.80 / 1_000_000;   // Haiku input $/token
        $prOut     = 4.00 / 1_000_000;   // Haiku output $/token
        $this->info("Natal ASC lord | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

            $section = $short ? 'natal_asc_lord_short' : 'natal_asc_lord';

            if (! $dryRun && ! $onlyKey) {
                $existing = TextBlock::where('key', $key)
                    ->where('section', $section)
                    ->where('language', 'en')
                    ->where('gender', $gender)
                    ->count();

                if ($existing >= $variants) {
                    $this->line("  → already exists, skipping");
                    continue;
                }
            }

            $prompt = $this->buildPrompt($key, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 1024,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $this->systemPrompt($short),
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
                            'section'  => $section,
                            'language' => 'en',
                            'variant'  => $block['variant'],
                            'gender'   => $gender,
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

    private function systemPrompt(bool $short = false): string
    {
        if ($short) {
            return <<<PROMPT
You are writing one-sentence natal chart placement summaries for a horoscope application.

Style rules:
- Exactly 1 sentence per text — no more
- Write impersonally — no "you", no "your", no direct address
- Describe the key behavioural trait as a fact: "Direct and bold in self-assertion." or "Strong need for emotional security before opening up."
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- No metaphors, no poetic language
- Write like an SMS — maximum 20 words. Cut every unnecessary word.
- NEVER mention planet names, sign names, or house numbers in the text — they are already shown in the label above.
- Use HTML formatting: <strong> for the key trait; <em> for planet and sign names
PROMPT;
        }

        return <<<PROMPT
You are writing natal chart placement descriptions for a horoscope application.

Style rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Each text is 3–5 sentences
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the person actually does in real situations. Concrete behaviour only.
- Each variant takes a different angle on the same placement (different behaviour, different life area)
- Do NOT start with "This placement...", "With [planet] in [sign]...", or "[Planet] in [sign] means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for key behavioural traits; <em> for planet and sign names
PROMPT;
    }

    private function buildPrompt(string $key, int $variants): string
    {
        // Key format: asc_in_{asc_sign}_lord_in_{lord_sign}_house_{house}
        if (preg_match('/^asc_in_(\w+)_lord_in_(\w+)_house_(\d+)$/', $key, $m)) {
            $ascSign  = ucfirst($m[1]);
            $lordSign = ucfirst($m[2]);
            $house    = (int) $m[3];
            $planet   = $this->rulers[strtolower($ascSign)] ?? ucfirst($m[1]);
            $ordinal  = $this->ordinals[$house] ?? $house;

            return $this->jsonPrompt(
                "Write {$variants} variants for: {$planet} (ruler of {$ascSign} Ascendant) in {$lordSign} in the {$ordinal} house.",
                $variants
            );
        }

        return $this->jsonPrompt("Write {$variants} variants for: {$key}", $variants);
    }

    private function jsonPrompt(string $instruction, int $variants): string
    {
        $template = '';
        for ($i = 1; $i <= $variants; $i++) {
            $template .= "  {\"variant\": {$i}, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}";
            if ($i < $variants) $template .= ",\n";
        }

        return <<<PROMPT
{$instruction}

Return only a JSON array, no extra text:
[
{$template}
]
PROMPT;
    }

    private function buildKeys(): array
    {
        $keys = [];

        // 12 ASC signs × 12 lord signs × 12 houses = 1,728 keys
        foreach ($this->signNames as $ascSign) {
            $ascSlug = strtolower($ascSign);
            // lord planet determined by modern rulership (not used in key, only in prompt)
            foreach ($this->signNames as $lordSign) {
                $lordSlug = strtolower($lordSign);
                for ($house = 1; $house <= 12; $house++) {
                    $keys[] = "asc_in_{$ascSlug}_lord_in_{$lordSlug}_house_{$house}";
                }
            }
        }

        return $keys;
    }
}
