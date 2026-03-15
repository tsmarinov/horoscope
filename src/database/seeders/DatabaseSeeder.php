<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TestUserSeeder::class);
        $this->call(WeekdayTextsSeeder::class);
        $this->call(SynastryTypeSeeder::class);
        $this->call(SynastryIntroSeeder::class);
        $this->call(SynastryPlanetHouseSeeder::class);
        $this->call(SynastryAscHouseSeeder::class);
        $this->call(SynastryShortTextsSeeder::class);
    }
}
