<?php

namespace App\Jobs;

use App\Models\Profile;
use App\Services\ReportBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateNatalPortrait implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(private readonly Profile $profile) {}

    public function handle(ReportBuilder $builder): void
    {
        $this->profile->loadMissing('birthCity');

        try {
            $builder->buildNatalReportBoth($this->profile, 'en');
        } finally {
            Cache::forget("natal_portrait_generating_{$this->profile->id}");
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::forget("natal_portrait_generating_{$this->profile->id}");
    }
}
