import { Link, useNavigate } from 'react-router-dom';
import { Calendar, Trophy, Users, Activity, ArrowRight } from 'lucide-react';
import Badge from '../common/Badge';
import { formatDate } from '../../utils/dateUtils';
import { truncateText } from '../../utils/formatUtils';

const TournamentCard = ({ tournament, viewMode = 'grid' }) => {
  const navigate = useNavigate();

  if (viewMode === 'list') {
    return (
      <div
        onClick={() => navigate(`/tournaments/${tournament.id}`)}
        className="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer border border-gray-100 overflow-hidden"
      >
        <div className="p-6">
          <div className="flex flex-col md:flex-row md:items-center gap-6">
            {/* Left: Logo and Name */}
            <div className="flex items-center gap-4 flex-1">
              {tournament.logo && (
                <div className="flex-shrink-0">
                  <img
                    src={tournament.logo}
                    alt={tournament.name}
                    className="h-20 w-20 object-contain bg-gray-50 p-2 rounded-lg"
                  />
                </div>
              )}
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-3 mb-2">
                  <h3 className="text-xl font-bold text-gray-900">
                    {tournament.name}
                  </h3>
                  <Badge
                    variant={tournament.status === 'ongoing' ? 'success' : tournament.status === 'upcoming' ? 'info' : 'default'}
                  >
                    {tournament.status}
                  </Badge>
                </div>
                {tournament.sport && (
                  <p className="text-sm text-gray-600 mb-2">
                    {tournament.sport.name || tournament.sport}
                  </p>
                )}
                {tournament.description && (
                  <p className="text-sm text-gray-600 line-clamp-2">
                    {truncateText(tournament.description, 150)}
                  </p>
                )}
              </div>
            </div>

            {/* Middle: Dates and Stats */}
            <div className="flex flex-col md:flex-row gap-6 md:items-center">
              <div className="space-y-2 text-sm">
                <div className="flex items-center gap-2 text-gray-600">
                  <Calendar className="h-4 w-4" />
                  <span>{formatDate(tournament.start_date)}</span>
                </div>
                <div className="flex items-center gap-2 text-gray-600">
                  <Calendar className="h-4 w-4" />
                  <span>{formatDate(tournament.end_date)}</span>
                </div>
              </div>
              <div className="flex items-center gap-6">
                <div className="flex items-center gap-2">
                  <Users className="h-4 w-4 text-blue-600" />
                  <span className="text-sm text-gray-600">
                    <span className="font-semibold text-gray-900">{tournament.team_count ?? 'N/A'}</span> teams
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <Activity className="h-4 w-4 text-green-600" />
                  <span className="text-sm text-gray-600">
                    <span className="font-semibold text-gray-900">{tournament.match_count ?? 'N/A'}</span> matches
                  </span>
                </div>
              </div>
            </div>

            {/* Right: Action */}
            <div className="flex items-center">
              <Link
                to={`/tournaments/${tournament.id}`}
                className="flex items-center gap-2 text-primary-600 hover:text-primary-700 font-semibold text-sm transition-colors"
                onClick={(e) => e.stopPropagation()}
              >
                View Details
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
      onClick={() => navigate(`/tournaments/${tournament.id}`)}
      className="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer border border-gray-100 overflow-hidden h-full flex flex-col group"
    >
      {/* Header with gradient background */}
      <div className="bg-gradient-to-br from-primary-600 to-primary-800 p-6 text-white relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div className="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
        
        <div className="relative z-10">
          <div className="flex items-start justify-between mb-4">
            {tournament.logo && (
              <div className="bg-white p-2 rounded-lg shadow-md">
                <img
                  src={tournament.logo}
                  alt={tournament.name}
                  className="h-16 w-16 object-contain"
                />
              </div>
            )}
            <Badge
              variant={tournament.status === 'ongoing' ? 'success' : tournament.status === 'upcoming' ? 'info' : 'default'}
              className="bg-white bg-opacity-20 backdrop-blur-sm"
            >
              {tournament.status}
            </Badge>
          </div>
          
          <h3 className="text-2xl font-bold mb-2 line-clamp-2 leading-tight">
            {tournament.name}
          </h3>
          
          {tournament.sport && (
            <div className="flex items-center gap-2 text-primary-100">
              <Trophy className="h-4 w-4" />
              <span className="text-sm font-medium">
                {tournament.sport.name || tournament.sport}
              </span>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="p-6 flex-1 flex flex-col">
        {/* Description */}
        {tournament.description && (
          <p className="text-sm text-gray-600 mb-4 line-clamp-3 flex-1">
            {truncateText(tournament.description, 120)}
          </p>
        )}

        {/* Dates */}
        <div className="space-y-3 mb-4">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Calendar className="h-4 w-4 text-primary-600" />
            <div>
              <span className="font-medium text-gray-700">Start:</span>
              <span className="ml-2">{formatDate(tournament.start_date)}</span>
            </div>
          </div>
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Calendar className="h-4 w-4 text-primary-600" />
            <div>
              <span className="font-medium text-gray-700">End:</span>
              <span className="ml-2">{formatDate(tournament.end_date)}</span>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 mb-4">
          <div className="text-center p-3 bg-blue-50 rounded-lg">
            <div className="flex items-center justify-center gap-2 mb-1">
              <Users className="h-4 w-4 text-blue-600" />
            </div>
            <div className="text-2xl font-bold text-gray-900">
              {tournament.team_count ?? 'N/A'}
            </div>
            <div className="text-xs text-gray-600">Teams</div>
          </div>
          <div className="text-center p-3 bg-green-50 rounded-lg">
            <div className="flex items-center justify-center gap-2 mb-1">
              <Activity className="h-4 w-4 text-green-600" />
            </div>
            <div className="text-2xl font-bold text-gray-900">
              {tournament.match_count ?? 'N/A'}
            </div>
            <div className="text-xs text-gray-600">Matches</div>
          </div>
        </div>

        {/* View Details Button */}
        <Link
          to={`/tournaments/${tournament.id}`}
          className="mt-auto flex items-center justify-center gap-2 w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors group-hover:shadow-md"
          onClick={(e) => e.stopPropagation()}
        >
          View Details
          <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
        </Link>
      </div>
    </div>
  );
};

export default TournamentCard;
