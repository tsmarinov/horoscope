<?php

namespace App\Console\Commands\Test;

use App\Facades\AspectCalculator;
use App\Models\User;
use Illuminate\Console\Command;

class HoroscopeTest extends Command
{
    protected $signature = 'horoscope:test
                            {--email=test@horo.test : User email to test with}';

    protected $description = 'Run horoscope test calculations for a user';


    public function handle(): int
    {
        $user = User::with('profile.birthCity')
            ->where('email', $this->option('email'))
            ->first();

        if (! $user) {
            $this->error('User not found: ' . $this->option('email'));
            return self::FAILURE;
        }

        $profile = $user->profile;

        $this->info("=== Natal Chart: {$user->name} ===");
        $this->line("Birth date : {$user->getBirthDate()}");
        $this->line("Birth time : " . ($user->getBirthTime() ?? '(unknown)'));
        $this->line("City       : " . ($profile?->birthCity?->name ?? '(unknown)'));
        $this->line("Timezone   : " . ($profile?->birthCity?->timezone ?? 'UTC'));
        $this->line("Chart tier : {$user->getChartTier()}");
        $this->newLine();

        $result = AspectCalculator::calculate($user);

        dump($result);

        return self::SUCCESS;
    }
}
