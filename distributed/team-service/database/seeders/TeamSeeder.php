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

        // Fetch tournaments from Tournament Service (using public endpoint)
        try {
            $tournamentServiceUrl = config('services.tournament.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
            $response = Http::timeout(10)->get($tournamentServiceUrl . '/api/public/tournaments');

            if ($response->status() !== 200) {
                $this->command->error('Failed to fetch tournaments from Tournament Service');
                $this->command->error('Status: ' . $response->status());
                $this->command->error('Response: ' . $response->body());
                return;
            }

            $responseData = $response->json();

            // The API returns: {success: true, data: {data: [...tournaments...], pagination: {...}}}
            // So we need to access data.data to get the actual tournaments array
            $tournaments = $responseData['data']['data'] ?? [];

            if (empty($tournaments)) {
                $this->command->error('No tournaments found');
                $this->command->error('Response structure: ' . json_encode(array_keys($responseData)));
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
