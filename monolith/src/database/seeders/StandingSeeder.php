<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;

class StandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::all();
        $tournaments = Tournament::all();

        if ($teams->count() < 4 || $tournaments->count() < 2) {
            $this->command->error('Insufficient data to create standings. Please run other seeders first.');
            return;
        }

        // Create standings for National Championship
        $nationalChampionship = $tournaments->where('name', 'National Championship 2024')->first();
        $nationalTeams = $teams->take(8);

        $this->createTournamentStandings($nationalChampionship, $nationalTeams);

        // Create standings for City Cup
        $cityCup = $tournaments->where('name', 'City Cup 2024')->first();
        $cityCupTeams = $teams->slice(8, 4);
        
        if ($cityCupTeams->count() > 0) {
            $this->createTournamentStandings($cityCup, $cityCupTeams);
        }

        $this->command->info('Standings seeded successfully!');
    }

    private function createTournamentStandings($tournament, $teams)
    {
        $teamArray = $teams->toArray();
        
        if (empty($teamArray)) {
            return;
        }
        
        // Create realistic standings with varying points
        $standings = [];
        $points = [15, 12, 10, 8, 6, 4, 3, 1]; // Realistic point distribution
        
        foreach ($teamArray as $index => $team) {
            $teamPoints = $points[$index] ?? 0;
            
            $standings[] = [
                'tournament_id' => $tournament->id,
                'team_id' => $team['id'],
                'played' => rand(3, 6),
                'won' => $teamPoints >= 12 ? rand(3, 4) : ($teamPoints >= 8 ? rand(2, 3) : ($teamPoints >= 4 ? rand(1, 2) : 0)),
                'drawn' => $teamPoints >= 8 ? rand(0, 2) : rand(0, 1),
                'lost' => $teamPoints >= 12 ? rand(0, 1) : ($teamPoints >= 8 ? rand(1, 2) : ($teamPoints >= 4 ? rand(2, 4) : rand(3, 6))),
                'goals_for' => rand(5, 20),
                'goals_against' => rand(2, 15),
                'goal_difference' => 0, // Will be calculated
                'points' => $teamPoints,
                'position' => $index + 1,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Calculate goal differences
        foreach ($standings as &$standing) {
            $standing['goal_difference'] = $standing['goals_for'] - $standing['goals_against'];
        }

        // Insert standings
        foreach ($standings as $standingData) {
            Standing::firstOrCreate([
                'tournament_id' => $standingData['tournament_id'],
                'team_id' => $standingData['team_id']
            ], $standingData);
        }
    }
}
