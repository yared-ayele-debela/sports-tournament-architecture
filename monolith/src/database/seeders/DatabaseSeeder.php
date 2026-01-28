<?php

namespace Database\Seeders;

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
        $this->command->info('Starting database seeding...');
        
        // Phase 1: Core data
        $this->command->info('Phase 1: Seeding core data...');
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Phase 2: Sports and Venues
        $this->command->info('Phase 2: Seeding sports and venues...');
        $this->call([
            SportSeeder::class,
            VenueSeeder::class,
        ]);

        // Phase 3: Users
        $this->command->info('Phase 3: Seeding users...');
        $this->call([
            UserSeeder::class,
        ]);

        // Phase 4: Tournaments
        $this->command->info('Phase 4: Seeding tournaments...');
        $this->call([
            TournamentSeeder::class,
        ]);

        // Phase 5: Teams
        $this->command->info('Phase 5: Seeding teams...');
        $this->call([
            TeamSeeder::class,
        ]);

        // Phase 6: Players
        $this->command->info('Phase 6: Seeding players...');
        $this->call([
            PlayerSeeder::class,
        ]);

        // Phase 7: Matches
        $this->command->info('Phase 7: Seeding matches...');
        $this->call([
            MatchSeeder::class,
        ]);

        // Phase 8: Match Events
        $this->command->info('Phase 8: Seeding match events...');
        $this->call([
            MatchEventSeeder::class,
        ]);

        // Phase 9: Standings
        $this->command->info('Phase 9: Seeding standings...');
        $this->call([
            StandingSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('Admin: admin@tournament.com / password');
        $this->command->info('Coach: coach1@tournament.com / password');
        $this->command->info('Referee: referee1@tournament.com / password');
        $this->command->info('============================');
    }
}
