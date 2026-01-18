<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\TeamCoach;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('team_coach')->delete();
        Team::query()->delete();

        // Tournament IDs (assuming these exist in Tournament Service)
        $tournamentIds = [1, 2];

        // Coach user IDs (assuming these exist in Auth Service)
        $coachUserIds = range(1, 20); // coach1@test.com to coach20@test.com

        $teamNames = [
            'Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta',
            'Iota', 'Kappa', 'Lambda', 'Mu', 'Nu', 'Xi', 'Omicron', 'Pi'
        ];

        $coachNames = [
            'John Smith', 'Maria Garcia', 'Chen Wei', 'Ahmed Hassan', 'Emma Wilson',
            'Luis Rodriguez', 'Priya Sharma', 'James Brown', 'Sophie Martin', 'Carlos Silva',
            'Yuki Tanaka', 'Anna Petrov', 'Mohammed Ali', 'Sarah Johnson', 'David Kim',
            'Elena Rodriguez', 'Michael Chen', 'Fatima Al-Rashid', 'Robert Taylor', 'Lisa Wang'
        ];

        $teamIndex = 0;
        $coachIndex = 0;

        foreach ($tournamentIds as $tournamentId) {
            for ($i = 0; $i < 8; $i++) {
                $team = Team::create([
                    'tournament_id' => $tournamentId,
                    'name' => "Team {$teamNames[$teamIndex]}",
                    'coach_name' => $coachNames[$coachIndex],
                    'logo' => "https://picsum.photos/seed/team{$teamIndex}/200/200.jpg"
                ]);

                // Assign coach to team
                TeamCoach::create([
                    'team_id' => $team->id,
                    'user_id' => $coachUserIds[$coachIndex]
                ]);

                $teamIndex++;
                $coachIndex++;
            }
        }

        $this->command->info('Team seeder completed successfully!');
        $this->command->info('Created 16 teams across 2 tournaments with assigned coaches.');
    }
}
