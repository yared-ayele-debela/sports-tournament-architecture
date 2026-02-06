<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Team;
use App\Models\Tournament;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MatchService
{
    /**
     * Validate that both teams belong to the same tournament
     */
    public function validateTeamsBelongToTournament(int $homeTeamId, int $awayTeamId, int $tournamentId): void
    {
        $homeTeam = Team::with('tournament')->find($homeTeamId);
        $awayTeam = Team::with('tournament')->find($awayTeamId);

        if (!$homeTeam) {
            throw new ResourceNotFoundException('Home Team', $homeTeamId);
        }

        if (!$awayTeam) {
            throw new ResourceNotFoundException('Away Team', $awayTeamId);
        }

        if ($homeTeam->tournament_id != $tournamentId || $awayTeam->tournament_id != $tournamentId) {
            throw new BusinessLogicException(
                "Teams belong to different tournaments. Home team tournament: {$homeTeam->tournament_id}, Away team tournament: {$awayTeam->tournament_id}",
                'Both teams must belong to the same tournament',
                [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'tournament_id' => $tournamentId,
                ]
            );
        }
    }

    /**
     * Create a new match with validation
     */
    public function createMatch(array $data): MatchModel
    {
        try {
            // Validate teams belong to same tournament
            $this->validateTeamsBelongToTournament(
                $data['home_team_id'],
                $data['away_team_id'],
                $data['tournament_id']
            );

            $match = MatchModel::create($data);

            Log::info('Match created', [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'user_id' => auth()->id(),
            ]);

            return $match;
        } catch (BusinessLogicException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create match', [
                'data' => $data,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to create match. Please try again.',
                ['data' => $data]
            );
        }
    }

    /**
     * Update match with validation
     */
    public function updateMatch(MatchModel $match, array $data): MatchModel
    {
        try {
            // Validate teams belong to same tournament
            $this->validateTeamsBelongToTournament(
                $data['home_team_id'],
                $data['away_team_id'],
                $data['tournament_id']
            );

            $match->update($data);

            Log::info('Match updated', [
                'match_id' => $match->id,
                'user_id' => auth()->id(),
            ]);

            return $match->fresh();
        } catch (BusinessLogicException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update match', [
                'match_id' => $match->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to update match. Please try again.',
                ['match_id' => $match->id, 'data' => $data]
            );
        }
    }

    /**
     * Get matches for a tournament
     */
    public function getMatchesForTournament(Tournament $tournament)
    {
        return MatchModel::where('tournament_id', $tournament->id)
            ->with(['homeTeam', 'awayTeam', 'venue', 'referee'])
            ->orderBy('match_date', 'desc')
            ->get();
    }

    /**
     * Get upcoming matches for a tournament
     */
    public function getUpcomingMatchesForTournament(Tournament $tournament, int $limit = 10)
    {
        return MatchModel::where('tournament_id', $tournament->id)
            ->where('match_date', '>', now())
            ->where('status', 'scheduled')
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy('match_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent matches for a tournament
     */
    public function getRecentMatchesForTournament(Tournament $tournament, int $limit = 10)
    {
        return MatchModel::where('tournament_id', $tournament->id)
            ->where('status', 'completed')
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy('match_date', 'desc')
            ->limit($limit)
            ->get();
    }
}
