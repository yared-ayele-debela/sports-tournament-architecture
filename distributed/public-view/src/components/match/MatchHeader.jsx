import Badge from '../common/Badge';
import { formatDateTime } from '../../utils/dateUtils';
import { Link } from 'react-router-dom';

const MatchHeader = ({ match }) => {
  const isLive = match.status === 'live' || match.status === 'in_progress';
  const isCompleted = match.status === 'completed';

  return (
    <div className="bg-white rounded-lg shadow-md p-6 mb-6">
      {/* Tournament and Round Info */}
      {match.tournament && (
        <div className="text-center mb-4">
          <Link
            to={`/tournaments/${match.tournament.id}`}
            className="text-primary-600 hover:text-primary-700 font-medium text-sm"
          >
            {match.tournament.name}
          </Link>
          {match.round_number && (
            <span className="text-gray-600 text-sm ml-2">
              • Round {match.round_number}
            </span>
          )}
        </div>
      )}

      {/* Teams and Score */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
        {/* Home Team */}
        <div className="text-center md:text-right">
          <div className="flex items-center justify-center md:justify-end gap-3 mb-2">
            {match.home_team?.logo && (
              <img
                src={match.home_team.logo}
                alt={match.home_team.name}
                className="h-16 w-16 object-contain"
              />
            )}
            <h2 className="text-2xl font-bold text-gray-900">
              {match.home_team?.name || 'TBD'}
            </h2>
          </div>
        </div>

        {/* Score Display */}
        <div className="text-center">
          {isCompleted || isLive ? (
            <div className="space-y-2">
              <div className="flex items-center justify-center gap-4">
                <span className="text-5xl font-bold text-gray-900">
                  {match.home_score ?? 0}
                </span>
                <span className="text-2xl text-gray-500">-</span>
                <span className="text-5xl font-bold text-gray-900">
                  {match.away_score ?? 0}
                </span>
              </div>
              {isLive && match.current_minute && (
                <div className="flex items-center justify-center gap-2">
                  <span className="h-2 w-2 bg-red-600 rounded-full animate-pulse"></span>
                  <span className="text-sm font-semibold text-red-600">
                    {match.current_minute}'
                  </span>
                </div>
              )}
              {isCompleted && (
                <span className="text-sm text-gray-600">Full Time</span>
              )}
            </div>
          ) : (
            <div className="text-gray-500 text-lg font-medium">VS</div>
          )}
        </div>

        {/* Away Team */}
        <div className="text-center md:text-left">
          <div className="flex items-center justify-center md:justify-start gap-3 mb-2">
            <h2 className="text-2xl font-bold text-gray-900">
              {match.away_team?.name || 'TBD'}
            </h2>
            {match.away_team?.logo && (
              <img
                src={match.away_team.logo}
                alt={match.away_team.name}
                className="h-16 w-16 object-contain"
              />
            )}
          </div>
        </div>
      </div>

      {/* Match Info */}
      <div className="mt-6 pt-6 border-t border-gray-200">
        <div className="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600">
          <div className="flex items-center gap-2">
            <span className="font-medium">Date & Time:</span>
            <span>{formatDateTime(match.match_date)}</span>
          </div>
          {match.venue && (
            <div className="flex items-center gap-2">
              <span className="font-medium">Venue:</span>
              <span>{match.venue.name}</span>
              {match.venue.address && (
                <span className="text-gray-500">• {match.venue.address}</span>
              )}
            </div>
          )}
          <div className="flex items-center gap-2">
            <span className="font-medium">Status:</span>
            <Badge
              variant={
                isLive ? 'danger' :
                match.status === 'scheduled' ? 'info' :
                match.status === 'completed' ? 'default' : 'warning'
              }
            >
              {match.status === 'in_progress' ? 'Live' : match.status}
            </Badge>
          </div>
        </div>
      </div>
    </div>
  );
};

export default MatchHeader;
