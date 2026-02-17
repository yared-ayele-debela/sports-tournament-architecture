import { Link, useNavigate } from 'react-router-dom';
import { Users, Trophy, Calendar, ArrowRight } from 'lucide-react';
import TeamLogo from './TeamLogo';

const TeamCard = ({ team, viewMode = 'grid' }) => {
  const navigate = useNavigate();

  if (viewMode === 'list') {
    return (
      <div
        onClick={() => navigate(`/teams/${team.id}`)}
        className="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors cursor-pointer"
      >
        <div className="p-4">
          <div className="flex items-center gap-4">
            {/* Logo */}
            <div className="flex-shrink-0">
              <div className="p-2 border border-gray-200 rounded-lg">
                <TeamLogo logo={team.logo_url} name={team.name} size="md" />
              </div>
            </div>
            
            {/* Name and Info */}
            <div className="flex-1 min-w-0">
              <h3 className="text-lg font-semibold text-gray-900 mb-1">
                {team.name}
              </h3>
              {team.tournament_name && (
                <p className="text-sm text-gray-600 mb-1">{team.tournament_name}</p>
              )}
            </div>

            {/* Player Count */}
            <div className="flex items-center gap-2 text-gray-600">
              <Users className="h-4 w-4" />
              <span className="text-sm font-medium">{team.player_count || 0} Players</span>
            </div>

            {/* Action */}
            <div className="flex items-center">
              <Link
                to={`/teams/${team.id}`}
                className="flex items-center gap-1 text-gray-700 hover:text-gray-900 font-medium text-sm transition-colors"
                onClick={(e) => e.stopPropagation()}
              >
                View
                <ArrowRight className="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Grid view (default) - Small/Medium card
  return (
    <div
      onClick={() => navigate(`/teams/${team.id}`)}
      className="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors cursor-pointer"
    >
      <div className="p-4">
        {/* Logo and Name */}
        <div className="flex flex-col items-center mb-3">
          <div className="mb-3">
            <div className="p-2 border border-gray-200 rounded-lg">
              <TeamLogo logo={team.logo_url} name={team.name} size="md" />
            </div>
          </div>
          
          <h3 className="text-base font-semibold text-center text-gray-900 mb-1 line-clamp-2">
            {team.name}
          </h3>
          
          {team.tournament_name && (
            <div className="flex items-center gap-1 text-gray-600 mb-2">
              <Trophy className="h-3 w-3" />
              <span className="text-xs">{team.tournament_name}</span>
            </div>
          )}
        </div>

        {/* Player Count */}
        <div className="flex items-center justify-center gap-2 py-2 border-t border-gray-200">
          <Users className="h-4 w-4 text-gray-600" />
          <span className="text-sm font-medium text-gray-900">{team.player_count || 0} Players</span>
        </div>
      </div>
    </div>
  );
};

export default TeamCard;
