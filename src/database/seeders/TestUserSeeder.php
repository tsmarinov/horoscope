<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@horo.test'],
            [
                'name'     => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                // Test profile 1 — 15 March 1985, 02:15 exact, London
                'first_name'    => 'Test',
                'last_name'     => 'Exact Time',
                'gender'        => Gender::Male,
                'birth_date'    => '1985-03-15',
                'birth_time'    => '02:15',
                'birth_city_id' => 24924, // London, Europe/London
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'test-notime@horo.test'],
            ['name' => 'Test No Time', 'password' => Hash::make('password')]
        );

        Profile::firstOrCreate(
            ['user_id' => $user2->id],
            [
                // Test profile 2 — 21 June 1990, no time, Paris
                'first_name'    => 'Test',
                'last_name'     => 'No Time',
                'gender'        => Gender::Female,
                'birth_date'    => '1990-06-21',
                'birth_time'    => null,
                'birth_city_id' => 22765, // Paris, Europe/Paris
            ]
        );

        $this->command->info('Test users ready — test@horo.test / test-notime@horo.test (password: password)');
    }
}
