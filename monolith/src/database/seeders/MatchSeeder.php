<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MatchModel;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Venue;
use App\Models\User;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();
        $venues = Venue::all();
        $tournaments = Tournament::all();

        if ($teams->count() < 4 || $venues->count() < 1 || $tournaments->count() < 1) {
            $this->command->error('Insufficient data. Seed teams, venues, and tournaments first.');
            return;
        }

        $nationalChampionship = $tournaments->firstWhere('name', 'National Championship 2024');
        $cityCup = $tournaments->firstWhere('name', 'City Cup 2024');

        if (!$nationalChampionship || !$cityCup) {
            $this->command->error('Required tournaments not found.');
            return;
        }

        // Split teams
        $nationalTeams = $teams->take(8);
        $cityCupTeams = $teams->slice(8, 4);

        $this->createTournamentMatches($nationalTeams, $venues, $nationalChampionship);
        $this->createTournamentMatches($cityCupTeams, $venues, $cityCup);

        $this->command->info('Matches seeded successfully!');
    }

    private function createTournamentMatches($teams, $venues, $tournament): void
    {
        if ($teams->count() < 2) {
            return;
        }

        $teams = $teams->values(); // reset indexes
        $teamCount = $teams->count();

        // Round-robin (home & away)
        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {

                // First leg
                MatchModel::firstOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'home_team_id' => $teams[$i]->id,
                        'away_team_id' => $teams[$j]->id,
                        'round_number' => 1,
                    ],
                    [
                        'venue_id' => $venues->random()->id,
                        'match_date' => now()->addDays(rand(10, 30))->setTime(rand(14, 20), 0),
                        'status' => 'scheduled',
                    ]
                );

                // Second leg
                MatchModel::firstOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'home_team_id' => $teams[$j]->id,
                        'away_team_id' => $teams[$i]->id,
                        'round_number' => 2,
                    ],
                    [
                        'venue_id' => $venues->random()->id,
                        'match_date' => now()->addDays(rand(35, 55))->setTime(rand(14, 20), 0),
                        'status' => 'scheduled',
                    ]
                );
            }
        }

        $this->createCompletedMatches($teams, $venues, $tournament);
    }

    private function createCompletedMatches($teams, $venues, $tournament): void
    {
        if ($teams->count() < 2) {
            return;
        }

        $matchesToCreate = min(4, floor($teams->count() / 2));

        for ($i = 0; $i < $matchesToCreate; $i++) {
            $home = $teams[$i * 2];
            $away = $teams[$i * 2 + 1];

            MatchModel::firstOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'round_number' => 0,
                ],
                [
                    'venue_id' => $venues->random()->id,
                    'match_date' => now()->subDays(rand(1, 7))->setTime(rand(14, 20), 0),
                    'home_score' => rand(0, 4),
                    'away_score' => rand(0, 4),
                    'status' => 'completed',
                ]
            );
        }
    }
}
