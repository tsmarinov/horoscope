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
                // Test profile — 15 March 1985, 02:15, London
                'birth_date'             => '1985-03-15',
                'birth_time'             => '02:15',
                'birth_time_approximate' => false,
                'birth_city_id'          => 24924, // London, Europe/London
            ]
        );

        $this->command->info('Test user ready — email: test@horo.test / password: password');
    }
}
