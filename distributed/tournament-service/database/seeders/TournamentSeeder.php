<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\TournamentSettings;
use App\Models\Sport;

class TournamentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run database seeds.
     */
    public function run(): void
    {
        // Get the soccer sport ID (assuming it's created first)
        $soccerSport = Sport::where('name', 'Soccer')->first();
        
        if (!$soccerSport) {
            $this->command->error('Soccer sport not found. Please run SportSeeder first.');
            return;
        }

        $tournaments = [
            [
                'sport_id' => $soccerSport->id,
                'name' => 'Spring Championship 2026',
                'location' => 'National Stadium, Addis Ababa',
                'start_date' => '2026-03-01',
                'end_date' => '2026-04-30',
                'status' => 'ongoing',
                'created_by' => 1, // Assuming user ID 1 exists
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sport_id' => $soccerSport->id,
                'name' => 'Winter Cup 2025',
                'location' => 'City Sports Complex, Addis Ababa',
                'start_date' => '2025-12-01',
                'end_date' => '2026-01-15',
                'status' => 'completed',
                'created_by' => 1, // Assuming user ID 1 exists
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
                'win_rest_time' => 15, // 15 minutes rest time
                'daily_start_time' => '09:00',
                'daily_end_time' => '18:00',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            TournamentSettings::create($settings);
        }

        $this->command->info('Tournaments and their settings seeded successfully!');
    }
}
