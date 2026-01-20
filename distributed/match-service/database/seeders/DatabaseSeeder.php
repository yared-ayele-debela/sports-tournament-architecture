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
        $this->command->info('Starting Match Service database seeding...');

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed matches first (required for events and reports)
        $this->call([
            MatchSeeder::class,
            MatchEventSeeder::class,
            MatchReportSeeder::class,
        ]);

        $this->command->info('Match Service database seeding completed!');
    }
}
