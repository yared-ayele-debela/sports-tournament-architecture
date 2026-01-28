<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\Venue;
use App\Models\MatchModel;
use App\Models\TournamentSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MatchScheduler
{
    public function generateRoundRobinSchedule(Tournament $tournament): Collection
    {
        return DB::transaction(function () use ($tournament) {
            $teams = $tournament->teams;
            $settings = $tournament->settings;
            $venues = Venue::all();

            if ($teams->count() < 2) {
                throw new \InvalidArgumentException('At least 2 teams are required for scheduling');
            }

            $matches = $this->createRoundRobinMatches($teams, $tournament, $settings, $venues);
            
            return $matches;
        });
    }

    public function generateKnockoutSchedule(Tournament $tournament): Collection
    {
        return DB::transaction(function () use ($tournament) {
            $teams = $tournament->teams;
            $settings = $tournament->settings;
            $venues = Venue::all();

            if ($teams->count() < 2) {
                throw new \InvalidArgumentException('At least 2 teams are required for knockout');
            }

            $matches = $this->createKnockoutMatches($teams, $tournament, $settings, $venues);
            
            return $matches;
        });
    }

    private function createRoundRobinMatches(Collection $teams, Tournament $tournament, TournamentSettings $settings, Collection $venues): Collection
    {
        $matches = collect();
        $teamList = $teams->pluck('id')->toArray();
        $totalTeams = count($teamList);
        
        if ($totalTeams % 2 !== 0) {
            $teamList[] = null; // Bye for odd number of teams
            $totalTeams++;
        }

        $totalRounds = $totalTeams - 1;
        $matchesPerRound = $totalTeams / 2;
        
        $startDate = Carbon::parse($tournament->start_date);
        $endDate = Carbon::parse($tournament->end_date);
        
        for ($round = 1; $round <= $totalRounds; $round++) {
            $roundDate = $this->calculateRoundDate($startDate, $endDate, $round, $totalRounds, $settings);
            
            for ($match = 0; $match < $matchesPerRound; $match++) {
                $homeIndex = ($round + $match) % ($totalTeams - 1);
                $awayIndex = ($totalTeams - 1 - $match + $round) % ($totalTeams - 1);
                
                if ($match === $matchesPerRound - 1) {
                    $awayIndex = $totalTeams - 1;
                }

                $homeTeamId = $teamList[$homeIndex];
                $awayTeamId = $teamList[$awayIndex];

                if ($homeTeamId !== null && $awayTeamId !== null) {
                    $matchData = $this->createMatchData($tournament, $homeTeamId, $awayTeamId, $roundDate, $round, $venues);
                    $matches->push($matchData);
                }
            }
            
            // Rotate teams (except the last one)
            $temp = $teamList[$totalTeams - 2];
            for ($i = $totalTeams - 2; $i > 0; $i--) {
                $teamList[$i] = $teamList[$i - 1];
            }
            $teamList[1] = $temp;
        }

        return $matches;
    }

    private function createKnockoutMatches(Collection $teams, Tournament $tournament, TournamentSettings $settings, Collection $venues): Collection
    {
        $matches = collect();
        $teamIds = $teams->shuffle()->pluck('id')->toArray();
        $round = 1;
        $startDate = Carbon::parse($tournament->start_date);

        while (count($teamIds) > 1) {
            $roundDate = $startDate->addDays($round * 2);
            $nextRoundTeams = [];

            for ($i = 0; $i < count($teamIds); $i += 2) {
                if (isset($teamIds[$i + 1])) {
                    $matchData = $this->createMatchData($tournament, $teamIds[$i], $teamIds[$i + 1], $roundDate, $round, $venues);
                    $matches->push($matchData);
                    $nextRoundTeams[] = 'winner_' . $matches->count();
                } else {
                    $nextRoundTeams[] = $teamIds[$i]; // Bye
                }
            }

            $teamIds = $nextRoundTeams;
            $round++;
        }

        return $matches;
    }

    private function createMatchData(Tournament $tournament, int $homeTeamId, int $awayTeamId, Carbon $matchDate, int $round, Collection $venues): array
    {
        $venue = $venues->random();
        $matchTime = $this->calculateMatchTime($matchDate, $tournament->settings);

        return [
            'tournament_id' => $tournament->id,
            'venue_id' => $venue->id,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_date' => $matchTime,
            'round_number' => $round,
            'status' => 'scheduled',
            'home_score' => null,
            'away_score' => null,
            'current_minute' => null,
        ];
    }

    private function calculateRoundDate(Carbon $startDate, Carbon $endDate, int $round, int $totalRounds, TournamentSettings $settings): Carbon
    {
        $totalDays = $startDate->diffInDays($endDate);
        $daysPerRound = max(1, floor($totalDays / $totalRounds));
        
        return $startDate->copy()->addDays(($round - 1) * $daysPerRound);
    }

    private function calculateMatchTime(Carbon $date, TournamentSettings $settings): Carbon
    {
        $matchDate = $date->copy();
        $startTime = $settings->daily_start_time;
        $endTime = $settings->daily_end_time;
        
        $matchDate->setTimeFromTimeString($startTime);
        
        // Add some randomness to match times within the allowed window
        if ($startTime && $endTime) {
            $startMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse('00:00:00'));
            $endMinutes = Carbon::parse($endTime)->diffInMinutes(Carbon::parse('00:00:00'));
            $availableMinutes = $endMinutes - $startMinutes;
            
            if ($availableMinutes > 0) {
                $randomOffset = rand(0, min($availableMinutes - 60, 240)); // Up to 4 hours or available time
                $matchDate->addMinutes($randomOffset);
            }
        }
        
        return $matchDate;
    }

    public function rescheduleMatch(MatchModel $match, Carbon $newDate): MatchModel
    {
        return DB::transaction(function () use ($match, $newDate) {
            $settings = $match->tournament->settings;
            $newDateTime = $this->calculateMatchTime($newDate, $settings);
            
            $match->update([
                'match_date' => $newDateTime,
                'status' => 'scheduled', // Reset to scheduled if it was postponed
            ]);
            
            return $match->fresh();
        });
    }

    public function generateScheduleForTournament(Tournament $tournament, string $type = 'round-robin'): Collection
    {
        switch ($type) {
            case 'round-robin':
                return $this->generateRoundRobinSchedule($tournament);
            case 'knockout':
                return $this->generateKnockoutSchedule($tournament);
            default:
                throw new \InvalidArgumentException("Unsupported tournament type: {$type}");
        }
    }
}
