<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

use App\Models\Tournament;
use App\Models\Sport;
use App\Models\TournamentSettings;

class TournamentIntegrationTest extends TestCase
{
    use RefreshDatabase;

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
     * Test complete tournament creation flow simulation
     */
    public function test_complete_tournament_flow_simulation(): void
    {
        // 1. Create Tournament
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'World Cup 2024',
            'location' => 'Qatar',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ]);

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals('World Cup 2024', $tournament->name);
        $this->assertEquals('planned', $tournament->status);

        // 2. Create Tournament Settings
        $settings = TournamentSettings::create([
            'tournament_id' => $tournament->id,
            'match_duration' => 90,
            'win_rest_time' => 15,
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00'
        ]);

        $this->assertInstanceOf(TournamentSettings::class, $settings);
        $this->assertEquals($tournament->id, $settings->tournament_id);

        // 3. Simulate Team Creation (would normally call team-service)
        $teams = [
            ['id' => 1, 'name' => 'Brazil', 'tournament_id' => $tournament->id],
            ['id' => 2, 'name' => 'Argentina', 'tournament_id' => $tournament->id],
            ['id' => 3, 'name' => 'France', 'tournament_id' => $tournament->id],
            ['id' => 4, 'name' => 'Germany', 'tournament_id' => $tournament->id]
        ];

        // 4. Simulate Match Creation (would normally call match-service)
        $matches = [
            [
                'id' => 1,
                'tournament_id' => $tournament->id,
                'team1_id' => 1, // Brazil
                'team2_id' => 2, // Argentina
                'venue' => 'Stadium 1',
                'match_date' => now()->addDays(35)->format('Y-m-d'),
                'match_time' => '15:00:00',
                'status' => 'scheduled'
            ],
            [
                'id' => 2,
                'tournament_id' => $tournament->id,
                'team1_id' => 3, // France
                'team2_id' => 4, // Germany
                'venue' => 'Stadium 2',
                'match_date' => now()->addDays(36)->format('Y-m-d'),
                'match_time' => '16:00:00',
                'status' => 'scheduled'
            ]
        ];

        // 5. Verify Tournament Status Progression
        $this->assertEquals('planned', $tournament->status);

        // Simulate tournament starting
        $tournament->update(['status' => 'ongoing']);
        $this->assertEquals('ongoing', $tournament->fresh()->status);

        // Simulate matches being played
        foreach ($matches as $match) {
            $match['status'] = 'completed';
            // In real flow, this would call match-service to update match status
        }

        // Simulate tournament completion
        $tournament->update(['status' => 'completed']);
        $this->assertEquals('completed', $tournament->fresh()->status);

        // 6. Verify Data Integrity
        $this->assertEquals($this->sport->id, $tournament->sport_id);
        $this->assertEquals('World Cup 2024', $tournament->name);
        $this->assertEquals('Qatar', $tournament->location);
        $this->assertEquals('completed', $tournament->status);
        $this->assertEquals(1, $tournament->created_by);

        // Verify settings are still accessible
        $tournamentSettings = $tournament->fresh()->settings;
        $this->assertInstanceOf(TournamentSettings::class, $tournamentSettings);
        $this->assertEquals(90, $tournamentSettings->match_duration);

        // 7. Test Tournament Retrieval
        $retrievedTournament = Tournament::with(['sport', 'settings'])
                                        ->find($tournament->id);

        $this->assertNotNull($retrievedTournament);
        $this->assertEquals('World Cup 2024', $retrievedTournament->name);
        $this->assertEquals('Football', $retrievedTournament->sport->name);
        $this->assertEquals(90, $retrievedTournament->settings->match_duration);
    }

    /**
     * Test tournament with different sport types
     */
    public function test_tournament_with_different_sports(): void
    {
        // Create additional sports
        $basketball = Sport::create([
            'name' => 'Basketball',
            'description' => 'Basketball Sport',
            'is_active' => true
        ]);

        $tennis = Sport::create([
            'name' => 'Tennis',
            'description' => 'Tennis Sport',
            'is_active' => true
        ]);

        // Create tournaments for different sports
        $footballTournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Football Championship',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $basketballTournament = Tournament::create([
            'sport_id' => $basketball->id,
            'name' => 'Basketball Championship',
            'start_date' => now()->addDays(45)->format('Y-m-d'),
            'end_date' => now()->addDays(75)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $tennisTournament = Tournament::create([
            'sport_id' => $tennis->id,
            'name' => 'Tennis Open',
            'start_date' => now()->addDays(60)->format('Y-m-d'),
            'end_date' => now()->addDays(90)->format('Y-m-d'),
            'created_by' => 1
        ]);

        // Create settings for each tournament with sport-specific configurations
        TournamentSettings::create([
            'tournament_id' => $footballTournament->id,
            'match_duration' => 90, // 90 minutes for football
            'win_rest_time' => 15,
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00'
        ]);

        TournamentSettings::create([
            'tournament_id' => $basketballTournament->id,
            'match_duration' => 48, // 48 minutes for basketball
            'win_rest_time' => 10,
            'daily_start_time' => '10:00',
            'daily_end_time' => '20:00'
        ]);

        TournamentSettings::create([
            'tournament_id' => $tennisTournament->id,
            'match_duration' => 180, // 3 hours for tennis matches
            'win_rest_time' => 30,
            'daily_start_time' => '08:00',
            'daily_end_time' => '22:00'
        ]);

        // Verify all tournaments are created correctly
        $allTournaments = Tournament::with(['sport', 'settings'])->get();
        $this->assertCount(3, $allTournaments);

        // Verify sport-specific settings
        $footballSettings = $footballTournament->fresh()->settings;
        $basketballSettings = $basketballTournament->fresh()->settings;
        $tennisSettings = $tennisTournament->fresh()->settings;

        $this->assertEquals(90, $footballSettings->match_duration);
        $this->assertEquals(48, $basketballSettings->match_duration);
        $this->assertEquals(180, $tennisSettings->match_duration);
    }

    /**
     * Test tournament lifecycle management
     */
    public function test_tournament_lifecycle_management(): void
    {
        // Create tournament
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Lifecycle Test Tournament',
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(20)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ]);

        // Create settings
        TournamentSettings::create([
            'tournament_id' => $tournament->id,
            'match_duration' => 90,
            'win_rest_time' => 15,
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00'
        ]);

        // Test planned phase
        $this->assertEquals('planned', $tournament->status);
        
        // Simulate tournament announcement and team registration
        $this->assertTrue($tournament->start_date->isFuture());
        $this->assertTrue($tournament->end_date->isFuture());

        // Test transition to ongoing
        $tournament->update(['status' => 'ongoing']);
        $this->assertEquals('ongoing', $tournament->fresh()->status);

        // Simulate match scheduling and execution
        // In real flow, this would involve:
        // - Creating matches via match-service
        - // - Updating match results
        // - Calculating standings via results-service

        // Test transition to completed
        $tournament->update(['status' => 'completed']);
        $this->assertEquals('completed', $tournament->fresh()->status);

        // Verify tournament data integrity after completion
        $completedTournament = Tournament::with(['sport', 'settings'])
                                        ->find($tournament->id);
        
        $this->assertEquals('Lifecycle Test Tournament', $completedTournament->name);
        $this->assertEquals('completed', $completedTournament->status);
        $this->assertEquals(90, $completedTournament->settings->match_duration);
    }
}
