<?php

namespace App\Console\Commands;

use App\Models\TextBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Generate short versions of all synastry_aspect texts using Anthropic API.
 *
 * Reads existing synastry_aspect blocks, summarises each to 1-2 sentences,
 * and stores them as synastry_aspect_short in text_blocks.
 */
class GenerateSynastryAspectShort extends Command
{
    protected $signature = 'horoscope:generate-synastry-aspect-short
                            {--force : Regenerate even if synastry_aspect_short already exists}
                            {--dry-run : Show prompt and response without saving}
                            {--model=claude-haiku-4-5-20251001 : Anthropic model to use}';

    protected $description = 'Generate short synastry_aspect texts from full versions using Anthropic API';

    public function handle(): int
    {
        $force  = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $model  = $this->option('model');

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            $this->error('Anthropic API key not configured (services.anthropic.key).');
            return self::FAILURE;
        }

        // Fetch all full synastry_aspect blocks (variant=1, en)
        $fullBlocks = TextBlock::where('section', 'synastry_aspect')
            ->where('language', 'en')
            ->where('variant', 1)
            ->orderBy('key')
            ->get();

        if ($fullBlocks->isEmpty()) {
            $this->error('No synastry_aspect blocks found. Seed them first.');
            return self::FAILURE;
        }

        // Find existing short keys to skip (unless --force)
        $existingShortKeys = [];
        if (! $force) {
            $existingShortKeys = TextBlock::where('section', 'synastry_aspect_short')
                ->where('language', 'en')
                ->where('variant', 1)
                ->pluck('key')
                ->flip()
                ->all();
        }

        $toProcess = $fullBlocks->filter(fn (TextBlock $b) => $force || ! isset($existingShortKeys[$b->key]));

        if ($toProcess->isEmpty()) {
            $this->info('All synastry_aspect_short blocks already exist. Use --force to regenerate.');
            return self::SUCCESS;
        }

        $total = $toProcess->count();
        $this->info("Processing {$total} blocks | Model: {$model}" . ($dryRun ? ' [DRY RUN]' : ''));

        $client = new \Anthropic\Client(apiKey: $apiKey);
        $bar    = $this->output->createProgressBar($total);
        $bar->start();

        $errors = 0;

        foreach ($toProcess as $block) {
            $strippedText = strip_tags($block->text);

            $prompt = "Summarise this synastry aspect in exactly 1 sentence (15-18 words). Use :owner and :other as placeholders for the two people. Plain text only, no HTML. Focus on the core relational dynamic.\n\nText: {$strippedText}";

            if ($dryRun) {
                $bar->advance();
                $this->newLine();
                $this->line("  [{$block->key}] PROMPT: {$prompt}");
                continue;
            }

            try {
                $response = $client->messages->create(
                    maxTokens: 256,
                    messages: [['role' => 'user', 'content' => $prompt]],
                    model: $model,
                    system: 'You are a concise astrology writer. Return only the summary text, nothing else.',
                );

                $shortText = trim(strip_tags($response->content[0]->text ?? ''));

                if (empty($shortText)) {
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
                        'section'    => 'synastry_aspect_short',
                        'language'   => 'en',
                        'variant'    => 1,
                        'text'       => $shortText,
                        'tone'       => $block->tone ?? 'neutral',
                        'tokens_in'  => $response->usage->inputTokens ?? 0,
                        'tokens_out' => $response->usage->outputTokens ?? 0,
                        'cost_usd'   => 0.0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['key', 'section', 'language', 'variant'],
                    ['text', 'tone', 'tokens_in', 'tokens_out', 'updated_at']
                );

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Error on {$block->key}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
            sleep(1); // rate limit
        }

        $bar->finish();
        $this->newLine(2);

        $processed = $total - $errors;
        $this->info("Done. {$processed}/{$total} blocks generated." . ($errors > 0 ? " ({$errors} errors)" : ''));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
