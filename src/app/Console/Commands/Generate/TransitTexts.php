<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

/**
 * Generate text blocks for transit horoscope sections.
 *
 * --type=transit        Transit-to-transit aspects (general sky atmosphere).
 *                       Section: transit. Keys: sun_conjunction_moon, mercury_trine_venus, ...
 *
 * --type=transit_natal  Transit-to-natal aspects (personal transit interpretation).
 *                       Section: transit_natal. Keys: transit_sun_conjunction_natal_sun, ...
 *
 * --type=retrograde     Planet retrograde in sign descriptions.
 *                       Section: retrograde. Keys: mercury_rx_aries, saturn_rx_pisces, ...
 *
 * Cost estimate (claude-haiku-4-5-20251001, 1 variant):
 *   transit:        ~474 keys × 1 = ~$0.37
 *   transit_natal: ~1033 keys × 1 = ~$0.80
 *   retrograde:       ~60 keys × 1 = ~$0.05
 *   Total (all 3):  ~1567 keys     = ~$1.22
 */
class TransitTexts extends Command
{
    protected $signature = 'horoscope:generate-transit
                            {--type=transit : Type: transit | transit_natal | retrograde}
                            {--short : Generate 1-sentence simplified variants (saves to {type}_short section)}
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate transit text blocks (transit, transit_natal, retrograde)';

    private array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
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

    // Planets that can go retrograde (Mercury–Pluto, not Sun/Moon)
    private array $rxPlanets = [2, 3, 4, 5, 6, 7, 8, 9]; // Mercury → Pluto

    public function handle(): int
    {
        $type     = $this->option('type');
        $variants = (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');

        if (! in_array($type, ['transit', 'transit_natal', 'retrograde'])) {
            $this->error("Unknown type: {$type}. Use transit | transit_natal | retrograde");
            return self::FAILURE;
        }

        $short   = $this->option('short');
        $section = $short ? $type . '_short' : $type;
        $client  = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $keys    = $this->buildKeys($type);

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
        $system    = $this->systemPrompt($type, $short);

        $this->info("Transit texts | Type: {$type} | Section: {$section} | Keys: {$total} | Variants: {$variants} | Model: {$model}" . ($short ? ' | SHORT' : ''));

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
                    $this->line('  → already exists, skipping');
                    continue;
                }
            }

            $prompt = $this->buildPrompt($type, $key, $variants, $short);

            if ($dryRun) {
                $this->line("  PROMPT:\n" . $prompt);
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 1024,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $system,
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

                $cost       = $in * $prIn + $out * $prOut;
                $totalCost += $cost;
                $this->line(sprintf('  → saved %d variant(s) | $%.4f | total $%.4f', $variants, $cost, $totalCost));

            } catch (\Exception $e) {
                $this->error('  ERROR: ' . $e->getMessage());
            }
        }

        $this->info(sprintf('Done. Total cost: $%.4f', $totalCost));
        return self::SUCCESS;
    }

    // ── Key builders ─────────────────────────────────────────────────────

    private function buildKeys(string $type): array
    {
        return match ($type) {
            'transit'       => $this->buildTransitKeys(),
            'transit_natal' => $this->buildTransitNatalKeys(),
            'retrograde'    => $this->buildRetrogradeKeys(),
        };
    }

    /** Transit-to-transit: same unique pairs as natal aspects (different section). */
    private function buildTransitKeys(): array
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

    /** Transit-to-natal: transit body × natal body × aspect. */
    private function buildTransitNatalKeys(): array
    {
        $bodies  = array_keys(PlanetaryPosition::BODY_NAMES);
        $aspects = array_keys($this->aspectLabels);
        $lilith  = 12;
        $keys    = [];

        foreach ($bodies as $tBody) {
            foreach ($bodies as $nBody) {
                foreach ($aspects as $asp) {
                    // Lilith: only conjunction (either side)
                    if (($tBody === $lilith || $nBody === $lilith) && $asp !== 'conjunction') continue;
                    $tName  = strtolower(PlanetaryPosition::BODY_NAMES[$tBody]);
                    $nName  = strtolower(PlanetaryPosition::BODY_NAMES[$nBody]);
                    $keys[] = "transit_{$tName}_{$asp}_natal_{$nName}";
                }
            }
        }

        return $keys;
    }

    /** Planet Rx in each sign: mercury_rx_aries … saturn_rx_pisces. */
    private function buildRetrogradeKeys(): array
    {
        $keys = [];
        foreach ($this->rxPlanets as $body) {
            $name = strtolower(PlanetaryPosition::BODY_NAMES[$body]);
            foreach ($this->signNames as $sign) {
                $keys[] = "{$name}_rx_" . strtolower($sign);
            }
        }
        return $keys;
    }

    // ── Prompt builders ───────────────────────────────────────────────────

    private function buildPrompt(string $type, string $key, int $variants, bool $short = false): string
    {
        $instruction = match ($type) {
            'transit'       => $this->transitInstruction($key, $variants, $short),
            'transit_natal' => $this->transitNatalInstruction($key, $variants, $short),
            'retrograde'    => $this->retrogradeInstruction($key, $variants, $short),
        };

        return $this->jsonPrompt($instruction, $variants);
    }

    private function transitInstruction(string $key, int $variants, bool $short = false): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseAspectKey($key);

        $nameA       = PlanetaryPosition::BODY_NAMES[$bodyA] ?? ucfirst($bodyA);
        $nameB       = PlanetaryPosition::BODY_NAMES[$bodyB] ?? ucfirst($bodyB);
        $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
        $toneHint    = $this->toneHint($aspect);

        if ($short) {
            return "Write {$variants} 1-sentence variant(s) for transit aspect: {$nameA} {$aspectLabel} {$nameB}.\n"
                 . "Describe the key collective atmosphere this creates in the sky today — impersonal, no 'you'.\n"
                 . "{$toneHint}\n"
                 . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
        }

        return "Write {$variants} variant(s) for transit aspect: {$nameA} {$aspectLabel} {$nameB}.\n"
             . "Two planets making this aspect in the sky TODAY — describe the general atmosphere and energy this creates for everyone. "
             . "What do people typically experience? What does this energy bring?\n"
             . "{$toneHint}\n"
             . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
    }

    private function transitNatalInstruction(string $key, int $variants, bool $short = false): string
    {
        // key format: transit_mercury_trine_natal_sun
        if (! preg_match('/^transit_(\w+)_(conjunction|opposition|trine|square|sextile|quincunx|semi_sextile)_natal_(\w+)$/', $key, $m)) {
            return "Write {$variants} variant(s) for: {$key}";
        }

        $bodies      = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
        $tName       = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
        $nName       = PlanetaryPosition::BODY_NAMES[$bodies[$m[3]] ?? -1] ?? ucfirst($m[3]);
        $aspectLabel = $this->aspectLabels[$m[2]] ?? $m[2];
        $toneHint    = $this->toneHint($m[2]);

        if ($short) {
            return "Write {$variants} 1-sentence variant(s) for transit-to-natal aspect: transit {$tName} {$aspectLabel} natal {$nName}.\n"
                 . "Describe the key personal effect while this transit is active. Address as 'you/your'.\n"
                 . "{$toneHint}\n"
                 . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
        }

        return "Write {$variants} variant(s) for transit-to-natal aspect: transit {$tName} {$aspectLabel} natal {$nName}.\n"
             . "Describe what this transit means for the person WHILE IT IS ACTIVE (days to weeks). "
             . "Address as 'you/your'. What do they experience? What shifts in their life?\n"
             . "{$toneHint}\n"
             . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
    }

    private function retrogradeInstruction(string $key, int $variants, bool $short = false): string
    {
        // key format: mercury_rx_pisces
        if (! preg_match('/^(\w+)_rx_(\w+)$/', $key, $m)) {
            return "Write {$variants} variant(s) for: {$key}";
        }

        $bodies = array_flip(array_map('strtolower', PlanetaryPosition::BODY_NAMES));
        $planet = PlanetaryPosition::BODY_NAMES[$bodies[$m[1]] ?? -1] ?? ucfirst($m[1]);
        $sign   = ucfirst($m[2]);

        if ($short) {
            return "Write {$variants} 1-sentence variant(s) for: {$planet} retrograde in {$sign}.\n"
                 . "Describe the key effect of this retrograde period — address as 'you/your'.\n"
                 . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
        }

        return "Write {$variants} variant(s) for: {$planet} retrograde in {$sign}.\n"
             . "Describe what this retrograde period brings — address as 'you/your'. "
             . "What areas of life are affected? What should the person pay attention to or avoid?\n"
             . "Use <strong> for the key behavioural trait. Use <em> for planet and sign names.";
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function systemPrompt(string $type, bool $short = false): string
    {
        $context = match ($type) {
            'transit'       => "two transiting planets making an aspect in the sky — this affects everyone; describe the general atmosphere and collective energy",
            'transit_natal' => "a transiting planet making an aspect to a person's natal planet — this is a personal temporary influence lasting days to weeks",
            'retrograde'    => "a planet in retrograde motion in a zodiac sign — a period lasting weeks, affecting specific life areas",
        };

        if ($short) {
            $impersonal = $type === 'transit'
                ? "\n- Write impersonally — NO \"you\", NO direct address; describe as observable fact"
                : '';
            return <<<PROMPT
You are writing one-sentence horoscope transit summaries for an astrology application.

Context: {$context}

Style rules:
- Exactly 1 sentence — no more{$impersonal}
- Write like an SMS — maximum 20 words. Cut every unnecessary word.
- Plain everyday words only — no abstract concepts, no spiritual or psychological jargon
- Be specific: use "emotional", "psychological", "practical", "social" — never "wounds", "healing", "energy", "forces"
- Describe the key behavioural effect as a concrete fact
- NEVER mention planet names, sign names, or aspect names in the text — they are already shown in the label above
- Forbidden words: journey, path, soul, essence, force, portal, gateway, threshold, healing, wounds, dance, dissolves
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for the key behavioural trait; <em> for planet and sign names
PROMPT;
        }

        return <<<PROMPT
You are writing horoscope transit descriptions for an astrology application.

Context: {$context}

Style rules:
- Write like a psychologist giving honest, grounded feedback — not an astrologer
- Address the person as "you" (gender-neutral, no he/she)
- Each text is exactly 3 sentences — no more
- Short, simple sentences — one idea per sentence, no dashes, no semicolons. Plain everyday words only — no abstract concepts, no spiritual or psychological jargon.
- Be specific about the domain: use "emotional", "psychological", "practical", "social" — never vague words like "wounds", "healing", "struggles", "energy", "forces"
- Describe what the person actually experiences in real situations. Concrete behaviour only.
- Each variant takes a different angle (different behaviour, different life area)
- Do NOT start with "This transit...", "With [planet]...", or "[Planet] [aspect] [Planet] means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- Time-framed language where appropriate: "right now", "during this period", "these days", "while this lasts", "at the moment", "over the coming weeks"
- Vary the opening — do NOT start with "Right now" or "During this period" every time; rotate between "These days", "At the moment", "Over the coming weeks", "While this lasts", or a direct observation without a time phrase
- No metaphors. No poetic language.
- Use HTML formatting: <strong> for key behavioural traits; <em> for planet and sign names
PROMPT;
    }

    private function toneHint(string $aspect): string
    {
        return match ($aspect) {
            'trine', 'sextile'         => 'Tone: positive (flowing, supportive)',
            'square', 'opposition'     => 'Tone: negative (tension, challenge, growth through friction)',
            'conjunction'              => 'Tone: neutral (intensity — depends on the planets involved)',
            'quincunx', 'semi_sextile' => 'Tone: neutral (subtle adjustment needed)',
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
}
