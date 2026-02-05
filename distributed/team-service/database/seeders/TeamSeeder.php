<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Services\TournamentServiceClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('team_coach')->delete();
        Team::query()->delete();

        // Fetch tournaments from Tournament Service
        try {
            $response = Http::get(config('http://localhost:8002') . '/api/tournaments');

            if ($response->status() !== 200) {
                $this->command->error('Failed to fetch tournaments from Tournament Service');
                return;
            }

            $tournaments = $response->json()['data'] ?? [];

            if (empty($tournaments)) {
                $this->command->error('No tournaments found');
                return;
            }

            // Take only first 2 tournaments
            $tournaments = array_slice($tournaments, 0, 2);

            $teamNames = [
                'Team Alpha', 'Team Beta', 'Team Gamma', 'Team Delta',
                'Team Epsilon', 'Team Zeta', 'Team Eta', 'Team Theta'
            ];

            foreach ($tournaments as $tournament) {
                $this->command->info("Creating teams for tournament: {$tournament['name']}");

                foreach ($teamNames as $index => $teamName) {
                    $team = Team::create([
                        'tournament_id' => $tournament['id'],
                        'name' => $teamName,
                        'logo' => "https://via.placeholder.com/150x150/4F46E5/FFFFFF?text=" . substr($teamName, -1),
                    ]);

                    // Assign random coach (user_id 4-21)
                    $coachId = rand(4, 21);
                    $team->coaches()->attach($coachId);

                    $this->command->info("Created {$teamName} with coach ID: {$coachId}");
                }
            }

        } catch (\Exception $e) {
            $this->command->error('Error creating teams: ' . $e->getMessage());
        }
    }
}
