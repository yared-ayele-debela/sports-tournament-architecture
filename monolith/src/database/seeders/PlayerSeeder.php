<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Player;
use App\Models\Team;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::all();

        foreach ($teams as $team) {
            $players = $this->generatePlayersForTeam($team->name);
            
            foreach ($players as $playerData) {
                Player::firstOrCreate([
                    'team_id' => $team->id,
                    'jersey_number' => $playerData['jersey_number']
                ], [
                    'full_name' => $playerData['full_name'],
                    'position' => $playerData['position'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('Players seeded successfully!');
    }

    private function generatePlayersForTeam($teamName)
    {
        $playerSets = [
            'Thunder FC' => [
                ['full_name' => 'Alex Rodriguez', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Marcus Johnson', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'David Kim', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'James Wilson', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Carlos Mendez', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Ryan Thompson', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Lucas Silva', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Nathan Chen', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Daniel Park', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Oliver Martinez', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Ethan Brown', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Lightning United' => [
                ['full_name' => 'Peter Anderson', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Steven Garcia', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Michael Lee', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'William Taylor', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Anthony Davis', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Christopher Moore', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Matthew White', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Joshua Harris', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Andrew Clark', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Kevin Lewis', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Brian Walker', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Storm Rangers' => [
                ['full_name' => 'Thomas Robinson', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Paul Hall', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Mark Young', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Daniel King', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'George Wright', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Edward Scott', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Henry Green', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Frank Baker', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Jack Carter', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Samuel Mitchell', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Ryan Cooper', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Eagles FC' => [
                ['full_name' => 'Walter Adams', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Nelson Nelson', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Ronald Hayes', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Bernard Perry', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Clarence Collins', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Earl Edwards', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Freddie Stewart', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Albert Morris', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Harry Rogers', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Jesse Reed', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Charlie Cook', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Lions Athletic' => [
                ['full_name' => 'Louis Morgan', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Earl Bailey', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Freddie Rivera', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Albert Campbell', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Harry Roberts', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Jesse Turner', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Charlie Phillips', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Louis Campbell', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Earl Parker', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Freddie Evans', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Albert Edwards', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Tigers FC' => [
                ['full_name' => 'Harry Long', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Jesse Hughes', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Charlie Flores', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Louis Washington', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Earl Jefferson', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Freddie Butler', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Albert Simmons', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Harry Foster', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Jesse Gonzales', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Charlie Bryant', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Louis Alexander', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Wolves United' => [
                ['full_name' => 'Earl Russell', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Freddie Griffin', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Albert Diaz', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Harry Hayes', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Jesse Myers', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Charlie Ford', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Louis Hamilton', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Earl Graham', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Freddie Sullivan', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Albert Wallace', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Harry Woods', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Dragons FC' => [
                ['full_name' => 'Jesse Cole', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Charlie West', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Louis Owens', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Earl Reynolds', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Freddie Fisher', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Albert Ellis', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Harry Harrison', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Jesse Gibson', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Charlie McDonald', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Louis Cruz', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Earl Marshall', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Phoenix Rising' => [
                ['full_name' => 'Albert Ortiz', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Harry Gomez', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Jesse Adkins', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Charlie Powell', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Louis Jenkins', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Earl Perry', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Freddie Barnes', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Albert Fisher', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Harry Henderson', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Jesse Coleman', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Charlie Perry', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Vanguard FC' => [
                ['full_name' => 'Louis Patterson', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Earl Kelley', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Freddie Griffin', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Albert Morris', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Harry Rogers', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Jesse Rice', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Charlie Hunt', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Louis Black', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Earl Snyder', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Freddie Cobb', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Albert Mills', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Apex United' => [
                ['full_name' => 'Harry Ward', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Jesse Burns', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Charlie Morgan', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Louis Flynn', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Earl Curtis', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Freddie Quinn', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Albert Floyd', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Harry Perry', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Jesse Cox', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Charlie Howard', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Louis Ward', 'position' => 'Forward', 'jersey_number' => 11],
            ],
            'Zenith FC' => [
                ['full_name' => 'Earl Torres', 'position' => 'Goalkeeper', 'jersey_number' => 1],
                ['full_name' => 'Freddie Peterson', 'position' => 'Defender', 'jersey_number' => 2],
                ['full_name' => 'Albert Gray', 'position' => 'Defender', 'jersey_number' => 3],
                ['full_name' => 'Harry Ramirez', 'position' => 'Defender', 'jersey_number' => 4],
                ['full_name' => 'Jesse James', 'position' => 'Midfielder', 'jersey_number' => 5],
                ['full_name' => 'Charlie Watson', 'position' => 'Midfielder', 'jersey_number' => 6],
                ['full_name' => 'Louis Brooks', 'position' => 'Midfielder', 'jersey_number' => 7],
                ['full_name' => 'Earl Kelly', 'position' => 'Midfielder', 'jersey_number' => 8],
                ['full_name' => 'Freddie Sanders', 'position' => 'Forward', 'jersey_number' => 9],
                ['full_name' => 'Albert Price', 'position' => 'Forward', 'jersey_number' => 10],
                ['full_name' => 'Harry Bennett', 'position' => 'Forward', 'jersey_number' => 11],
            ]
        ];

        return $playerSets[$teamName] ?? [];
    }
}
