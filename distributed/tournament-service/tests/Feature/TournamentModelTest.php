<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

use App\Models\Tournament;
use App\Models\Sport;
use App\Models\TournamentSettings;

class TournamentModelTest extends TestCase
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
     * Test tournament model creation and relationships
     */
    public function test_tournament_model_creation(): void
    {
        $tournamentData = [
            'sport_id' => $this->sport->id,
            'name' => 'World Cup 2024',
            'location' => 'Qatar',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ];

        $tournament = Tournament::create($tournamentData);

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals($tournamentData['name'], $tournament->name);
        $this->assertEquals($tournamentData['sport_id'], $tournament->sport_id);
        $this->assertEquals($tournamentData['location'], $tournament->location);
        $this->assertEquals($tournamentData['status'], $tournament->status);
        $this->assertEquals($tournamentData['created_by'], $tournament->created_by);

        // Test relationship with sport
        $this->assertInstanceOf(Sport::class, $tournament->sport);
        $this->assertEquals($this->sport->name, $tournament->sport->name);

        // Create tournament settings manually
        $settings = TournamentSettings::create([
            'tournament_id' => $tournament->id,
            'match_duration' => 90,
            'win_rest_time' => 15,
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00'
        ]);

        // Test tournament settings relationship
        $this->assertInstanceOf(TournamentSettings::class, $tournament->fresh()->settings);
        $this->assertEquals($tournament->id, $tournament->fresh()->settings->tournament_id);
    }

    /**
     * Test tournament validation rules
     */
    public function test_tournament_validation_rules(): void
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        Tournament::create([]);

        // Test invalid sport_id
        $this->expectException(\Illuminate\Database\QueryException::class);
        Tournament::create([
            'sport_id' => 999, // Non-existent sport
            'name' => 'Test Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        // Test valid tournament creation
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Valid Tournament',
            'location' => 'Test Location',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ]);

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals('Valid Tournament', $tournament->name);
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

        // Test initial status
        $this->assertEquals('planned', $tournament->status);

        // Test status change to ongoing
        $tournament->status = 'ongoing';
        $tournament->save();
        $this->assertEquals('ongoing', $tournament->fresh()->status);

        // Test status change to completed
        $tournament->status = 'completed';
        $tournament->save();
        $this->assertEquals('completed', $tournament->fresh()->status);

        // Test status change to cancelled
        $tournament->status = 'cancelled';
        $tournament->save();
        $this->assertEquals('cancelled', $tournament->fresh()->status);
    }

    /**
     * Test tournament scopes and queries
     */
    public function test_tournament_scopes(): void
    {
        // Create tournaments with different statuses
        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Planned Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => 'planned',
            'created_by' => 1
        ]);

        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Ongoing Tournament',
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'ongoing',
            'created_by' => 1
        ]);

        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Completed Tournament',
            'start_date' => now()->subDays(60)->format('Y-m-d'),
            'end_date' => now()->subDays(30)->format('Y-m-d'),
            'status' => 'completed',
            'created_by' => 1
        ]);

        // Test filtering by status
        $plannedTournaments = Tournament::where('status', 'planned')->get();
        $this->assertCount(1, $plannedTournaments);
        $this->assertEquals('Planned Tournament', $plannedTournaments->first()->name);

        $ongoingTournaments = Tournament::where('status', 'ongoing')->get();
        $this->assertCount(1, $ongoingTournaments);
        $this->assertEquals('Ongoing Tournament', $ongoingTournaments->first()->name);

        $completedTournaments = Tournament::where('status', 'completed')->get();
        $this->assertCount(1, $completedTournaments);
        $this->assertEquals('Completed Tournament', $completedTournaments->first()->name);

        // Test total count
        $allTournaments = Tournament::all();
        $this->assertCount(3, $allTournaments);
    }

    /**
     * Test tournament with sport relationship
     */
    public function test_tournament_sport_relationship(): void
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

        // Test relationships
        $this->assertEquals('Football', $footballTournament->sport->name);
        $this->assertEquals('Basketball', $basketballTournament->sport->name);

        // Test sport has many tournaments
        $this->assertCount(1, $this->sport->tournaments);
        $this->assertCount(1, $basketball->tournaments);
    }

    /**
     * Test tournament settings functionality
     */
    public function test_tournament_settings(): void
    {
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Settings Test Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        // Create settings manually
        $settings = TournamentSettings::create([
            'tournament_id' => $tournament->id,
            'match_duration' => 90,
            'win_rest_time' => 15,
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00'
        ]);

        // Test settings relationship
        $this->assertInstanceOf(TournamentSettings::class, $tournament->fresh()->settings);
        $this->assertEquals($tournament->id, $settings->tournament_id);

        // Test updating settings
        $settings->update([
            'match_duration' => 120,
            'win_rest_time' => 20
        ]);

        $updatedSettings = $tournament->fresh()->settings;
        $this->assertEquals(120, $updatedSettings->match_duration);
        $this->assertEquals(20, $updatedSettings->win_rest_time);
        $this->assertEquals('09:00', $updatedSettings->daily_start_time->format('H:i'));
        $this->assertEquals('18:00', $updatedSettings->daily_end_time->format('H:i'));
    }

    /**
     * Test tournament date validation
     */
    public function test_tournament_date_validation(): void
    {
        // Test valid date range
        $tournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Valid Date Tournament',
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(20)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $this->assertTrue($tournament->start_date->lt($tournament->end_date));

        // Test same day tournament
        $sameDayTournament = Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'Same Day Tournament',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'created_by' => 1
        ]);

        $this->assertEquals($sameDayTournament->start_date, $sameDayTournament->end_date);
    }

    /**
     * Test tournament search functionality
     */
    public function test_tournament_search(): void
    {
        // Create test tournaments
        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'World Championship 2024',
            'location' => 'Qatar',
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'created_by' => 1
        ]);

        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'European Cup',
            'location' => 'Germany',
            'start_date' => now()->addDays(45)->format('Y-m-d'),
            'end_date' => now()->addDays(75)->format('Y-m-d'),
            'created_by' => 1
        ]);

        Tournament::create([
            'sport_id' => $this->sport->id,
            'name' => 'World Series',
            'location' => 'USA',
            'start_date' => now()->addDays(60)->format('Y-m-d'),
            'end_date' => now()->addDays(90)->format('Y-m-d'),
            'created_by' => 1
        ]);

        // Test search by name
        $worldTournaments = Tournament::where('name', 'LIKE', '%World%')->get();
        $this->assertCount(2, $worldTournaments);

        // Test search by location
        $qatarTournaments = Tournament::where('location', 'Qatar')->get();
        $this->assertCount(1, $qatarTournaments);
        $this->assertEquals('World Championship 2024', $qatarTournaments->first()->name);

        // Test combined search
        $europeanTournaments = Tournament::where('name', 'LIKE', '%European%')
                                        ->where('location', 'Germany')
                                        ->get();
        $this->assertCount(1, $europeanTournaments);
    }
}
