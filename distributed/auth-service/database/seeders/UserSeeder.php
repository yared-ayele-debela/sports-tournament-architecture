<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Create Administrators
        for ($i = 1; $i <= 3; $i++) {
            $admin = User::firstOrCreate(
                ['email' => "admin{$i}@test.com"],
                [
                    'name' => "Administrator {$i}",
                    'email' => "admin{$i}@test.com",
                    'password' => $password,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Assign Administrator role
            $admin->roles()->attach(1); // Administrator role ID
        }

        // Create Coaches
        for ($i = 1; $i <= 20; $i++) {
            $coach = User::firstOrCreate(
                ['email' => "coach{$i}@test.com"],
                [
                    'name' => "Coach {$i}",
                    'email' => "coach{$i}@test.com",
                    'password' => $password,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Assign Coach role
            $coach->roles()->attach(2); // Coach role ID
        }

        // Create Referees
        for ($i = 1; $i <= 10; $i++) {
            $referee = User::firstOrCreate(
                ['email' => "referee{$i}@test.com"],
                [
                    'name' => "Referee {$i}",
                    'email' => "referee{$i}@test.com",
                    'password' => $password,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Assign Referee role
            $referee->roles()->attach(3); // Referee role ID
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Created: 3 Administrators, 20 Coaches, 10 Referees');
        $this->command->info('All users have password: "password"');
    }
}
