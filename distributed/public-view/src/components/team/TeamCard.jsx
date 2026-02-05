import { Link, useNavigate } from 'react-router-dom';
import { Users, Trophy, TrendingUp, TrendingDown, Minus, Calendar, ArrowRight } from 'lucide-react';
import TeamLogo from './TeamLogo';

const TeamCard = ({ team, viewMode = 'grid' }) => {
  const navigate = useNavigate();
  const wins = team.match_stats?.wins || 0;
  const losses = team.match_stats?.losses || 0;
  const draws = team.match_stats?.draws || 0;
  const totalMatches = wins + losses + draws;
  const winRate = totalMatches > 0 ? ((wins / totalMatches) * 100).toFixed(1) : 0;

  if (viewMode === 'list') {
    return (
      <div
        onClick={() => navigate(`/teams/${team.id}`)}
        className="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer border border-gray-100 overflow-hidden"
      >
        <div className="p-6">
          <div className="flex flex-col md:flex-row md:items-center gap-6">
            {/* Left: Logo and Name */}
            <div className="flex items-center gap-4 flex-1">
              <div className="flex-shrink-0">
                <div className="bg-gray-50 p-2 rounded-lg">
                  <TeamLogo logo={team.logo} name={team.name} size="lg" />
                </div>
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="text-xl font-bold text-gray-900 mb-1">
                  {team.name}
                </h3>
                {team.tournament_name && (
                  <p className="text-sm text-gray-600 mb-1">{team.tournament_name}</p>
                )}
                {team.founded_year && (
                  <div className="flex items-center gap-1 text-xs text-gray-500">
                    <Calendar className="h-3 w-3" />
                    <span>Founded: {team.founded_year}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Middle: Stats */}
            <div className="flex items-center gap-6">
              <div className="text-center p-2 bg-blue-50 rounded-lg min-w-[70px]">
                <Users className="h-4 w-4 text-blue-600 mx-auto mb-1" />
                <p className="text-lg font-bold text-gray-900">{team.player_count || 0}</p>
                <p className="text-xs text-gray-600">Players</p>
              </div>
              {totalMatches > 0 && (
                <>
                  <div className="text-center p-2 bg-green-50 rounded-lg min-w-[70px]">
                    <TrendingUp className="h-4 w-4 text-green-600 mx-auto mb-1" />
                    <p className="text-lg font-bold text-green-600">{wins}</p>
                    <p className="text-xs text-gray-600">Wins</p>
                  </div>
                  <div className="text-center p-2 bg-red-50 rounded-lg min-w-[70px]">
                    <TrendingDown className="h-4 w-4 text-red-600 mx-auto mb-1" />
                    <p className="text-lg font-bold text-red-600">{losses}</p>
                    <p className="text-xs text-gray-600">Losses</p>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg min-w-[70px]">
                    <Minus className="h-4 w-4 text-gray-600 mx-auto mb-1" />
                    <p className="text-lg font-bold text-gray-600">{draws}</p>
                    <p className="text-xs text-gray-600">Draws</p>
                  </div>
                  <div className="text-center p-2 bg-primary-50 rounded-lg min-w-[70px]">
                    <Trophy className="h-4 w-4 text-primary-600 mx-auto mb-1" />
                    <p className="text-lg font-bold text-primary-600">{winRate}%</p>
                    <p className="text-xs text-gray-600">Win Rate</p>
                  </div>
                </>
              )}
            </div>

            {/* Right: Action */}
            <div className="flex items-center">
              <Link
                to={`/teams/${team.id}`}
                className="flex items-center gap-2 text-primary-600 hover:text-primary-700 font-semibold text-sm transition-colors"
                onClick={(e) => e.stopPropagation()}
              >
                View Team
                <ArrowRight className="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Grid view (default) - Professional Design
  return (
    <div
      onClick={() => navigate(`/teams/${team.id}`)}
      className="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer border border-gray-100 overflow-hidden h-full flex flex-col group"
    >
      {/* Header with gradient background */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 p-6 text-white relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div className="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
        
        <div className="relative z-10">
          {/* Team Logo */}
          <div className="flex justify-center mb-4">
            <div className="bg-white p-3 rounded-xl">
              <TeamLogo logo={team.logo} name={team.name} size="lg" className="bg-white" />
            </div>
          </div>
          
          {/* Team Name */}
          <h3 className="text-2xl font-bold mb-2 text-center line-clamp-2 leading-tight">
            {team.name}
          </h3>
          
          {/* Tournament Name */}
          {team.tournament_name && (
            <div className="flex items-center justify-center gap-2 text-blue-100">
              <Trophy className="h-4 w-4" />
              <span className="text-sm font-medium">
                {team.tournament_name}
              </span>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="p-6 flex-1 flex flex-col">
        {/* Founded Year */}
        {team.founded_year && (
          <div className="flex items-center justify-center gap-2 mb-4 text-sm text-gray-600">
            <Calendar className="h-4 w-4 text-primary-600" />
            <span>Founded {team.founded_year}</span>
          </div>
        )}

        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-3 mb-4">
          <div className="text-center p-3 bg-blue-50 rounded-lg">
            <div className="flex items-center justify-center gap-1 mb-1">
              <Users className="h-4 w-4 text-blue-600" />
            </div>
            <div className="text-2xl font-bold text-gray-900">
              {team.player_count || 0}
            </div>
            <div className="text-xs text-gray-600">Players</div>
          </div>

          {totalMatches > 0 ? (
            <div className="text-center p-3 bg-primary-50 rounded-lg">
              <div className="flex items-center justify-center gap-1 mb-1">
                <Trophy className="h-4 w-4 text-primary-600" />
              </div>
              <div className="text-2xl font-bold text-primary-600">
                {winRate}%
              </div>
              <div className="text-xs text-gray-600">Win Rate</div>
            </div>
          ) : (
            <div className="text-center p-3 bg-gray-50 rounded-lg">
              <div className="text-2xl font-bold text-gray-400">-</div>
              <div className="text-xs text-gray-600">No matches</div>
            </div>
          )}
        </div>

        {/* Match Stats */}
        {totalMatches > 0 && (
          <div className="space-y-2 mb-4 pt-4 border-t border-gray-200">
            <div className="flex items-center justify-between text-sm">
              <div className="flex items-center gap-2 text-gray-600">
                <TrendingUp className="h-4 w-4 text-green-600" />
                <span>Wins:</span>
              </div>
              <span className="font-bold text-green-600">{wins}</span>
            </div>
            <div className="flex items-center justify-between text-sm">
              <div className="flex items-center gap-2 text-gray-600">
                <TrendingDown className="h-4 w-4 text-red-600" />
                <span>Losses:</span>
              </div>
              <span className="font-bold text-red-600">{losses}</span>
            </div>
            <div className="flex items-center justify-between text-sm">
              <div className="flex items-center gap-2 text-gray-600">
                <Minus className="h-4 w-4 text-gray-600" />
                <span>Draws:</span>
              </div>
              <span className="font-bold text-gray-600">{draws}</span>
            </div>
          </div>
        )}

        {/* View Team Button */}
        <Link
          to={`/teams/${team.id}`}
          className="mt-auto flex items-center justify-center gap-2 w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors group-hover:shadow-md"
          onClick={(e) => e.stopPropagation()}
        >
          View Team
          <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
        </Link>
      </div>
    </div>
  );
};

export default TeamCard;
