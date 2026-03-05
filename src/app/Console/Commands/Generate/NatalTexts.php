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
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate natal aspect text blocks (planet_aspect_planet)';

    private const SECTION = 'natal';

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
        $this->info("Natal aspects | Keys: {$total} | Variants: {$variants} | Model: {$model}");

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
- Each variant takes a different angle on the same aspect (different imagery, different life area emphasis)
- Conversational but meaningful — as if spoken during a real consultation
- Use plain, simple language — avoid literary or academic phrasing; many readers are non-native English speakers
- Vary sentence length: mix short punchy sentences with longer ones for rhythm
- Do NOT start with "This aspect...", "With [aspect]...", or "[Planet] [aspect] [Planet] means..."
- Write directly about the person's experience, not about the symbols
- Use HTML formatting generously: <strong> for key qualities, themes, and memorable phrases; <em> for planet and sign names
- Aim for at least one <strong> or <em> per sentence — formatting should be dense, not sparse
- Example: "<em>Sun</em> conjunct <em>Moon</em> gives you a rare <strong>internal coherence</strong>. What you feel and who you are align naturally — your instincts and your will <strong>speak the same language</strong>. People sense this: there is no gap between your words and your heart."
PROMPT;
    }

    private function buildPrompt(string $key, int $variants): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseKey($key);

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
