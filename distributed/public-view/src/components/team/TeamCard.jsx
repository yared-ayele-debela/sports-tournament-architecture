import { Link } from 'react-router-dom';
import Card from '../common/Card';
import { Users, Trophy, TrendingUp, TrendingDown, Minus } from 'lucide-react';

const TeamCard = ({ team, viewMode = 'grid' }) => {
  const wins = team.match_stats?.wins || 0;
  const losses = team.match_stats?.losses || 0;
  const draws = team.match_stats?.draws || 0;
  const totalMatches = wins + losses + draws;
  const winRate = totalMatches > 0 ? ((wins / totalMatches) * 100).toFixed(1) : 0;

  if (viewMode === 'list') {
    return (
      <Card
        hover
        onClick={() => window.location.href = `/teams/${team.id}`}
        className="h-full"
      >
        <div className="p-6">
          <div className="flex flex-col md:flex-row md:items-center gap-6">
            {/* Left: Logo and Name */}
            <div className="flex items-center gap-4 flex-1">
              {team.logo && (
                <img
                  src={team.logo}
                  alt={team.name}
                  className="h-20 w-20 object-contain flex-shrink-0"
                />
              )}
              <div className="flex-1 min-w-0">
                <h3 className="text-xl font-bold text-gray-900 mb-1">
                  {team.name}
                </h3>
                {team.tournament_name && (
                  <p className="text-sm text-gray-600">{team.tournament_name}</p>
                )}
                {team.founded_year && (
                  <p className="text-xs text-gray-500 mt-1">Founded: {team.founded_year}</p>
                )}
              </div>
            </div>

            {/* Middle: Stats */}
            <div className="flex items-center gap-6">
              <div className="text-center">
                <p className="text-sm text-gray-600">Players</p>
                <p className="text-lg font-bold text-gray-900">{team.player_count || 0}</p>
              </div>
              <div className="text-center">
                <p className="text-sm text-gray-600">Wins</p>
                <p className="text-lg font-bold text-green-600">{wins}</p>
              </div>
              <div className="text-center">
                <p className="text-sm text-gray-600">Losses</p>
                <p className="text-lg font-bold text-red-600">{losses}</p>
              </div>
              <div className="text-center">
                <p className="text-sm text-gray-600">Draws</p>
                <p className="text-lg font-bold text-gray-600">{draws}</p>
              </div>
              {totalMatches > 0 && (
                <div className="text-center">
                  <p className="text-sm text-gray-600">Win Rate</p>
                  <p className="text-lg font-bold text-primary-600">{winRate}%</p>
                </div>
              )}
            </div>

            {/* Right: Action */}
            <div className="flex items-center">
              <Link
                to={`/teams/${team.id}`}
                className="text-primary-600 hover:text-primary-700 font-medium text-sm whitespace-nowrap"
              >
                View Team →
              </Link>
            </div>
          </div>
        </div>
      </Card>
    );
  }

  // Grid view (default)
  return (
    <Card
      hover
      onClick={() => window.location.href = `/teams/${team.id}`}
      className="h-full"
    >
      <div className="p-6">
        {/* Team Logo */}
        <div className="flex justify-center mb-4">
          {team.logo ? (
            <img
              src={team.logo}
              alt={team.name}
              className="h-24 w-24 object-contain"
            />
          ) : (
            <div className="h-24 w-24 bg-gray-200 rounded-full flex items-center justify-center">
              <Users className="h-12 w-12 text-gray-400" />
            </div>
          )}
        </div>

        {/* Team Name */}
        <h3 className="text-xl font-bold text-gray-900 mb-2 text-center line-clamp-2">
          {team.name}
        </h3>

        {/* Tournament Name */}
        {team.tournament_name && (
          <p className="text-sm text-gray-600 mb-3 text-center">
            {team.tournament_name}
          </p>
        )}

        {/* Founded Year */}
        {team.founded_year && (
          <p className="text-xs text-gray-500 mb-4 text-center">
            Founded: {team.founded_year}
          </p>
        )}

        {/* Stats */}
        <div className="space-y-3 mb-4">
          <div className="flex items-center justify-between text-sm">
            <span className="text-gray-600 flex items-center gap-1">
              <Users className="h-4 w-4" />
              Players:
            </span>
            <span className="font-medium text-gray-900">{team.player_count || 0}</span>
          </div>

          {totalMatches > 0 && (
            <>
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600 flex items-center gap-1">
                  <TrendingUp className="h-4 w-4 text-green-600" />
                  Wins:
                </span>
                <span className="font-medium text-green-600">{wins}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600 flex items-center gap-1">
                  <TrendingDown className="h-4 w-4 text-red-600" />
                  Losses:
                </span>
                <span className="font-medium text-red-600">{losses}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600 flex items-center gap-1">
                  <Minus className="h-4 w-4 text-gray-600" />
                  Draws:
                </span>
                <span className="font-medium text-gray-600">{draws}</span>
              </div>
              <div className="pt-2 border-t border-gray-200">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Win Rate:</span>
                  <span className="font-medium text-primary-600">{winRate}%</span>
                </div>
              </div>
            </>
          )}
        </div>

        {/* View Team Button */}
        <Link
          to={`/teams/${team.id}`}
          className="mt-4 block text-center text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors"
        >
          View Team →
        </Link>
      </div>
    </Card>
  );
};

export default TeamCard;
