<?php

namespace Database\Seeders;

use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Database\Seeder;

/**
 * Generates natal + natal_short texts for all 45 planet-pair mutual reception aspects.
 * Key format: {planet_a}_mutual_reception_{planet_b} (body_a < body_b)
 * Run: docker exec horo_php php artisan db:seed --class=NatalMutualReceptionSeeder
 */
class NatalMutualReceptionSeeder extends Seeder
{
    private const PLANETS = [
        0 => 'sun', 1 => 'moon', 2 => 'mercury', 3 => 'venus', 4 => 'mars',
        5 => 'jupiter', 6 => 'saturn', 7 => 'uranus', 8 => 'neptune', 9 => 'pluto',
    ];

    private const PLANET_LABELS = [
        'sun' => 'Sun', 'moon' => 'Moon', 'mercury' => 'Mercury', 'venus' => 'Venus',
        'mars' => 'Mars', 'jupiter' => 'Jupiter', 'saturn' => 'Saturn',
        'uranus' => 'Uranus', 'neptune' => 'Neptune', 'pluto' => 'Pluto',
    ];

    private const TONE_MAP = [
        'positive' => 'positive', 'negative' => 'negative', 'neutral' => 'neutral',
        'challenging' => 'negative', 'difficult' => 'negative', 'mixed' => 'neutral',
    ];

    private const BATCH_SIZE = 9;
    private const PR_IN  = 0.80 / 1_000_000;
    private const PR_OUT = 4.00 / 1_000_000;

    public function run(): void
    {
        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $model  = 'claude-haiku-4-5-20251001';

        // Build all 45 ordered pairs
        $allKeys = [];
        $ids = array_keys(self::PLANETS);
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = self::PLANETS[$ids[$i]];
                $b = self::PLANETS[$ids[$j]];
                $allKeys[] = "{$a}_mutual_reception_{$b}";
            }
        }

        // Skip existing
        $existing = TextBlock::where('section', 'natal')
            ->where('key', 'like', '%_mutual_reception_%')
            ->whereNull('gender')
            ->pluck('key')->toArray();
        $existingMap = array_flip($existing);
        $keys = array_values(array_filter($allKeys, fn($k) => !isset($existingMap[$k])));

        $this->command->info(count($existing) . ' already exist, ' . count($keys) . ' to generate.');

        $system = <<<PROMPT
You are writing natal chart mutual reception descriptions for a horoscope application.

Mutual reception occurs when two planets are each in the sign the other rules (e.g. Venus in Aries and Mars in Libra). This creates a cooperative exchange of energy between the two planets.

Each key requires TWO versions: a full paragraph ("full") and a one-sentence summary ("short").

FULL version rules:
- Write like a psychologist giving honest feedback — not an astrologer
- Address the person as "you" (gender-neutral)
- 3–4 sentences. Short, simple sentences — one idea per sentence, no dashes, no semicolons
- Plain everyday words only — no spiritual or psychological jargon
- Describe what the person actually does in real situations. Concrete behaviour only
- Do NOT start with "This aspect...", "With [planet]...", or "Mutual reception means..."
- Forbidden words: journey, path, lifetime, depth, soul, essence, force, pull, tension, dance, dissolves
- No metaphors. No poetic language
- Tone: generally positive or neutral — mutual reception is cooperative
- MANDATORY HTML — you MUST include both tags in every full text:
  * Wrap 1–3 key behavioural traits in <strong>...</strong>
  * Wrap EVERY planet name in <em>...</em> — every single occurrence, no exceptions
  Example: "You <strong>adapt easily between two very different sides of yourself</strong>. <em>Venus</em> and <em>Mars</em> work together rather than against each other."

SHORT version rules:
- Exactly 1 sentence — no more
- Write impersonally — no "you", no direct address
- Describe the key behavioural trait as a fact
- Plain everyday words only. Maximum 20 words
- NEVER mention planet names in the short text — they are already shown in the UI label
- MANDATORY HTML: wrap the key trait in <strong>...</strong>
  Example: "<strong>Natural harmony between drive and desire</strong> makes this person unusually effective."
PROMPT;

        $chunks    = array_chunk($keys, self::BATCH_SIZE);
        $done      = 0;
        $total     = count($keys);
        $totalCost = 0.0;

        foreach ($chunks as $chunk) {
            $lines = [];
            foreach ($chunk as $key) {
                [$a, , $b] = explode('_mutual_reception_', $key . '_mutual_reception_');
                // Robust parse: key = planetA_mutual_reception_planetB
                $parts = explode('_mutual_reception_', $key);
                $a = $parts[0] ?? '';
                $b = $parts[1] ?? '';
                $aLabel = self::PLANET_LABELS[$a] ?? ucfirst($a);
                $bLabel = self::PLANET_LABELS[$b] ?? ucfirst($b);
                $lines[] = "\"{$key}\": {$aLabel} mutual reception with {$bLabel}. Tone: positive.";
            }

            $htmlNote = 'IMPORTANT: HTML is REQUIRED. Every "full" text MUST have <em>PlanetName</em> around EVERY planet name AND <strong>key trait</strong>. Every "short" text MUST have <strong>key trait</strong>.';
            $prompt = "Generate texts for these mutual reception aspects. For EACH key return both \"full\" (paragraph) and \"short\" (1 sentence).\n\n{$htmlNote}\n\nAspects:\n" . implode("\n", $lines) . "\n\nReturn ONLY a valid JSON object:\n{\n  \"key_name\": {\n    \"full\":  [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}],\n    \"short\": [{\"variant\": 1, \"tone\": \"positive|negative|neutral\", \"text\": \"...\"}]\n  }\n}";

            try {
                $response = $client->messages->create(
                    maxTokens:   min(8192, 600 * count($chunk)),
                    messages:    [['role' => 'user', 'content' => $prompt]],
                    model:       $model,
                    system:      $system,
                    temperature: 1.0,
                );

                $raw    = $response->content[0]->text ?? '';
                $json   = preg_replace('/^```(?:json)?\s*/m', '', $raw);
                $json   = preg_replace('/```\s*$/m', '', $json);
                $result = json_decode($json, true);

                if (!is_array($result)) {
                    $this->command->error("Invalid JSON for chunk starting at {$chunk[0]}");
                    continue;
                }

                $in   = $response->usage->inputTokens  ?? 0;
                $out  = $response->usage->outputTokens ?? 0;
                $cost = $in * self::PR_IN + $out * self::PR_OUT;
                $totalCost += $cost;
                $saved = 0;

                foreach ($chunk as $key) {
                    $entry = $result[$key] ?? null;
                    if (!is_array($entry)) {
                        $this->command->warn("  Missing: {$key}");
                        continue;
                    }

                    foreach ($entry['full'] ?? [] as $block) {
                        $tone = self::TONE_MAP[strtolower($block['tone'] ?? 'neutral')] ?? 'neutral';
                        TextBlock::updateOrCreate(
                            ['key' => $key, 'section' => 'natal', 'language' => 'en', 'variant' => $block['variant'], 'gender' => null],
                            ['text' => $block['text'], 'tone' => $tone,
                             'tokens_in'  => (int) round($in  / count($chunk)),
                             'tokens_out' => (int) round($out / count($chunk)),
                             'cost_usd'   => round($cost / count($chunk), 8)]
                        );
                    }
                    foreach ($entry['short'] ?? [] as $block) {
                        $tone = self::TONE_MAP[strtolower($block['tone'] ?? 'neutral')] ?? 'neutral';
                        TextBlock::updateOrCreate(
                            ['key' => $key, 'section' => 'natal_short', 'language' => 'en', 'variant' => $block['variant'], 'gender' => null],
                            ['text' => $block['text'], 'tone' => $tone,
                             'tokens_in'  => (int) round($in  / count($chunk)),
                             'tokens_out' => (int) round($out / count($chunk)),
                             'cost_usd'   => round($cost / count($chunk), 8)]
                        );
                    }
                    $saved++;
                }

                $done += $saved;
                $this->command->info(sprintf('[%d/%d] saved %d | $%.4f | total $%.4f',
                    $done, $total, $saved, $cost, $totalCost));

            } catch (\Exception $e) {
                $this->command->error('ERROR: ' . $e->getMessage());
            }
        }

        $this->command->info(sprintf('Done. Total cost: $%.4f', $totalCost));
    }
}
