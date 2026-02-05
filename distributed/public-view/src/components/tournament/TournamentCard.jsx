import { Link, useNavigate } from 'react-router-dom';
import Card from '../common/Card';
import Badge from '../common/Badge';
import { formatDate } from '../../utils/dateUtils';
import { truncateText } from '../../utils/formatUtils';

const TournamentCard = ({ tournament, viewMode = 'grid' }) => {
  const navigate = useNavigate();

  if (viewMode === 'list') {
    return (
      <Card
        hover
        onClick={() => navigate(`/tournaments/${tournament.id}`)}
        className="h-full"
      >
        <div className="p-6">
          <div className="flex flex-col md:flex-row md:items-center gap-6">
            {/* Left: Logo and Name */}
            <div className="flex items-center gap-4 flex-1">
              {tournament.logo && (
                <img
                  src={tournament.logo}
                  alt={tournament.name}
                  className="h-20 w-20 object-contain flex-shrink-0"
                />
              )}
              <div className="flex-1 min-w-0">
                <h3 className="text-xl font-bold text-gray-900 mb-1">
                  {tournament.name}
                </h3>
                {tournament.sport && (
                  <p className="text-sm text-gray-600">
                    {tournament.sport.name || tournament.sport}
                  </p>
                )}
                {tournament.description && (
                  <p className="text-sm text-gray-600 mt-2 line-clamp-2">
                    {truncateText(tournament.description, 150)}
                  </p>
                )}
              </div>
            </div>

            {/* Middle: Dates and Stats */}
            <div className="flex flex-col md:flex-row gap-6 md:items-center">
              <div className="space-y-1 text-sm text-gray-600">
                <div className="flex items-center">
                  <span className="font-medium w-16">Start:</span>
                  <span>{formatDate(tournament.start_date)}</span>
                </div>
                <div className="flex items-center">
                  <span className="font-medium w-16">End:</span>
                  <span>{formatDate(tournament.end_date)}</span>
                </div>
              </div>
              <div className="flex items-center gap-6 text-sm text-gray-600">
                <div>
                  <span className="font-medium">{tournament.team_count ?? 'N/A'}</span> teams
                </div>
                <div>
                  <span className="font-medium">{tournament.match_count ?? 'N/A'}</span> matches
                </div>
              </div>
            </div>

            {/* Right: Status and Action */}
            <div className="flex items-center justify-between md:justify-end gap-4">
              <Badge
                variant={tournament.status === 'ongoing' ? 'success' : tournament.status === 'upcoming' ? 'info' : 'default'}
              >
                {tournament.status}
              </Badge>
              <Link
                to={`/tournaments/${tournament.id}`}
                className="text-primary-600 hover:text-primary-700 font-medium text-sm whitespace-nowrap"
              >
                View Details →
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
      onClick={() => navigate(`/tournaments/${tournament.id}`)}
      className="h-full"
    >
      <div className="p-6">
        {/* Tournament Logo/Image */}
        <div className="flex items-center justify-between mb-4">
          {tournament.logo && (
            <img
              src={tournament.logo}
              alt={tournament.name}
              className="h-16 w-16 object-contain"
            />
          )}
          <Badge
            variant={tournament.status === 'ongoing' ? 'success' : tournament.status === 'upcoming' ? 'info' : 'default'}
          >
            {tournament.status}
          </Badge>
        </div>

        {/* Tournament Name */}
        <h3 className="text-xl font-bold text-gray-900 mb-2 line-clamp-2">
          {tournament.name}
        </h3>

        {/* Sport Type */}
        {tournament.sport && (
          <p className="text-sm text-gray-600 mb-3">
            {tournament.sport.name || tournament.sport}
          </p>
        )}

        {/* Description (truncated) */}
        {tournament.description && (
          <p className="text-sm text-gray-600 mb-4 line-clamp-3">
            {truncateText(tournament.description, 100)}
          </p>
        )}

        {/* Dates */}
        <div className="space-y-1 mb-4 text-sm text-gray-600">
          <div className="flex items-center">
            <span className="font-medium">Start:</span>
            <span className="ml-2">{formatDate(tournament.start_date)}</span>
          </div>
          <div className="flex items-center">
            <span className="font-medium">End:</span>
            <span className="ml-2">{formatDate(tournament.end_date)}</span>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="flex items-center justify-between pt-4 border-t border-gray-200">
          <div className="text-sm text-gray-600">
            <span className="font-medium">{tournament.team_count ?? 'N/A'}</span> teams
          </div>
          <div className="text-sm text-gray-600">
            <span className="font-medium">{tournament.match_count ?? 'N/A'}</span> matches
          </div>
        </div>

        {/* View Details Link */}
        <Link
          to={`/tournaments/${tournament.id}`}
          className="mt-4 block text-center text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors"
        >
          View Details →
        </Link>
      </div>
    </Card>
  );
};

export default TournamentCard;
