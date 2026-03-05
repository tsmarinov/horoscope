<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

class GenerateTexts extends Command
{
    protected $signature = 'horoscope:generate-texts
                            {--section=natal : Section (natal, natal_synthesis)}
                            {--variants=3 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate text blocks for horoscope sections using Anthropic API';

    private array $aspectLabels = [
        'conjunction'  => 'conjunction',
        'opposition'   => 'opposition',
        'trine'        => 'trine',
        'square'       => 'square',
        'sextile'      => 'sextile',
        'quincunx'     => 'quincunx (150°)',
        'semi_sextile' => 'semi-sextile (30°)',
    ];

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
        $section  = $this->option('section');
        $variants = (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');

        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));

        $keys = $this->buildKeys($section);

        if (empty($keys)) {
            return self::FAILURE;
        }

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

        $keys  = array_values($keys);
        $total = count($keys);
        $this->info("Section: {$section} | Keys: {$total} | Variants: {$variants} | Model: {$model}");

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

            $prompt = $this->buildPrompt($key, $section, $variants);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 1024,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $this->systemPrompt($section),
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

                foreach ($blocks as $block) {
                    TextBlock::updateOrCreate(
                        [
                            'key'      => $key,
                            'section'  => $section,
                            'language' => 'en',
                            'variant'  => $block['variant'],
                        ],
                        [
                            'text' => $block['text'],
                            'tone' => $block['tone'] ?? 'neutral',
                        ]
                    );
                }

                $this->line("  → saved {$variants} variants");

            } catch (\Exception $e) {
                $this->error("  ERROR: " . $e->getMessage());
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function systemPrompt(string $section): string
    {
        $base = <<<BASE
You are an experienced astrologer writing natal chart interpretations for a horoscope application.

Style rules:
- Write organic narrative paragraphs — not bullet lists, not catalogs of facts
- Address the person as "you" (gender-neutral, no he/she)
- Each text is 3–5 sentences
- Each variant takes a different angle (different imagery, different life area emphasis)
- Conversational but meaningful — as if spoken during a real consultation
- Do NOT start with "This placement...", "With [planet] in [sign]...", or "[Planet] in [sign] means..."
- Write directly about the person's experience, not about the symbols
BASE;

        if ($section === 'natal_synthesis') {
            $base .= "\n- These are standalone paragraphs that will be combined into a flowing natal chart narrative";
        }

        return $base;
    }

    private function buildPrompt(string $key, string $section, int $variants): string
    {
        return match($section) {
            'natal'           => $this->natalAspectPrompt($key, $variants),
            'natal_synthesis' => $this->natalSynthesisPrompt($key, $variants),
            default           => '',
        };
    }

    // ─── Natal aspects ────────────────────────────────────────────────────────

    private function natalAspectPrompt(string $key, int $variants): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseAspectKey($key);

        $nameA       = PlanetaryPosition::BODY_NAMES[$bodyA] ?? $bodyA;
        $nameB       = PlanetaryPosition::BODY_NAMES[$bodyB] ?? $bodyB;
        $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
        $toneHint    = $this->toneHint($aspect);

        return <<<PROMPT
Write {$variants} variants for natal aspect: {$nameA} {$aspectLabel} {$nameB}

{$toneHint}

Return only a JSON array, no extra text:
[
  {"variant": 1, "tone": "positive|negative|neutral", "text": "..."},
  {"variant": 2, "tone": "positive|negative|neutral", "text": "..."},
  {"variant": 3, "tone": "positive|negative|neutral", "text": "..."}
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

    private function parseAspectKey(string $key): array
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

    // ─── Natal synthesis ──────────────────────────────────────────────────────

    private function natalSynthesisPrompt(string $key, int $variants): string
    {
        // ascendant_in_aries
        if (str_starts_with($key, 'ascendant_in_')) {
            $sign = str_replace('ascendant_in_', '', $key);
            $sign = ucfirst($sign);
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$sign} as the Ascendant (rising sign) in a natal chart.",
                $variants
            );
        }

        // dominant_earth / dominant_fire / dominant_water / dominant_air
        if (str_starts_with($key, 'dominant_')) {
            $element = ucfirst(str_replace('dominant_', '', $key));
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$element} as the dominant element in a natal chart.",
                $variants
            );
        }

        // sun_in_aries_house_1
        if (preg_match('/^(\w+)_in_(\w+)_house_(\d+)$/', $key, $m)) {
            $bodies  = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
            $planet  = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
            $sign    = ucfirst($m[2]);
            $house   = $this->ordinals[(int) $m[3]] ?? $m[3];
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$planet} in {$sign} in the {$house} house in a natal chart.\nTone: neutral (describe the energy, challenges and gifts without judgment)",
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

    // ─── Key builders ─────────────────────────────────────────────────────────

    private function buildKeys(string $section): array
    {
        return match($section) {
            'natal'           => $this->natalAspectKeys(),
            'natal_synthesis' => $this->natalSynthesisKeys(),
            default           => $this->unsupportedSection($section),
        };
    }

    private function natalAspectKeys(): array
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

    private function natalSynthesisKeys(): array
    {
        $keys = [];

        // Ascendant by sign
        foreach ($this->signNames as $sign) {
            $keys[] = 'ascendant_in_' . strtolower($sign);
        }

        // Dominant element
        foreach (['fire', 'earth', 'air', 'water'] as $element) {
            $keys[] = 'dominant_' . $element;
        }

        // Planet in sign + house
        foreach (PlanetaryPosition::BODY_NAMES as $bodyId => $planet) {
            foreach ($this->signNames as $sign) {
                for ($house = 1; $house <= 12; $house++) {
                    $keys[] = strtolower($planet) . '_in_' . strtolower($sign) . '_house_' . $house;
                }
            }
        }

        return $keys;
    }

    private function unsupportedSection(string $section): array
    {
        $this->error("Section '{$section}' not yet supported.");
        return [];
    }
}
