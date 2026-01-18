<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\TournamentSettings;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tournaments = [
            [
                'sport_id' => 1, // Soccer
                'name' => 'Spring Championship 2026',
                'location' => 'National Soccer Stadium',
                'start_date' => '2026-03-01',
                'end_date' => '2026-04-30',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sport_id' => 1, // Soccer
                'name' => 'Winter Cup 2025',
                'location' => 'Regional Sports Arena',
                'start_date' => '2025-12-01',
                'end_date' => '2026-01-15',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tournaments as $tournamentData) {
            $tournament = Tournament::create($tournamentData);

            // Create tournament settings for each tournament
            $settings = [
                'tournament_id' => $tournament->id,
                'match_duration' => 90, // 90 minutes for soccer
                'win_rest_time' => 15, // 15 minutes rest between matches
                'daily_start_time' => '09:00',
                'daily_end_time' => '18:00',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            TournamentSettings::create($settings);
        }

        $this->command->info('Tournaments and settings seeded successfully!');
    }
}
