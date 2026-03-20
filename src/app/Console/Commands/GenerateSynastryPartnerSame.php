<?php

namespace App\Console\Commands;

use App\Models\TextBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Generate same-sex partner archetype texts for synastry.
 *
 * Reads existing synastry_partner_male / synastry_partner_female blocks,
 * rewrites them for same-sex couples (no Venus/Moon/Mars planet names,
 * correct partner gender pronouns) and stores in:
 *   synastry_partner_male_same   (M+M: attracted to men)
 *   synastry_partner_female_same (F+F: attracted to women)
 */
class GenerateSynastryPartnerSame extends Command
{
    protected $signature = 'horoscope:generate-synastry-partner-same
                            {--section=both : Which section to generate: male_same, female_same, or both}
                            {--force : Regenerate even if target already exists}
                            {--dry-run : Show prompts without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate same-sex partner archetype texts (synastry_partner_male_same / female_same)';

    private const JOBS = [
        'male_same' => [
            'source'  => 'synastry_partner_male',
            'target'  => 'synastry_partner_male_same',
            'system'  => 'You are a concise astrology writer. Return only the rewritten text, nothing else. Plain text, no HTML.',
            'prompt'  => <<<'PROMPT'
Rewrite this synastry partner archetype text for a same-sex male couple (a man who is attracted to men).

Rules:
- Replace ALL references to women/woman/she/her/herself with men/man/he/him/his/himself
- Do NOT mention Venus or Moon anywhere — remove the planet name entirely
- Keep the same astrological sign archetype (the qualities described)
- Keep similar length (4–5 sentences)
- Write in third person: "He is drawn to..."
- No HTML tags

Original text:
{text}
PROMPT,
        ],
        'female_same' => [
            'source'  => 'synastry_partner_female',
            'target'  => 'synastry_partner_female_same',
            'system'  => 'You are a concise astrology writer. Return only the rewritten text, nothing else. Plain text, no HTML.',
            'prompt'  => <<<'PROMPT'
Rewrite this synastry partner archetype text for a same-sex female couple (a woman who is attracted to women).

Rules:
- Replace ALL references to men/man/he/him/his/himself with women/woman/she/her/her/herself
- Do NOT mention Mars or Moon anywhere — remove the planet name entirely
- Keep the same astrological sign archetype (the qualities described)
- Keep similar length (4–5 sentences)
- Write in third person: "She is drawn to..."
- No HTML tags

Original text:
{text}
PROMPT,
        ],
    ];

    public function handle(): int
    {
        $section = $this->option('section');
        $force   = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');
        $model   = $this->option('model');

        if (! in_array($section, ['male_same', 'female_same', 'both'])) {
            $this->error('--section must be male_same, female_same, or both.');
            return self::FAILURE;
        }

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            $this->error('Anthropic API key not configured (services.anthropic.key).');
            return self::FAILURE;
        }

        $jobs = $section === 'both'
            ? self::JOBS
            : [$section => self::JOBS[$section]];

        $totalErrors = 0;

        foreach ($jobs as $jobKey => $job) {
            $this->newLine();
            $this->info("=== {$job['target']} ===");
            $errors = $this->runJob($job, $force, $dryRun, $model, $apiKey);
            $totalErrors += $errors;
        }

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runJob(array $job, bool $force, bool $dryRun, string $model, string $apiKey): int
    {
        $sourceBlocks = TextBlock::where('section', $job['source'])
            ->where('language', 'en')
            ->where('variant', 1)
            ->orderBy('key')
            ->get();

        if ($sourceBlocks->isEmpty()) {
            $this->warn("  No source blocks found in {$job['source']}. Seed them first.");
            return 0;
        }

        $existingKeys = [];
        if (! $force) {
            $existingKeys = TextBlock::where('section', $job['target'])
                ->where('language', 'en')
                ->where('variant', 1)
                ->pluck('key')
                ->flip()
                ->all();
        }

        $toProcess = $sourceBlocks->filter(fn (TextBlock $b) => $force || ! isset($existingKeys[$b->key]));

        if ($toProcess->isEmpty()) {
            $this->info("  All {$job['target']} blocks already exist. Use --force to regenerate.");
            return 0;
        }

        $total = $toProcess->count();
        $this->info("  Processing {$total} blocks | Model: {$model}" . ($dryRun ? ' [DRY RUN]' : ''));

        $client = new \Anthropic\Client(apiKey: $apiKey);
        $bar    = $this->output->createProgressBar($total);
        $bar->start();

        $errors = 0;

        foreach ($toProcess as $block) {
            $strippedText = strip_tags($block->text);
            $prompt       = str_replace('{text}', $strippedText, $job['prompt']);

            if ($dryRun) {
                $bar->advance();
                $this->newLine();
                $this->line("  [{$block->key}] PROMPT: " . substr($prompt, 0, 120) . '...');
                continue;
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 512,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: $job['system'],
                );

                $newText = trim(strip_tags($response->content[0]->text ?? ''));

                if (empty($newText)) {
                    $this->newLine();
                    $this->warn("  Empty response for {$block->key}, skipping.");
                    $errors++;
                    $bar->advance();
                    sleep(1);
                    continue;
                }

                $now = now();
                DB::table('text_blocks')->upsert(
                    [[
                        'key'        => $block->key,
                        'section'    => $job['target'],
                        'language'   => 'en',
                        'variant'    => 1,
                        'gender'     => null,
                        'text'       => $newText,
                        'tone'       => $block->tone ?? 'neutral',
                        'tokens_in'  => $response->usage->inputTokens ?? 0,
                        'tokens_out' => $response->usage->outputTokens ?? 0,
                        'cost_usd'   => 0.0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['key', 'section', 'language', 'variant', 'gender'],
                    ['text', 'tone', 'tokens_in', 'tokens_out', 'updated_at']
                );

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Error on {$block->key}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);

        $processed = $total - $errors;
        $this->info("  Done. {$processed}/{$total} blocks generated." . ($errors > 0 ? " ({$errors} errors)" : ''));

        return $errors;
    }
}
