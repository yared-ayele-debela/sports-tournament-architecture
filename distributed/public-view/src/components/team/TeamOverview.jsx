import { useQuery } from '@tanstack/react-query';
import { resultsService } from '../../api/results';
import { teamService } from '../../api/teams';
import { Trophy, TrendingUp, TrendingDown, Minus, Target } from 'lucide-react';
import Loading from '../common/Loading';

const TeamOverview = ({ team }) => {
  const wins = team.statistics?.wins || 0;
  const losses = team.statistics?.losses || 0;
  const draws = team.statistics?.draws || 0;
  const totalMatches = team.statistics?.total_matches || wins + losses + draws;
  const winRate = totalMatches > 0 ? ((wins / totalMatches) * 100).toFixed(1) : 0;

  // Fetch tournament standings to get current position (from Results Service)
  const { data: standingsData } = useQuery({
    queryKey: ['tournamentStandings', team.tournament?.id],
    queryFn: () => resultsService.getStandings(team.tournament?.id),
    enabled: !!team.tournament?.id,
  });

  const standings = standingsData?.data?.data || standingsData?.data || [];
  const currentPosition = standings.findIndex(s => s.team_id === team.id) + 1;

  // Fetch recent matches for form
  const { data: matchesData } = useQuery({
    queryKey: ['teamRecentMatches', team.id],
    queryFn: () => teamService.getMatches(team.id, { limit: 5, status: 'completed' }),
    enabled: !!team.id,
  });

  const recentMatches = matchesData?.data?.data || matchesData?.data || [];
  const form = recentMatches.slice(0, 5).reverse().map(match => {
    const isHome = match.home_team_id === team.id;
    const teamScore = isHome ? match.home_score : match.away_score;
    const opponentScore = isHome ? match.away_score : match.home_score;
    
    if (teamScore > opponentScore) return 'W';
    if (teamScore < opponentScore) return 'L';
    return 'D';
  });

  return (
    <div className="space-y-6">
      {/* Quick Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Total Matches</span>
            <Target className="h-5 w-5 text-primary-600" />
          </div>
          <p className="text-3xl font-bold text-gray-900">{totalMatches}</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Wins</span>
            <TrendingUp className="h-5 w-5 text-green-600" />
          </div>
          <p className="text-3xl font-bold text-green-600">{wins}</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Losses</span>
            <TrendingDown className="h-5 w-5 text-red-600" />
          </div>
          <p className="text-3xl font-bold text-red-600">{losses}</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Win Rate</span>
            <Trophy className="h-5 w-5 text-yellow-600" />
          </div>
          <p className="text-3xl font-bold text-primary-600">{winRate}%</p>
        </div>
      </div>

      {/* Detailed Statistics */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-xl font-bold text-gray-900 mb-4">Team Statistics</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 className="text-sm font-semibold text-gray-700 mb-3 uppercase">Match Record</h4>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600">Wins:</span>
                <span className="font-medium text-gray-900">{wins}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Losses:</span>
                <span className="font-medium text-gray-900">{losses}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Draws:</span>
                <span className="font-medium text-gray-900">{draws}</span>
              </div>
              <div className="flex justify-between pt-2 border-t border-gray-200">
                <span className="text-gray-600">Total:</span>
                <span className="font-medium text-gray-900">{totalMatches}</span>
              </div>
            </div>
          </div>

          <div>
            <h4 className="text-sm font-semibold text-gray-700 mb-3 uppercase">Performance</h4>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600">Win Rate:</span>
                <span className="font-medium text-primary-600">{winRate}%</span>
              </div>
              {currentPosition > 0 && (
                <div className="flex justify-between">
                  <span className="text-gray-600">Tournament Position:</span>
                  <span className="font-medium text-gray-900">#{currentPosition}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-gray-600">Players:</span>
                <span className="font-medium text-gray-900">{team.player_count || 0}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Form (Last 5 Matches) */}
      {form.length > 0 && (
        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-xl font-bold text-gray-900 mb-4">Form (Last 5 Matches)</h3>
          <div className="flex items-center gap-2">
            {form.map((result, index) => (
              <div
                key={index}
                className={`w-12 h-12 rounded-full flex items-center justify-center font-bold text-white ${
                  result === 'W' ? 'bg-green-600' :
                  result === 'L' ? 'bg-red-600' :
                  'bg-gray-600'
                }`}
              >
                {result}
              </div>
            ))}
          </div>
          <div className="mt-4 flex items-center gap-4 text-sm text-gray-600">
            <span className="flex items-center gap-2">
              <div className="w-4 h-4 bg-green-600 rounded-full"></div>
              Win
            </span>
            <span className="flex items-center gap-2">
              <div className="w-4 h-4 bg-gray-600 rounded-full"></div>
              Draw
            </span>
            <span className="flex items-center gap-2">
              <div className="w-4 h-4 bg-red-600 rounded-full"></div>
              Loss
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export default TeamOverview;
