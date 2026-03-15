<?php

namespace App\Console\Commands\Generate;

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class NatalHouseLordsTexts extends Command
{
    protected $signature = 'horoscope:generate-natal-house-lords
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--short : Generate 1-sentence simplified variants (_short sections)}
                            {--batch=10 : Number of keys per API call (set 1 to disable batching)}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}
                            {--gender= : Gender variant (male, female, or omit for neutral)}';

    protected $description = 'Generate natal house lord placement text blocks (house cusp sign + lord in sign + house)';

    // Cost reference: 20736 keys × 1 variant ≈ $16.60 (standard), $8-9 (short)

    private array $signNames = [
        0 => 'aries', 1 => 'taurus', 2 => 'gemini', 3 => 'cancer',
        4 => 'leo', 5 => 'virgo', 6 => 'libra', 7 => 'scorpio',
        8 => 'sagittarius', 9 => 'capricorn', 10 => 'aquarius', 11 => 'pisces',
    ];

    // Modern rulerships only
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

        $client    = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $batchSize = $onlyKey ? 1 : max(1, (int) $this->option('batch'));
        $keys      = $this->buildKeys();

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
        $section   = $short ? 'natal_house_lords_short' : 'natal_house_lords';
        $total     = count($keys);
        $totalCost = 0.0;
        $prIn      = 0.80 / 1_000_000;
        $prOut     = 4.00 / 1_000_000;
        $this->info("Natal house lords | Keys: {$total} | Variants: {$variants} | Batch: {$batchSize} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        // Filter out already-existing keys (only when not resuming via --from-key)
        if (! $dryRun && ! $onlyKey && ! $fromKey) {
            $chunks100 = array_chunk($keys, 100);
            $existingKeys = [];
            foreach ($chunks100 as $chunk) {
                $found = TextBlock::whereIn('key', $chunk)
                    ->where('section', $section)
                    ->where('language', 'en')
                    ->where('gender', $gender)
                    ->pluck('key')
                    ->toArray();
                $existingKeys = array_merge($existingKeys, $found);
            }
            $existingMap = array_flip($existingKeys);
            $keys = array_values(array_filter($keys, fn($k) => !isset($existingMap[$k])));
            $this->line("  → " . count($existingKeys) . " already exist, skipping");
        }

        $chunks  = array_chunk($keys, $batchSize);
        $done    = 0;

        foreach ($chunks as $chunk) {
            $prompt = $this->buildBatchPrompt($chunk, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: min(8192, 400 * count($chunk) * $variants),
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

                $result = json_decode($json, true);

                if (! is_array($result)) {
                    $this->error("  Invalid JSON for chunk starting at {$chunk[0]}");
                    continue;
                }

                $in  = $response->usage->inputTokens  ?? 0;
                $out = $response->usage->outputTokens ?? 0;
                $perBlock = [
                    'tokens_in'  => (int) round($in  / count($chunk)),
                    'tokens_out' => (int) round($out / count($chunk)),
                    'cost_usd'   => round(($in * $prIn + $out * $prOut) / count($chunk), 8),
                ];

                $saved = 0;
                foreach ($chunk as $key) {
                    $blocks = $result[$key] ?? null;
                    if (! is_array($blocks)) {
                        $this->warn("  Missing key in response: {$key}");
                        continue;
                    }
                    foreach ($blocks as $block) {
                        TextBlock::updateOrCreate(
                            ['key' => $key, 'section' => $section, 'language' => 'en', 'variant' => $block['variant'], 'gender' => $gender],
                            array_merge(['text' => $block['text'], 'tone' => $block['tone'] ?? 'neutral'], $perBlock)
                        );
                    }
                    $saved++;
                }

                $cost       = $in * $prIn + $out * $prOut;
                $totalCost += $cost;
                $done      += $saved;
                $this->line(sprintf('[%d/%d] batch:%d saved | $%.4f | total $%.4f', $done, $total, $saved, $cost, $totalCost));

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
You are writing one-sentence natal chart house lord placement summaries for a horoscope application.

Style rules:
- Exactly 1 sentence per text — no more
- Write impersonally — no "you", no "your", no direct address
- Describe the key behavioural trait as a fact: "Practical focus on building security through partnerships." or "Restless drive to expand through daily work and routine."
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- No metaphors, no poetic language
- Write like an SMS — maximum 20 words. Cut every unnecessary word.
- NEVER mention planet names, sign names, or house numbers in the text — they are already shown in the label above.
- Use HTML formatting: <strong> for the key trait only
PROMPT;
        }

        return <<<PROMPT
You are writing natal chart house lord placement descriptions for a horoscope application.

Style rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Each text is 3–4 sentences
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the person actually does in real situations. Concrete behaviour only.
- Each variant takes a different angle on the same placement (different behaviour, different life area)
- Do NOT start with "This placement...", "With [planet] in [sign]...", "[Planet] in [sign] means...", "This house...", or "House {N}..."
- Do NOT mention house numbers or what the house represents (e.g. do not say "house of money", "house of relationships")
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language.
- MANDATORY HTML formatting — every text MUST contain HTML tags:
  - Wrap the 1-3 most important behavioural traits in <strong>...</strong>
  - Wrap every planet name and sign name in <em>...</em>
  - Example: "You tend to be <strong>cautious about emotional closeness</strong>. <em>Saturn</em> in <em>Capricorn</em> makes you wait before opening up."
PROMPT;
    }

    private function buildBatchPrompt(array $keys, int $variants): string
    {
        $lines = [];
        foreach ($keys as $key) {
            if (preg_match('/^house_(\d+)_cusp_(\w+)_lord_in_(\w+)_house_(\d+)$/', $key, $m)) {
                $house     = (int) $m[1];
                $cuspSign  = ucfirst($m[2]);
                $lordSign  = ucfirst($m[3]);
                $lordHouse = (int) $m[4];
                $planet    = $this->rulers[strtolower($cuspSign)] ?? $cuspSign;
                $ordinal   = $this->ordinals[$lordHouse] ?? $lordHouse;
                $lines[]   = "\"{$key}\": House {$house} has {$cuspSign} on the cusp. Its ruler {$planet} is in {$lordSign} in the {$ordinal} house.";
            }
        }

        $template = '';
        for ($i = 1; $i <= $variants; $i++) {
            $template .= "{\"variant\": {$i}, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}";
            if ($i < $variants) $template .= ", ";
        }

        $placements = implode("\n", $lines);

        return <<<PROMPT
Generate texts for these placements. Return EXACTLY 1 variant per key — no more.

Placements:
{$placements}

Return ONLY a valid JSON object, no extra text:
{
  "key_name": [{$template}],
  ...
}
PROMPT;
    }

    private function buildPrompt(string $key, int $variants): string
    {
        // Key format: house_{house}_cusp_{cusp_sign}_lord_in_{lord_sign}_house_{lord_house}
        if (preg_match('/^house_(\d+)_cusp_(\w+)_lord_in_(\w+)_house_(\d+)$/', $key, $m)) {
            $house     = (int) $m[1];
            $cuspSign  = ucfirst($m[2]);
            $lordSign  = ucfirst($m[3]);
            $lordHouse = (int) $m[4];
            $planet    = $this->rulers[strtolower($cuspSign)] ?? $cuspSign;
            $ordinal   = $this->ordinals[$lordHouse] ?? $lordHouse;

            return $this->jsonPrompt(
                "Write {$variants} variant(s) for: House {$house} has {$cuspSign} on the cusp. Its ruler {$planet} is in {$lordSign} in the {$ordinal} house.",
                $variants
            );
        }

        return $this->jsonPrompt("Write {$variants} variant(s) for: {$key}", $variants);
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

        // 12 houses × 12 cusp signs × 12 lord signs × 12 lord houses = 20,736 keys
        for ($house = 1; $house <= 12; $house++) {
            foreach ($this->signNames as $cuspSign) {
                $lord = $this->rulers[$cuspSign];
                foreach ($this->signNames as $lordSign) {
                    for ($lordHouse = 1; $lordHouse <= 12; $lordHouse++) {
                        $keys[] = "house_{$house}_cusp_{$cuspSign}_lord_in_{$lordSign}_house_{$lordHouse}";
                    }
                }
            }
        }

        return $keys;
    }
}
