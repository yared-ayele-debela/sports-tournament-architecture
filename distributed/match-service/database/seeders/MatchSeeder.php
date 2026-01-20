<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MatchGame;
use Carbon\Carbon;

class MatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding matches...');

        // Winter Cup 2025 - 8 teams, 28 matches, all completed
        $this->createWinterCup2025();

        // Spring Championship 2026 - full schedule with mixed statuses
        $this->createSpringChampionship2026();
    }

    private function createWinterCup2025(): void
    {
        $teams = [1, 2, 3, 4, 5, 6, 7, 8]; // 8 teams
        $venues = [1, 2, 3, 4]; // 4 venues
        $referees = [1, 2, 3, 4, 5]; // 5 referees

        $matches = [];
        $matchDate = Carbon::create(2025, 1, 15, 14, 0); // Start Jan 15, 2025

        // Round-robin tournament: each team plays every other team once
        for ($i = 0; $i < count($teams); $i++) {
            for ($j = $i + 1; $j < count($teams); $j++) {
                $homeTeam = $teams[$i];
                $awayTeam = $teams[$j];
                
                // Generate realistic scores
                $homeScore = rand(0, 4);
                $awayScore = rand(0, 4);
                
                $matches[] = [
                    'tournament_id' => 1, // Winter Cup 2025
                    'venue_id' => $venues[array_rand($venues)],
                    'home_team_id' => $homeTeam,
                    'away_team_id' => $awayTeam,
                    'referee_id' => $referees[array_rand($referees)],
                    'match_date' => $matchDate->copy()->addMinutes(count($matches) * 120),
                    'round_number' => floor(count($matches) / 4) + 1,
                    'status' => 'completed',
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'current_minute' => 90,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        MatchGame::insert($matches);
        $this->command->info('Winter Cup 2025: ' . count($matches) . ' matches seeded');
    }

    private function createSpringChampionship2026(): void
    {
        $teams = [9, 10, 11, 12, 13, 14, 15, 16]; // 8 different teams
        $venues = [5, 6, 7, 8]; // 4 different venues
        $referees = [6, 7, 8, 9, 10]; // 5 different referees

        $matches = [];
        $matchDate = Carbon::create(2026, 3, 1, 15, 0); // Start March 1, 2026

        // Generate round-robin matches
        for ($i = 0; $i < count($teams); $i++) {
            for ($j = $i + 1; $j < count($teams); $j++) {
                $homeTeam = $teams[$i];
                $awayTeam = $teams[$j];
                
                $matches[] = [
                    'tournament_id' => 2, // Spring Championship 2026
                    'venue_id' => $venues[array_rand($venues)],
                    'home_team_id' => $homeTeam,
                    'away_team_id' => $awayTeam,
                    'referee_id' => $referees[array_rand($referees)],
                    'match_date' => $matchDate->copy()->addMinutes(count($matches) * 150),
                    'round_number' => floor(count($matches) / 4) + 1,
                    'status' => 'scheduled', // Will be updated below
                    'home_score' => null,
                    'away_score' => null,
                    'current_minute' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Update statuses: first 10 completed, next 5 in_progress, rest scheduled
        foreach ($matches as $index => &$match) {
            if ($index < 10) {
                $match['status'] = 'completed';
                $match['home_score'] = rand(0, 4);
                $match['away_score'] = rand(0, 4);
                $match['current_minute'] = 90;
            } elseif ($index < 15) {
                $match['status'] = 'in_progress';
                $match['home_score'] = rand(0, 2);
                $match['away_score'] = rand(0, 2);
                $match['current_minute'] = rand(20, 75);
            }
            // Rest remain 'scheduled'
        }

        MatchGame::insert($matches);
        $this->command->info('Spring Championship 2026: ' . count($matches) . ' matches seeded');
    }
}
