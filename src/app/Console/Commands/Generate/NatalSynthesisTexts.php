<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class NatalSynthesisTexts extends Command
{
    protected $signature = 'horoscope:generate-natal-synthesis
                            {--variants=3 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--short : Generate 1-sentence simplified variants (_short sections)}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate natal position text blocks (Ascendant in sign, planet in sign+house)';

    // Cost reference (2026-03-05, claude-haiku-4-5-20251001):
    // 732 keys × 1 variant = ~$1.06
    // 732 keys × 3 variants = ~$3.20 (estimated)

    private array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
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
        $this->info("Natal positions | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

            $section = $this->sectionForKey($key, $short);

            if (! $dryRun && ! $onlyKey) {
                $existing = TextBlock::where('key', $key)
                    ->where('section', $section)
                    ->where('language', 'en')
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
        if (str_starts_with($key, 'ascendant_in_')) {
            return $this->buildAscendantPrompt($key, $variants);
        }

        if (preg_match('/^(\w+)_in_(\w+)_house_(\d+)$/', $key, $m)) {
            return $this->buildPositionPrompt($key, $variants, $m);
        }

        return $this->jsonPrompt("Write {$variants} variants for: {$key}", $variants);
    }

    private function buildAscendantPrompt(string $key, int $variants): string
    {
        $sign = ucfirst(str_replace('ascendant_in_', '', $key));
        return $this->jsonPrompt(
            "Write {$variants} variants for natal placement: {$sign} Ascendant (rising sign).",
            $variants
        );
    }

    private function buildPositionPrompt(string $key, int $variants, array $m): string
    {
        $bodies = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
        $planet = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
        $sign   = ucfirst($m[2]);
        $house  = $this->ordinals[(int) $m[3]] ?? $m[3];
        return $this->jsonPrompt(
            "Write {$variants} variants for natal placement: {$planet} in {$sign} in the {$house} house.",
            $variants
        );
    }

    private function sectionForKey(string $key, bool $short = false): string
    {
        $base = str_starts_with($key, 'ascendant_in_') ? 'natal_ascendant' : 'natal_positions';
        return $short ? $base . '_short' : $base;
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

        // natal_ascendant — ASC in sign (12 keys)
        foreach ($this->signNames as $sign) {
            $keys[] = 'ascendant_in_' . strtolower($sign);
        }

        // natal_positions — Sun, Moon, Mercury, Venus, Mars in sign + house (5 × 12 × 12 = 720 keys)
        // Jupiter/Saturn/outer planets excluded — covered by aspect sections.
        $positionBodies = array_intersect_key(PlanetaryPosition::BODY_NAMES, array_flip([0, 1, 2, 3, 4]));

        foreach ($positionBodies as $planet) {
            foreach ($this->signNames as $sign) {
                for ($house = 1; $house <= 12; $house++) {
                    $keys[] = strtolower($planet) . '_in_' . strtolower($sign) . '_house_' . $house;
                }
            }
        }

        return $keys;
    }
}
