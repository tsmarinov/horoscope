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
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate natal synthesis text blocks (ascendant, dominant element, planet in sign+house)';

    private const SECTION = 'natal_synthesis';

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
        $variants = (int) $this->option('variants');
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

        $keys  = array_values($keys);
        $total = count($keys);
        $this->info("Natal synthesis | Keys: {$total} | Variants: {$variants} | Model: {$model}");

        if ($dryRun) {
            $this->warn('[DRY RUN] — nothing will be saved');
        }

        foreach ($keys as $i => $key) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] {$key}");

            if (! $dryRun && ! $onlyKey) {
                $existing = TextBlock::where('key', $key)
                    ->where('section', self::SECTION)
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
                    system: $this->systemPrompt(),
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
                            'section'  => self::SECTION,
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

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an experienced astrologer writing natal chart interpretations for a horoscope application.

Style rules:
- Write organic narrative paragraphs — not bullet lists, not catalogs of facts
- Address the person as "you" (gender-neutral, no he/she)
- Each text is 3–5 sentences
- Each variant takes a different angle (different imagery, different life area emphasis)
- Conversational but meaningful — as if spoken during a real consultation
- Use plain, simple language — avoid literary or academic phrasing; many readers are non-native English speakers
- Vary sentence length: mix short punchy sentences with longer ones for rhythm
- Do NOT start with "This placement...", "With [planet] in [sign]...", or "[Planet] in [sign] means..."
- Write directly about the person's experience, not about the symbols
- These are standalone paragraphs that will be combined into a flowing natal chart narrative
- Use HTML formatting generously: <strong> for key qualities, themes, and memorable phrases; <em> for planet and sign names
- Aim for at least one <strong> or <em> per sentence — formatting should be dense, not sparse
- Example: "<em>Sun</em> conjunct <em>Moon</em> gives you a rare <strong>internal coherence</strong>. What you feel and who you are align naturally — your instincts and your will <strong>speak the same language</strong>. People sense this: there is no gap between your words and your heart."
PROMPT;
    }

    private function buildPrompt(string $key, int $variants): string
    {
        // ascendant_in_aries
        if (str_starts_with($key, 'ascendant_in_')) {
            $sign = ucfirst(str_replace('ascendant_in_', '', $key));
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$sign} as the Ascendant (rising sign) in a natal chart.",
                $variants
            );
        }

        // dominant_fire / dominant_earth / dominant_water / dominant_air
        if (str_starts_with($key, 'dominant_')) {
            $element = ucfirst(str_replace('dominant_', '', $key));
            return $this->jsonPrompt(
                "Write {$variants} variants describing what it means to have {$element} as the dominant element in a natal chart.",
                $variants
            );
        }

        // sun_in_aries_house_1
        if (preg_match('/^(\w+)_in_(\w+)_house_(\d+)$/', $key, $m)) {
            $bodies = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
            $planet = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
            $sign   = ucfirst($m[2]);
            $house  = $this->ordinals[(int) $m[3]] ?? $m[3];
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

    private function buildKeys(): array
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
        foreach (PlanetaryPosition::BODY_NAMES as $planet) {
            foreach ($this->signNames as $sign) {
                for ($house = 1; $house <= 12; $house++) {
                    $keys[] = strtolower($planet) . '_in_' . strtolower($sign) . '_house_' . $house;
                }
            }
        }

        return $keys;
    }
}
