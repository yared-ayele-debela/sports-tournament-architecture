<?php

namespace Database\Seeders;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing players
        Player::query()->delete();

        // Get all teams
        $teams = Team::all();

        $firstNames = [
            'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph',
            'Thomas', 'Charles', 'Christopher', 'Daniel', 'Matthew', 'Anthony', 'Mark',
            'Steven', 'Paul', 'Andrew', 'Joshua', 'Kenneth', 'Kevin', 'Brian'
        ];

        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
            'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
            'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White'
        ];

        $positions = [
            'Goalkeeper' => 2,
            'Defender' => 5,
            'Midfielder' => 6,
            'Forward' => 3
        ];

        foreach ($teams as $team) {
            $this->command->info("Creating players for team: {$team->name}");
            
            $usedJerseyNumbers = [];
            $playerIndex = 0;

            foreach ($positions as $position => $count) {
                for ($i = 0; $i < $count; $i++) {
                    // Generate unique jersey number (1-99)
                    do {
                        $jerseyNumber = rand(1, 99);
                    } while (in_array($jerseyNumber, $usedJerseyNumbers));
                    
                    $usedJerseyNumbers[] = $jerseyNumber;

                    // Generate realistic name
                    $firstName = $firstNames[array_rand($firstNames)];
                    $lastName = $lastNames[array_rand($lastNames)];
                    $fullName = $firstName . ' ' . $lastName;

                    Player::create([
                        'team_id' => $team->id,
                        'full_name' => $fullName,
                        'position' => $position,
                        'jersey_number' => $jerseyNumber,
                    ]);

                    $this->command->info("Created {$position}: {$fullName} (#{$jerseyNumber})");
                    $playerIndex++;
                }
            }
        }
    }
}
