<?php

namespace App\Console\Commands\Generate;

use App\Models\PlanetaryPosition;
use App\Models\TextBlock;
use Anthropic\Client as AnthropicClient;
use Illuminate\Console\Command;

/**
 * Generate synastry aspect text blocks.
 *
 * Section: synastry_aspect
 * Keys:    {body_a}_{aspect}_{body_b}
 *          body_a always has the lower body ID (direction-agnostic).
 *          Same-body keys (e.g. sun_trine_sun) cover the Mutual section.
 *
 * Bodies:  Sun–Pluto + Chiron + NNode (12 bodies, no Lilith)
 * Aspects: conjunction, opposition, trine, square, sextile, quincunx, semi_sextile
 * Total:   78 unique pairs × 7 aspects = 546 keys
 *
 * Cost estimate (claude-haiku-4-5-20251001, 1 variant):
 *   ~546 keys × ~$0.0006 = ~$0.33
 */
class SynastryTexts extends Command
{
    protected $signature = 'horoscope:generate-synastry
                            {--variants=1 : Number of variants per block}
                            {--from-key= : Start from a specific block key (resume)}
                            {--key= : Generate only this specific block key}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}
                            {--gender= : Gender variant (male, female, or omit for neutral)}';

    protected $description = 'Generate synastry cross-chart aspect text blocks';

    private array $aspectLabels = [
        'conjunction'  => 'conjunction',
        'opposition'   => 'opposition',
        'trine'        => 'trine',
        'square'       => 'square',
        'sextile'      => 'sextile',
        'quincunx'     => 'quincunx (150°)',
        'semi_sextile' => 'semi-sextile (30°)',
    ];

    // Sun–Pluto (0–9) + Chiron (10) + NNode (11); no Lilith (12)
    private array $bodies = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    public function handle(): int
    {
        $variants = (int) $this->option('variants');
        $dryRun   = $this->option('dry-run');
        $model    = $this->option('model');
        $onlyKey  = $this->option('key');
        $fromKey  = $this->option('from-key');
        $gender   = $this->option('gender') ?: null;
        $section  = 'synastry_aspect';

        $client = new AnthropicClient(apiKey: config('services.anthropic.key'));
        $keys   = $this->buildKeys();

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
        $system    = $this->systemPrompt();

        $this->info("Synastry texts | Section: {$section} | Keys: {$total} | Variants: {$variants} | Model: {$model}");

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
                    ->where('gender', $gender)
                    ->count();

                if ($existing >= $variants) {
                    $this->line('  → already exists, skipping');
                    continue;
                }
            }

            $prompt = $this->buildPrompt($key, $variants);

            if ($dryRun) {
                $this->line("  SYSTEM:\n" . $system);
                $this->line("  PROMPT:\n" . $prompt);
                return self::SUCCESS;
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 512,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $system,
                    temperature: 1.0,
                );

                $raw  = $response->content[0]->text ?? '';
                $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
                $json = preg_replace('/```\s*$/m', '', $json);

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

    // ── Key builder ───────────────────────────────────────────────────────

    private function buildKeys(): array
    {
        $aspects = array_keys($this->aspectLabels);
        $keys    = [];

        foreach ($this->bodies as $a) {
            foreach ($this->bodies as $b) {
                if ($b < $a) continue; // lower ID first; include a==b (same-planet pairs)
                foreach ($aspects as $asp) {
                    $nameA  = strtolower(PlanetaryPosition::BODY_NAMES[$a]);
                    $nameB  = strtolower(PlanetaryPosition::BODY_NAMES[$b]);
                    $keys[] = "{$nameA}_{$asp}_{$nameB}";
                }
            }
        }

        return $keys;
    }

    // ── Prompt builders ───────────────────────────────────────────────────

    private function buildPrompt(string $key, int $variants): string
    {
        ['bodyA' => $bodyA, 'bodyB' => $bodyB, 'aspect' => $aspect] = $this->parseAspectKey($key);

        $nameA       = PlanetaryPosition::BODY_NAMES[$bodyA] ?? ucfirst($bodyA);
        $nameB       = PlanetaryPosition::BODY_NAMES[$bodyB] ?? ucfirst($bodyB);
        $aspectLabel = $this->aspectLabels[$aspect] ?? $aspect;
        $toneHint    = $this->toneHint($aspect);
        $isSame      = $bodyA === $bodyB;

        if ($isSame) {
            $instruction = "Write {$variants} variant(s) for synastry aspect: both people's {$nameA} form a {$aspectLabel} with each other.\n"
                . "Describe what this shared planetary resonance means in a relationship — what quality does it create between them?\n"
                . "Use only 'they', 'their', 'between them' — no :owner/:other placeholders (both share the same archetype).\n"
                . "{$toneHint}";
        } else {
            $instruction = "Write {$variants} variant(s) for synastry aspect: :owner's {$nameA} {$aspectLabel} :other's {$nameB}.\n"
                . "Use :owner to refer to the {$nameA} person and :other for the {$nameB} person throughout the text.\n"
                . "Describe what this planetary combination means in a relationship — how does it shape the dynamic between these two people?\n"
                . "{$toneHint}";
        }

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

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are writing synastry interpretation texts for an astrology application.

Context: Two people's natal charts are compared. The aspect is a permanent natal connection between the two charts — it shapes the ongoing dynamic of their relationship regardless of direction.

Style rules:
- Write like a relationship psychologist giving honest, grounded insight — not an astrologer
- ~80–90 words per text, no more
- Write in the third person — "they", "the relationship", "between them" — NOT "you"
- NEVER use "person A", "person B", "Person A", "Person B" — these are forbidden labels
- For cross-planet aspects: use :owner for the first-named planet's person, :other for the second. E.g. ":owner's Sun energizes :other's confidence."
- For same-planet aspects: use only "they", "their", "between them" — no :owner/:other
- Each text is 2–3 sentences. Short, direct sentences — one idea each.
- Plain everyday words — no spiritual jargon ("soul", "journey", "essence", "healing", "wounds")
- Be specific about the relational domain: emotional, intellectual, physical, practical, social
- Describe what actually happens between two people who have this aspect
- For soft aspects (trine, sextile): describe what flows naturally between them
- For hard aspects (square, opposition): describe genuine friction or growth dynamic — do NOT soften it
- For conjunction: describe intensity or merging of the two planetary energies
- Do NOT start with "This aspect...", "When [Planet] [aspect] [Planet]...", or the planet name
- Forbidden words: journey, path, soul, essence, force, portal, gateway, threshold, healing, dance, dissolves, karmic (unless NNode is involved)
- Use HTML formatting: <strong> for key relational traits; <em> for planet and sign names
PROMPT;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function toneHint(string $aspect): string
    {
        return match ($aspect) {
            'trine', 'sextile'         => 'Tone: positive (flows naturally, mutual ease)',
            'square', 'opposition'     => 'Tone: negative (genuine friction, challenge, growth through tension)',
            'conjunction'              => 'Tone: neutral (intensity — depends on the planets involved)',
            'quincunx', 'semi_sextile' => 'Tone: neutral (subtle, requires conscious adjustment)',
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

        return ['bodyA' => 0, 'bodyB' => 0, 'aspect' => 'conjunction'];
    }
}
