<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
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
                'name'                   => 'Test Exact Time',
                'birth_date'             => '1985-03-15',
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
                'name'                   => 'Test No Time',
                'birth_date'             => '1990-06-21',
                'birth_time'    => null,
                'birth_city_id' => 22765, // Paris, Europe/Paris
            ]
        );

        $this->command->info('Test user ready — email: test@horo.test / password: password');
    }
}
