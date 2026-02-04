import { useQuery } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';
import { resultsService } from '../../api/results';
import { ArrowLeft, BarChart3, TrendingUp, TrendingDown } from 'lucide-react';

export default function TeamStatistics() {
  const { teamId } = useParams();
  const navigate = useNavigate();

  const { data: statsData, isLoading, error } = useQuery({
    queryKey: ['team-statistics', teamId],
    queryFn: () => resultsService.getTeamStatistics(teamId),
    enabled: !!teamId,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !statsData) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Team statistics not found'}
      </div>
    );
  }

  const team = statsData.team || {};
  const statistics = statsData.statistics || {};

  const totalMatches = statistics.total_matches ?? 0;
  const wins = statistics.wins ?? 0;
  const draws = statistics.draws ?? 0;
  const losses = statistics.losses ?? 0;
  const goalsFor = statistics.goals_for ?? 0;
  const goalsAgainst = statistics.goals_against ?? 0;
  const goalDifference = statistics.goal_difference ?? goalsFor - goalsAgainst;
  const points = statistics.points ?? 0;
  const winRate = statistics.win_rate ?? 0;
  const recentForm = statistics.recent_form || '';

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back
        </button>
        <div className="flex items-center space-x-4">
          <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
            <BarChart3 className="w-8 h-8 text-primary-600" />
          </div>
          <div>
            <h1 className="text-3xl font-bold text-gray-900">
              {team.name || `Team ${teamId}`} Statistics
            </h1>
            {team.tournament && (
              <p className="text-gray-600 mt-1">{team.tournament.name}</p>
            )}
          </div>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div className="card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Total Matches</p>
              <p className="text-3xl font-bold text-gray-900">{totalMatches}</p>
            </div>
            <BarChart3 className="w-12 h-12 text-primary-600 opacity-50" />
          </div>
        </div>

        <div className="card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Points</p>
              <p className="text-3xl font-bold text-primary-600">{points}</p>
            </div>
            <TrendingUp className="w-12 h-12 text-primary-600 opacity-50" />
          </div>
        </div>

        <div className="card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Win Rate</p>
              <p className="text-3xl font-bold text-green-600">{winRate}%</p>
            </div>
            <TrendingUp className="w-12 h-12 text-green-600 opacity-50" />
          </div>
        </div>

        <div className="card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Goal Difference</p>
              <p
                className={`text-3xl font-bold ${
                  goalDifference > 0
                    ? 'text-green-600'
                    : goalDifference < 0
                    ? 'text-red-600'
                    : 'text-gray-600'
                }`}
              >
                {goalDifference > 0 ? '+' : ''}
                {goalDifference}
              </p>
            </div>
            {goalDifference > 0 ? (
              <TrendingUp className="w-12 h-12 text-green-600 opacity-50" />
            ) : goalDifference < 0 ? (
              <TrendingDown className="w-12 h-12 text-red-600 opacity-50" />
            ) : (
              <BarChart3 className="w-12 h-12 text-gray-600 opacity-50" />
            )}
          </div>
        </div>
      </div>

      {/* Detailed Statistics */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Match Results */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Match Results</h2>
          <div className="space-y-4">
            <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
              <span className="font-medium text-gray-900">Wins</span>
              <span className="text-2xl font-bold text-green-600">{wins}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
              <span className="font-medium text-gray-900">Draws</span>
              <span className="text-2xl font-bold text-yellow-600">{draws}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-red-50 rounded-lg">
              <span className="font-medium text-gray-900">Losses</span>
              <span className="text-2xl font-bold text-red-600">{losses}</span>
            </div>
          </div>
        </div>

        {/* Goals */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Goals</h2>
          <div className="space-y-4">
            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
              <span className="font-medium text-gray-900">Goals For</span>
              <span className="text-2xl font-bold text-blue-600">{goalsFor}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
              <span className="font-medium text-gray-900">Goals Against</span>
              <span className="text-2xl font-bold text-orange-600">{goalsAgainst}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <span className="font-medium text-gray-900">Goal Difference</span>
              <span
                className={`text-2xl font-bold ${
                  goalDifference > 0
                    ? 'text-green-600'
                    : goalDifference < 0
                    ? 'text-red-600'
                    : 'text-gray-600'
                }`}
              >
                {goalDifference > 0 ? '+' : ''}
                {goalDifference}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Recent Form */}
      {recentForm && (
        <div className="card mt-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Recent Form</h2>
          <div className="flex items-center space-x-2">
            {recentForm.split('').map((result, index) => (
              <div
                key={index}
                className={`w-12 h-12 rounded-full flex items-center justify-center font-bold text-white ${
                  result === 'W'
                    ? 'bg-green-600'
                    : result === 'D'
                    ? 'bg-yellow-500'
                    : 'bg-red-600'
                }`}
              >
                {result}
              </div>
            ))}
          </div>
          <p className="text-sm text-gray-500 mt-2">
            Last {recentForm.length} matches (W = Win, D = Draw, L = Loss)
          </p>
        </div>
      )}
    </div>
  );
}
