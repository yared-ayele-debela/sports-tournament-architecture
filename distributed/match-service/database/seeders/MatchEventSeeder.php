<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MatchGame;
use App\Models\MatchEvent;

class MatchEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding match events...');

        // Get completed matches to generate events for
        $completedMatches = MatchGame::where('status', 'completed')->get();
        
        $events = [];

        foreach ($completedMatches as $match) {
            $totalGoals = $match->home_score + $match->away_score;
            
            // Generate goal events matching the final score
            $goalEvents = $this->generateGoalEvents($match, $totalGoals);
            
            // Generate card events
            $cardEvents = $this->generateCardEvents($match);
            
            $events = array_merge($events, $goalEvents, $cardEvents);
        }

        // Insert events in batches
        if (!empty($events)) {
            MatchEvent::insert($events);
            $this->command->info('Generated ' . count($events) . ' match events');
        }
    }

    private function generateGoalEvents($match, $totalGoals): array
    {
        $events = [];
        $homeGoals = $match->home_score;
        $awayGoals = $match->away_score;
        
        // Generate goal minutes distributed throughout the match
        $goalMinutes = $this->generateGoalMinutes($totalGoals);
        
        $goalIndex = 0;
        for ($i = 0; $i < $homeGoals; $i++) {
            if ($goalIndex < count($goalMinutes)) {
                $events[] = [
                    'match_id' => $match->id,
                    'team_id' => $match->home_team_id,
                    'player_id' => rand(1, 20), // Random player from home team
                    'event_type' => 'goal',
                    'minute' => $goalMinutes[$goalIndex],
                    'description' => $this->generateGoalDescription(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $goalIndex++;
            }
        }
        
        for ($i = 0; $i < $awayGoals; $i++) {
            if ($goalIndex < count($goalMinutes)) {
                $events[] = [
                    'match_id' => $match->id,
                    'team_id' => $match->away_team_id,
                    'player_id' => rand(21, 40), // Random player from away team
                    'event_type' => 'goal',
                    'minute' => $goalMinutes[$goalIndex],
                    'description' => $this->generateGoalDescription(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $goalIndex++;
            }
        }
        
        return $events;
    }

    private function generateCardEvents($match): array
    {
        $events = [];
        
        // Generate yellow cards (0-3 per match)
        $yellowCardCount = rand(0, 3);
        for ($i = 0; $i < $yellowCardCount; $i++) {
            $team = (rand(0, 1) == 0) ? $match->home_team_id : $match->away_team_id;
            $playerId = ($team == $match->home_team_id) ? rand(1, 20) : rand(21, 40);
            
            $events[] = [
                'match_id' => $match->id,
                'team_id' => $team,
                'player_id' => $playerId,
                'event_type' => 'yellow_card',
                'minute' => rand(15, 85),
                'description' => $this->generateCardDescription('yellow'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Occasional red cards (10% chance)
        if (rand(1, 10) == 1) {
            $team = (rand(0, 1) == 0) ? $match->home_team_id : $match->away_team_id;
            $playerId = ($team == $match->home_team_id) ? rand(1, 20) : rand(21, 40);
            
            $events[] = [
                'match_id' => $match->id,
                'team_id' => $team,
                'player_id' => $playerId,
                'event_type' => 'red_card',
                'minute' => rand(25, 80),
                'description' => $this->generateCardDescription('red'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $events;
    }

    private function generateGoalMinutes($totalGoals): array
    {
        $minutes = [];
        
        for ($i = 0; $i < $totalGoals; $i++) {
            do {
                $minute = rand(1, 90);
            } while (in_array($minute, $minutes));
            
            $minutes[] = $minute;
        }
        
        sort($minutes);
        return $minutes;
    }

    private function generateGoalDescription(): string
    {
        $descriptions = [
            'Beautiful strike into the top corner',
            'Clinical finish from close range',
            'Powerful header from a corner kick',
            'Solo run and precise shot',
            'Teamwork leads to easy tap-in',
            'Long range effort finds the net',
            'Penalty kick converted',
            'Free kick curled over the wall',
            'Counter attack finished perfectly',
            'Volley from the edge of the box'
        ];
        
        return $descriptions[array_rand($descriptions)];
    }

    private function generateCardDescription($color): string
    {
        if ($color === 'yellow') {
            $descriptions = [
                'Unsportsmanlike conduct',
                'Delaying the restart',
                'Dissent towards referee',
                'Tactical foul',
                'Persistent infringement'
            ];
        } else {
            $descriptions = [
                'Serious foul play',
                'Violent conduct',
                'Denying obvious goal-scoring opportunity',
                'Second yellow card',
                'Professional foul'
            ];
        }
        
        return $descriptions[array_rand($descriptions)];
    }
}
