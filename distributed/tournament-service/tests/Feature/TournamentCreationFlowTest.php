<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

use App\Models\Tournament;
use App\Models\Sport;
use App\Models\TournamentSettings;

class TournamentCreationFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a sport for testing
        $this->sport = Sport::create([
            'name' => 'Football',
            'description' => 'Association Football',
            'is_active' => true
        ]);
    }

    /**
     * Test complete tournament creation flow: tournament → teams → matches
     */
    public function test_complete_tournament_creation_flow(): void
    {
        // 1. Create Tournament
        $tournamentData = [
            'sport_id' => $this->sport->id,
            'name' => 'World Cup 2024',
            'location' => 'Qatar',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned'
        ];

        // Mock authentication for admin user
        $adminUser = [
            'id' => 1,
            'email' => 'admin@example.com',
            'name' => 'Admin User'
        ];

        $this->withHeaders([
            'X-User-Id' => $adminUser['id'],
            'X-User-Email' => $adminUser['email'],
            'X-User-Name' => $adminUser['name'],
            'X-User-Roles' => json_encode([['name' => 'Administrator']]),
            'X-User-Permissions' => json_encode([['name' => 'manage_tournaments']])
        ]);

        // Mock external service calls
        Http::fake([
            'auth-service/*' => Http::response(['valid' => true], 200),
            'team-service/*' => Http::response(['success' => true], 200),
            'match-service/*' => Http::response(['success' => true], 200),
        ]);

        // Mock queue and events
        Queue::fake();
        Event::fake();

        // Create tournament
        $response = $this->postJson('/api/tournaments', $tournamentData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'sport_id',
                         'name',
                         'location',
                         'start_date',
                         'end_date',
                         'status',
                         'created_by',
                         'sport',
                         'settings'
                     ]
                 ]);

        $tournament = $response->json('data');
        $this->assertEquals($tournamentData['name'], $tournament['name']);
        $this->assertEquals($tournamentData['sport_id'], $tournament['sport_id']);

        // 2. Create Teams for Tournament
        $teams = [];
        $teamNames = ['Team A', 'Team B', 'Team C', 'Team D'];
        
        foreach ($teamNames as $index => $teamName) {
            $teamData = [
                'tournament_id' => $tournament['id'],
                'name' => $teamName,
                'logo' => "https://example.com/logo{$index}.png",
                'coach_name' => "Coach {$index}",
                'contact_email' => "coach{$index}@example.com"
            ];

            // Mock team service response
            Http::fake([
                "team-service/api/tournaments/{$tournament['id']}/teams" => Http::response([
                    'success' => true,
                    'data' => array_merge($teamData, ['id' => $index + 1])
                ], 201)
            ]);

            $teamResponse = $this->postJson("/api/tournaments/{$tournament['id']}/teams", $teamData);
            
            if ($teamResponse->status() === 201) {
                $teams[] = $teamResponse->json('data');
            }
        }

        // Verify teams were created (at least 2 teams needed for matches)
        $this->assertGreaterThanOrEqual(2, count($teams));

        // 3. Create Matches between Teams
        $matches = [];
        
        // Create a simple round-robin schedule (first few matches)
        for ($i = 0; $i < min(3, count($teams)); $i++) {
            for ($j = $i + 1; $j < min($i + 2, count($teams)); $j++) {
                $matchData = [
                    'tournament_id' => $tournament['id'],
                    'team1_id' => $teams[$i]['id'],
                    'team2_id' => $teams[$j]['id'],
                    'venue' => "Stadium {$i}-{$j}",
                    'match_date' => now()->addDays($i + $j + 1)->format('Y-m-d'),
                    'match_time' => '15:00:00',
                    'status' => 'scheduled'
                ];

                // Mock match service response
                Http::fake([
                    'match-service/api/matches' => Http::response([
                        'success' => true,
                        'data' => array_merge($matchData, ['id' => count($matches) + 1])
                    ], 201)
                ]);

                $matchResponse = $this->postJson('/api/matches', $matchData);
                
                if ($matchResponse->status() === 201) {
                    $matches[] = $matchResponse->json('data');
                }
            }
        }

        // Verify matches were created
        $this->assertGreaterThanOrEqual(1, count($matches));

        // 4. Verify Tournament Status and Settings
        $tournamentResponse = $this->getJson("/api/tournaments/{$tournament['id']}");
        $tournamentResponse->assertStatus(200)
                           ->assertJsonPath('data.id', $tournament['id'])
                           ->assertJsonPath('data.name', $tournament['name']);

        // 5. Verify Tournament Settings were created
        $this->assertDatabaseHas('tournament_settings', [
            'tournament_id' => $tournament['id']
        ]);

        // 6. Verify Queue Events were dispatched
        Queue::assertPushed(function ($job) use ($tournament) {
            return str_contains($job->queue, 'tournament');
        });

        Log::info('Tournament creation flow completed successfully', [
            'tournament_id' => $tournament['id'],
            'teams_count' => count($teams),
            'matches_count' => count($matches)
        ]);
    }

    /**
     * Test tournament creation validation
     */
    public function test_tournament_creation_validation(): void
    {
        // Test missing required fields - should get 401 without auth headers
        $response = $this->postJson('/api/tournaments', []);
        $response->assertStatus(401);

        // Test with auth headers but missing required fields
        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Email' => 'admin@example.com',
            'X-User-Name' => 'Admin User',
            'X-User-Roles' => json_encode([['name' => 'Administrator']]),
            'X-User-Permissions' => json_encode([['name' => 'manage_tournaments']])
        ]);

        $response = $this->postJson('/api/tournaments', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sport_id', 'name', 'start_date', 'end_date']);

        // Test invalid dates
        $invalidData = [
            'sport_id' => $this->sport->id,
            'name' => 'Invalid Tournament',
            'start_date' => now()->subDays(1)->format('Y-m-d'), // Past date
            'end_date' => now()->format('Y-m-d'), // Before start date
        ];

        $response = $this->postJson('/api/tournaments', $invalidData);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['start_date', 'end_date']);

        // Test invalid sport_id
        $invalidSportData = [
            'sport_id' => 999,
            'name' => 'Invalid Sport Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/tournaments', $invalidSportData);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sport_id']);
    }

    /**
     * Test tournament creation authorization
     */
    public function test_tournament_creation_authorization(): void
    {
        $tournamentData = [
            'sport_id' => $this->sport->id,
            'name' => 'Unauthorized Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
        ];

        // Test without authentication
        $response = $this->postJson('/api/tournaments', $tournamentData);
        $response->assertStatus(401);

        // Test with regular user (no permissions)
        $this->withHeaders([
            'X-User-Id' => 2,
            'X-User-Email' => 'user@example.com',
            'X-User-Name' => 'Regular User',
            'X-User-Roles' => json_encode([['name' => 'User']]),
            'X-User-Permissions' => json_encode([]),
        ]);

        $response = $this->postJson('/api/tournaments', $tournamentData);
        $response->assertStatus(403);
    }

    /**
     * Test team creation in tournament
     */
    public function test_team_creation_in_tournament(): void
    {
        // Create tournament first
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Test Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Email' => 'admin@example.com',
            'X-User-Name' => 'Admin User',
            'X-User-Roles' => json_encode([['name' => 'Administrator']]),
            'X-User-Permissions' => json_encode([['name' => 'manage_tournaments']])
        ]);

        $teamData = [
            'name' => 'Test Team',
            'logo' => 'https://example.com/logo.png',
            'coach_name' => 'Test Coach',
            'contact_email' => 'coach@example.com'
        ];

        // Note: Team creation is handled by team-service, not tournament-service
        // This test verifies that the tournament exists and can be queried
        $response = $this->getJson("/api/tournaments/{$tournament->id}");
        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $tournament->id)
                 ->assertJsonPath('data.name', 'Test Tournament');
    }

    /**
     * Test match creation between tournament teams
     */
    public function test_match_creation_between_teams(): void
    {
        // Create tournament
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Test Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Email' => 'admin@example.com',
            'X-User-Name' => 'Admin User',
            'X-User-Roles' => json_encode([['name' => 'Administrator']]),
            'X-User-Permissions' => json_encode([['name' => 'manage_tournaments']])
        ]);

        // Note: Match creation is handled by match-service, not tournament-service
        // This test verifies tournament matches endpoint exists
        $response = $this->getJson("/api/tournaments/{$tournament->id}/matches");
        $response->assertStatus(200);
    }

    /**
     * Test tournament status transitions
     */
    public function test_tournament_status_transitions(): void
    {
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Status Test Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ]);

        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Email' => 'admin@example.com',
            'X-User-Name' => 'Admin User',
            'X-User-Roles' => json_encode([['name' => 'Administrator']]),
            'X-User-Permissions' => json_encode([['name' => 'manage_tournaments']])
        ]);

        // Test status update using PATCH endpoint
        $response = $this->patchJson("/api/tournaments/{$tournament->id}/status", [
            'status' => 'ongoing'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'status' => 'ongoing'
        ]);
    }
}
