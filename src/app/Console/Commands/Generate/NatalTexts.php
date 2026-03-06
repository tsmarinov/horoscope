<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class NatalTexts extends Command
{
    protected $signature = 'horoscope:generate-natal
                            {--variants=3 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--short : Generate 1-sentence simplified variants (natal_short section)}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate natal aspect text blocks (planet_aspect_planet)';

    private array $aspectLabels = [
        'conjunction'  => 'conjunction',
        'opposition'   => 'opposition',
        'trine'        => 'trine',
        'square'       => 'square',
        'sextile'      => 'sextile',
        'quincunx'     => 'quincunx (150°)',
        'semi_sextile' => 'semi-sextile (30°)',
    ];

    public function handle(): int
    {
        $short    = $this->option('short');
        $section  = $short ? 'natal_short' : 'natal';
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
        $prIn      = 0.80 / 1_000_000;
        $prOut     = 4.00 / 1_000_000;
        $this->info("Natal aspects | Section: {$section} | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

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
You are writing one-sentence natal chart aspect summaries for a horoscope application.

Style rules:
- Exactly 1 sentence per text — no more
- Write impersonally — no "you", no "your", no direct address
- Describe the key behavioural trait as a fact: "Natural harmony between willpower and emotion." or "Difficulty committing to decisions without second-guessing."
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- No metaphors, no poetic language
- Write like an SMS — maximum 20 words. Cut every unnecessary word.
- NEVER mention planet names, sign names, or aspect names in the text — they are already shown in the label above.
- Use HTML formatting: <strong> for the key trait; <em> for planet and sign names
PROMPT;
        }

        return <<<PROMPT
You are writing natal chart aspect descriptions for a horoscope application.

Style rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Each text is 3–5 sentences
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the person actually does in real situations. Concrete behaviour only.
- Each variant takes a different angle on the same aspect (different behaviour, different life area)
- Do NOT start with "This aspect...", "With [aspect]...", or "[Planet] [aspect] [Planet] means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for key behavioural traits; <em> for planet and sign names
PROMPT;
    }

    private function buildPrompt(string $key, int $variants): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseKey($key);

        $nameA       = PlanetaryPosition::BODY_NAMES[$bodyA] ?? $bodyA;
        $nameB       = PlanetaryPosition::BODY_NAMES[$bodyB] ?? $bodyB;
        $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
        $toneHint    = $this->toneHint($aspect);

        $template = implode(",\n", array_map(
            fn($i) => "  {\"variant\": {$i}, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}",
            range(1, $variants)
        ));

        return <<<PROMPT
Write {$variants} variants for natal aspect: {$nameA} {$aspectLabel} {$nameB}

{$toneHint}

Return only a JSON array, no extra text:
[
{$template}
]
PROMPT;
    }

    private function toneHint(string $aspect): string
    {
        return match($aspect) {
            'trine', 'sextile'         => 'Tone: positive (flowing, supportive energy)',
            'square', 'opposition'     => 'Tone: negative (tension, challenge, growth through friction)',
            'conjunction'              => 'Tone: neutral (intensity — depends on the planets involved)',
            'quincunx', 'semi_sextile' => 'Tone: neutral (subtle, requires adjustment)',
            default                    => 'Tone: neutral',
        };
    }

    private function parseKey(string $key): array
    {
        $bodies  = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
        $aspects = array_keys($this->aspectLabels);

        foreach ($aspects as $asp) {
            $pattern = '_' . $asp . '_';
            if (str_contains($key, $pattern)) {
                [$rawA, $rawB] = explode($pattern, $key, 2);
                return [
                    'bodyA'  => $bodies[$rawA] ?? $rawA,
                    'bodyB'  => $bodies[$rawB] ?? $rawB,
                    'aspect' => $asp,
                ];
            }
        }

        return ['bodyA' => '?', 'bodyB' => '?', 'aspect' => '?'];
    }

    private function buildKeys(): array
    {
        $bodies  = array_keys(PlanetaryPosition::BODY_NAMES);
        $aspects = array_keys($this->aspectLabels);
        $lilith  = 12;
        $keys    = [];

        foreach ($bodies as $a) {
            foreach ($bodies as $b) {
                if ($b <= $a) continue;
                foreach ($aspects as $asp) {
                    if (($a === $lilith || $b === $lilith) && $asp !== 'conjunction') continue;
                    $nameA  = strtolower(PlanetaryPosition::BODY_NAMES[$a]);
                    $nameB  = strtolower(PlanetaryPosition::BODY_NAMES[$b]);
                    $keys[] = "{$nameA}_{$asp}_{$nameB}";
                }
            }
        }

        return $keys;
    }
}
