<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MatchGame;
use App\Models\MatchReport;

class MatchReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding match reports...');

        // Get completed matches to create reports for
        $completedMatches = MatchGame::where('status', 'completed')->get();
        
        $reports = [];

        foreach ($completedMatches as $match) {
            $reports[] = [
                'match_id' => $match->id,
                'summary' => $this->generateMatchSummary($match),
                'referee' => $this->generateRefereeName($match->referee_id),
                'attendance' => $this->generateAttendance(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert reports in batches
        if (!empty($reports)) {
            MatchReport::insert($reports);
            $this->command->info('Generated ' . count($reports) . ' match reports');
        }
    }

    private function generateMatchSummary($match): string
    {
        $homeScore = $match->home_score;
        $awayScore = $match->away_score;
        
        if ($homeScore > $awayScore) {
            $result = 'decisive victory';
            $margin = $homeScore - $awayScore;
        } elseif ($awayScore > $homeScore) {
            $result = 'clear win';
            $margin = $awayScore - $homeScore;
        } else {
            $result = 'hard-fought draw';
            $margin = 0;
        }

        $summaries = [
            "An entertaining match that saw both teams create numerous chances. The home side secured a {$result} with a final score of {$homeScore}-{$awayScore}.",
            "A tactical battle where the home team's strategy prevailed. The {$homeScore}-{$awayScore} result reflects their dominance on the field.",
            "End-to-end action thrilled the crowd as the teams battled to a {$homeScore}-{$awayScore} finish. Key moments in the second half decided the outcome.",
            "The home team delivered a commanding performance, controlling possession and creating multiple scoring opportunities in their {$homeScore}-{$awayScore} triumph.",
            "A competitive match where the away side proved clinical in front of goal. The {$homeScore}-{$awayScore} scoreline tells the story of their efficiency.",
            "Both teams showed quality and determination in this {$homeScore}-{$awayScore} encounter. The match had everything a neutral fan could ask for.",
            "The home side's attacking prowess was on full display in this {$homeScore}-{$awayScore} victory. Their movement off the ball created constant problems.",
            "A disciplined defensive performance combined with lethal counter-attacks led to this {$homeScore}-{$awayScore} result. The away team will regret missed chances.",
        ];

        if ($margin >= 3) {
            $summaries[] = "A dominant display from the home team resulted in a comprehensive {$homeScore}-{$awayScore} victory. They were in control from start to finish.";
            $summaries[] = "The away team was completely outplayed in this {$homeScore}-{$awayScore} mismatch. Every department of the home side functioned perfectly.";
        }

        return $summaries[array_rand($summaries)];
    }

    private function generateRefereeName($refereeId): string
    {
        $referees = [
            1 => 'Michael Thompson',
            2 => 'David Rodriguez',
            3 => 'James Wilson',
            4 => 'Robert Martinez',
            5 => 'Thomas Anderson',
            6 => 'Christopher Lee',
            7 => 'Daniel Garcia',
            8 => 'Matthew Brown',
            9 => 'Anthony Davis',
            10 => 'Mark Johnson'
        ];

        return $referees[$refereeId] ?? 'Senior Referee';
    }

    private function generateAttendance(): string
    {
        $attendances = [
            '15,234',
            '18,567',
            '22,891',
            '12,456',
            '25,123',
            '19,789',
            '14,321',
            '28,456',
            '16,789',
            '21,234',
            '13,567',
            '24,890',
            '17,234',
            '20,456',
            '26,123'
        ];

        return $attendances[array_rand($attendances)];
    }
}
