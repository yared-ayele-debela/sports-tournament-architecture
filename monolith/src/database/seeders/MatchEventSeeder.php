<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MatchEvent;
use App\Models\MatchModel;
use App\Models\Player;

class MatchEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $completedMatches = MatchModel::where('status', 'completed')->get();

        if ($completedMatches->count() == 0) {
            $this->command->error('No completed matches found. Please run MatchSeeder first.');
            return;
        }

        foreach ($completedMatches as $match) {
            $this->createMatchEvents($match);
        }

        $this->command->info('Match events seeded successfully!');
    }

    private function createMatchEvents($match)
    {
        $players = Player::where('team_id', $match->home_team_id)
            ->orWhere('team_id', $match->away_team_id)
            ->get();

        if ($players->count() < 4) {
            return;
        }

        // Create realistic match events
        $events = [];

        // Goals
        $goalScorers = $players->random(min(3, 6));
        foreach ($goalScorers as $player) {
            $events[] = [
                'match_id' => $match->id,
                'team_id' => $player->team_id,
                'player_id' => $player->id,
                'event_type' => 'goal',
                'minute' => rand(15, 85),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Yellow cards
        $yellowCardPlayers = $players->random(min(4, $players->count()));
        foreach ($yellowCardPlayers as $player) {
            $events[] = [
                'match_id' => $match->id,
                'team_id' => $player->team_id,
                'player_id' => $player->id,
                'event_type' => 'yellow_card',
                'minute' => rand(20, 80),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Red cards (fewer)
        $redCardPlayers = $players->random(min(1, 2));
        foreach ($redCardPlayers as $player) {
            $events[] = [
                'match_id' => $match->id,
                'team_id' => $player->team_id,
                'player_id' => $player->id,
                'event_type' => 'red_card',
                'minute' => rand(60, 90),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Shuffle events to randomize order
        shuffle($events);

        // Insert events
        foreach ($events as $event) {
            MatchEvent::firstOrCreate([
                'match_id' => $event['match_id'],
                'player_id' => $event['player_id'],
                'event_type' => $event['event_type'],
                'minute' => $event['minute']
            ], $event);
        }
    }
}
