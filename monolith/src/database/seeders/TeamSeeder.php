<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nationalChampionship = Tournament::where('name', 'National Championship 2024')->first();
        $cityCup = Tournament::where('name', 'City Cup 2024')->first();

        $coach1 = User::where('email', 'coach1@tournament.com')->first();
        $coach2 = User::where('email', 'coach2@tournament.com')->first();
        $coach3 = User::where('email', 'coach3@tournament.com')->first();
        $coach4 = User::where('email', 'coach4@tournament.com')->first();

        // National Championship Teams
        $teams = [
            [
                'name' => 'Thunder FC',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'Michael Thompson',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Lightning United',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'David Martinez',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Storm Rangers',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'Robert Chen',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Eagles FC',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'James Wilson',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Lions Athletic',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'Michael Thompson',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Tigers FC',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'David Martinez',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Wolves United',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'Robert Chen',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Dragons FC',
                'tournament_id' => $nationalChampionship->id,
                'coach_name' => 'James Wilson',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($teams as $teamData) {
            $team = Team::firstOrCreate(['name' => $teamData['name']], $teamData);
        }

        // City Cup Teams
        $cityCupTeams = [
            [
                'name' => 'Phoenix Rising',
                'tournament_id' => $cityCup->id,
                'coach_name' => 'Michael Thompson',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Vanguard FC',
                'tournament_id' => $cityCup->id,
                'coach_name' => 'David Martinez',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Apex United',
                'tournament_id' => $cityCup->id,
                'coach_name' => 'Robert Chen',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Zenith FC',
                'tournament_id' => $cityCup->id,
                'coach_name' => 'James Wilson',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($cityCupTeams as $teamData) {
            $team = Team::firstOrCreate(['name' => $teamData['name']], $teamData);
        }

        // Assign coaches to teams
        $this->assignCoachesToTeams();

        $this->command->info('Teams seeded successfully!');
    }

    private function assignCoachesToTeams()
    {
        $coach1 = User::where('email', 'coach1@tournament.com')->first();
        $coach2 = User::where('email', 'coach2@tournament.com')->first();
        $coach3 = User::where('email', 'coach3@tournament.com')->first();
        $coach4 = User::where('email', 'coach4@tournament.com')->first();

        // Assign coaches to specific teams
        $teams = Team::all();
        
        if ($teams->count() >= 4) {
            $teams[0]->coaches()->sync([$coach1->id]);
            $teams[1]->coaches()->sync([$coach2->id]);
            $teams[2]->coaches()->sync([$coach3->id]);
            $teams[3]->coaches()->sync([$coach4->id]);
        }
    }
}
