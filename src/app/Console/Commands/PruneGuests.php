<?php

namespace App\Console\Commands;

use App\Models\Guest;
use Illuminate\Console\Command;

class PruneGuests extends Command
{
    protected $signature   = 'guests:prune {--dry-run : Show count without deleting}';
    protected $description = 'Delete guests inactive for 100+ days (cascades to profiles and all related data)';

    public function handle(): int
    {
        $cutoff = now()->subDays(100);
        $query  = Guest::where('last_seen_at', '<', $cutoff);
        $count  = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} inactive guest(s) (last seen before {$cutoff->toDateString()}).");
            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Deleted {$count} inactive guest(s).");

        return self::SUCCESS;
    }
}
