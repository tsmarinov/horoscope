<?php

namespace App\Console\Commands\Generate;

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class NatalHouseLordAspectsTexts extends Command
{
    protected $signature = 'horoscope:generate-natal-house-lord-aspects
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--batch=10 : Number of keys per API call}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}
                            {--gender= : Gender variant (male, female, or omit for neutral)}';

    protected $description = 'Generate natal house lord aspect texts — full + short in one API call (7,560 keys, ~$4)';

    // Cost reference: 7,560 keys × dual (full+short) × 1 variant ≈ $4 (Haiku)

    private array $planetNames = [
        'sun'     => 'Sun',     'moon'    => 'Moon',    'mercury' => 'Mercury',
        'venus'   => 'Venus',   'mars'    => 'Mars',    'jupiter' => 'Jupiter',
        'saturn'  => 'Saturn',  'uranus'  => 'Uranus',  'neptune' => 'Neptune',
        'pluto'   => 'Pluto',
    ];

    private array $aspectLabels = [
        'conjunction'  => 'conjunction',
        'opposition'   => 'opposition',
        'trine'        => 'trine',
        'square'       => 'square',
        'sextile'      => 'sextile',
        'quincunx'     => 'quincunx (150°)',
        'semi_sextile' => 'semi-sextile (30°)',
    ];

    private array $houseDomains = [
        1  => 'self-image, identity, and personal approach to life',
        2  => 'finances, material security, and personal values',
        3  => 'communication, learning, and close connections',
        4  => 'home, family, and emotional foundations',
        5  => 'creativity, romance, and self-expression',
        6  => 'health, daily routines, and work environment',
        7  => 'partnerships, close relationships, and commitments',
        8  => 'shared resources, transformation, and deep bonds',
        9  => 'travel, higher learning, and personal beliefs',
        10 => 'career, public reputation, and long-term ambitions',
        11 => 'friendships, social circles, and future goals',
        12 => 'solitude, inner life, and hidden patterns',
    ];

    private array $ordinals = [
        1 => '1st', 2 => '2nd',  3 => '3rd',  4 => '4th',
        5 => '5th', 6 => '6th',  7 => '7th',  8 => '8th',
        9 => '9th', 10 => '10th', 11 => '11th', 12 => '12th',
    ];

    public function handle(): int
    {
        $variants  = (int) $this->option('variants');
        $dryRun    = $this->option('dry-run');
        $model     = $this->option('model');
        $onlyKey   = $this->option('key');
        $fromKey   = $this->option('from-key');
        $gender    = $this->option('gender') ?: null;

        $client    = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $batchSize = $onlyKey ? 1 : max(1, (int) $this->option('batch'));
        $keys      = $this->buildKeys();

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

        $this->info("Natal house lord aspects | Keys: {$total} | Variants: {$variants} | Batch: {$batchSize} | Model: {$model}");
        $this->info("Saves to: natal_house_lord_aspects + natal_house_lord_aspects_short (one API call each)");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        // Skip keys where BOTH sections already exist
        if (! $dryRun && ! $onlyKey && ! $fromKey) {
            $chunks100     = array_chunk($keys, 100);
            $existingFull  = [];
            $existingShort = [];

            foreach ($chunks100 as $chunk) {
                $full = TextBlock::whereIn('key', $chunk)
                    ->where('section', 'natal_house_lord_aspects')
                    ->where('language', 'en')
                    ->where('gender', $gender)
                    ->pluck('key')->toArray();

                $short = TextBlock::whereIn('key', $chunk)
                    ->where('section', 'natal_house_lord_aspects_short')
                    ->where('language', 'en')
                    ->where('gender', $gender)
                    ->pluck('key')->toArray();

                $existingFull  = array_merge($existingFull, $full);
                $existingShort = array_merge($existingShort, $short);
            }

            $bothExist = array_intersect($existingFull, $existingShort);
            $skipMap   = array_flip($bothExist);
            $keys      = array_values(array_filter($keys, fn ($k) => ! isset($skipMap[$k])));
            $this->line('  → ' . count($bothExist) . ' already complete (both sections), skipping');
        }

        $chunks = array_chunk($keys, $batchSize);
        $done   = 0;

        foreach ($chunks as $chunk) {
            $prompt = $this->buildBatchPrompt($chunk, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
                return self::SUCCESS;
            }

            try {
                $response = $client->messages->create(
                    maxTokens:   min(8192, 700 * count($chunk) * $variants),
                    messages:    [['role' => 'user', 'content' => $prompt]],
                    model:       $model,
                    system:      $this->systemPrompt(),
                    temperature: 1.0,
                );

                $raw  = $response->content[0]->text ?? '';
                $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
                $json = preg_replace('/```\s*$/m', '', $json);

                $result = json_decode($json, true);

                if (! is_array($result)) {
                    $this->error("  Invalid JSON for chunk starting at {$chunk[0]}");
                    continue;
                }

                $in       = $response->usage->inputTokens  ?? 0;
                $out      = $response->usage->outputTokens ?? 0;
                $perBlock = [
                    'tokens_in'  => (int) round($in  / count($chunk)),
                    'tokens_out' => (int) round($out / count($chunk)),
                    'cost_usd'   => round(($in * $prIn + $out * $prOut) / count($chunk), 8),
                ];

                $saved = 0;
                foreach ($chunk as $key) {
                    $entry = $result[$key] ?? null;
                    if (! is_array($entry)) {
                        $this->warn("  Missing key in response: {$key}");
                        continue;
                    }

                    foreach ($entry['full'] ?? [] as $block) {
                        TextBlock::updateOrCreate(
                            ['key' => $key, 'section' => 'natal_house_lord_aspects', 'language' => 'en', 'variant' => $block['variant'], 'gender' => $gender],
                            array_merge(['text' => $block['text'], 'tone' => $block['tone'] ?? 'neutral'], $perBlock)
                        );
                    }

                    foreach ($entry['short'] ?? [] as $block) {
                        TextBlock::updateOrCreate(
                            ['key' => $key, 'section' => 'natal_house_lord_aspects_short', 'language' => 'en', 'variant' => $block['variant'], 'gender' => $gender],
                            array_merge(['text' => $block['text'], 'tone' => $block['tone'] ?? 'neutral'], $perBlock)
                        );
                    }

                    $saved++;
                }

                $cost       = $in * $prIn + $out * $prOut;
                $totalCost += $cost;
                $done      += $saved;
                $this->line(sprintf('[%d/%d] batch:%d saved (full+short) | $%.4f | total $%.4f', $done, $total, $saved, $cost, $totalCost));

            } catch (\Exception $e) {
                $this->error('  ERROR: ' . $e->getMessage());
            }
        }

        $this->info(sprintf('Done. Total cost: $%.4f', $totalCost));
        return self::SUCCESS;
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are writing natal chart house lord aspect descriptions for a horoscope application.

Each key requires TWO versions in one response: a full paragraph and a one-sentence summary.

FULL version rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- 3–4 sentences. Short, simple sentences — one idea per sentence, no dashes, no semicolons
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- The planet acts as ruler of a specific house. Weave the life domain of that house into the text naturally (e.g. "your partnerships", "your finances", "your daily routines") — but NEVER mention house numbers or say "house of X"
- Describe what the person actually does in real situations. Concrete behaviour only
- Do NOT start with "This aspect...", "With [planet]...", or "[Planet] [aspect] [Planet] means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language
- MANDATORY HTML: wrap 1–3 key behavioural traits in <strong>...</strong>; wrap every planet name in <em>...</em>

SHORT version rules:
- Exactly 1 sentence — no more
- Write impersonally — no "you", no "your", no direct address
- Describe the key behavioural trait as a fact: "Strong drive to expand through committed relationships."
- Plain everyday words only
- Maximum 20 words. Cut every unnecessary word
- NEVER mention planet names in the text — they are already shown in the UI label
- MANDATORY HTML: wrap the key trait in <strong>...</strong> only
PROMPT;
    }

    private function buildBatchPrompt(array $keys, int $variants): string
    {
        $lines = [];
        foreach ($keys as $key) {
            [
                'house'  => $house,
                'lord'   => $lord,
                'aspect' => $aspect,
                'other'  => $other,
            ] = $this->parseKey($key);

            $lordName    = $this->planetNames[$lord]  ?? ucfirst($lord);
            $otherName   = $this->planetNames[$other] ?? ucfirst($other);
            $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
            $domain      = $this->houseDomains[$house]  ?? "house {$house} matters";
            $ordinal     = $this->ordinals[$house]      ?? $house;
            $toneHint    = $this->toneHint($aspect);

            $lines[] = "\"{$key}\": {$ordinal} house ruler {$lordName} (domain: {$domain}) {$aspectLabel} {$otherName}. {$toneHint}";
        }

        $fullTemplate = implode(', ', array_map(
            fn ($i) => '{"variant": ' . $i . ', "tone": "positive|negative|neutral", "text": "..."}',
            range(1, $variants)
        ));

        $placements = implode("\n", $lines);

        return <<<PROMPT
Generate texts for these house lord aspects. For EACH key return both "full" (paragraph) and "short" (1 sentence).

Aspects:
{$placements}

Return ONLY a valid JSON object, no extra text:
{
  "key_name": {
    "full":  [{$fullTemplate}],
    "short": [{"variant": 1, "tone": "positive|negative|neutral", "text": "..."}]
  },
  ...
}
PROMPT;
    }

    private function toneHint(string $aspect): string
    {
        return match ($aspect) {
            'trine', 'sextile'         => 'Tone: positive.',
            'square', 'opposition'     => 'Tone: challenging.',
            'conjunction'              => 'Tone: neutral (intensity depends on planets).',
            'quincunx', 'semi_sextile' => 'Tone: neutral (subtle adjustment needed).',
            default                    => 'Tone: neutral.',
        };
    }

    private function parseKey(string $key): array
    {
        // Format: house_{N}_lord_{planet}_{aspect}_{other_planet}
        if (! preg_match('/^house_(\d+)_lord_(.+)$/', $key, $m)) {
            return ['house' => 1, 'lord' => 'sun', 'aspect' => 'conjunction', 'other' => 'moon'];
        }

        $house = (int) $m[1];
        $rest  = $m[2]; // e.g. "mars_trine_venus" or "mars_semi_sextile_venus"

        // Match longest aspect names first (semi_sextile before sextile)
        foreach (array_keys($this->aspectLabels) as $asp) {
            $pattern = '_' . $asp . '_';
            if (str_contains($rest, $pattern)) {
                [$lord, $other] = explode($pattern, $rest, 2);
                return ['house' => $house, 'lord' => $lord, 'aspect' => $asp, 'other' => $other];
            }
        }

        return ['house' => $house, 'lord' => 'sun', 'aspect' => 'conjunction', 'other' => 'moon'];
    }

    private function buildKeys(): array
    {
        $planets = array_keys($this->planetNames);
        $aspects = array_keys($this->aspectLabels);
        $keys    = [];

        for ($house = 1; $house <= 12; $house++) {
            foreach ($planets as $lord) {
                foreach ($aspects as $asp) {
                    foreach ($planets as $other) {
                        if ($other === $lord) continue;
                        $keys[] = "house_{$house}_lord_{$lord}_{$asp}_{$other}";
                    }
                }
            }
        }

        return $keys; // 12 × 10 × 7 × 9 = 7,560
    }
}
