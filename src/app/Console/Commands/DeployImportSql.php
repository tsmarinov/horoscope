<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Import deploy SQL files into the database.
 *
 * Usage:
 *   php artisan deploy:import-sql              — import all files in deploy/sql/
 *   php artisan deploy:import-sql --file=natal — import text_blocks_natal.sql.gz (or exact filename)
 *   php artisan deploy:import-sql --dry-run    — show what would be imported
 */
class DeployImportSql extends Command
{
    protected $signature = 'deploy:import-sql
                            {--file=    : File name or partial match (e.g. natal, transit, cities)}
                            {--dry-run  : Show files that would be imported without running them}';

    protected $description = 'Import deploy/sql/*.sql.gz files into the database';

    public function handle(): int
    {
        $deployDir = '/var/www/deploy/sql';

        if (! is_dir($deployDir)) {
            $this->error("Deploy directory not found: {$deployDir}");
            return self::FAILURE;
        }

        $files = glob($deployDir . '/*.sql.gz');
        sort($files);

        if (empty($files)) {
            $this->warn('No .sql.gz files found in ' . $deployDir);
            return self::SUCCESS;
        }

        // Filter by --file if provided
        if ($filter = $this->option('file')) {
            $files = array_filter($files, fn ($f) => str_contains(basename($f), $filter));
            if (empty($files)) {
                $this->error("No files matching '{$filter}'");
                return self::FAILURE;
            }
        }

        $dryRun = $this->option('dry-run');

        $db   = config('database.connections.mysql');
        $host = $db['host'];
        $port = $db['port'] ?? 3306;
        $user = $db['username'];
        $pass = $db['password'];
        $name = $db['database'];

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Importing ' . count($files) . ' file(s)...');

        $ok = 0;
        foreach ($files as $file) {
            $base = basename($file);
            $size = $this->humanSize(filesize($file));

            if ($dryRun) {
                $this->line("  → {$base} ({$size})");
                continue;
            }

            $this->line("  → {$base} ({$size}) ...");

            $cmd = "zcat " . escapeshellarg($file) . " | mysql"
                . " -h " . escapeshellarg($host)
                . " -P " . (int) $port
                . " -u " . escapeshellarg($user)
                . " -p" . escapeshellarg($pass)
                . " " . escapeshellarg($name)
                . " 2>&1";

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                $this->error("    FAILED: " . implode("\n", $output));
                return self::FAILURE;
            }

            $this->line("    ✓ done");
            $ok++;
        }

        if (! $dryRun) {
            $this->info("Imported {$ok} file(s) successfully.");
        }

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
