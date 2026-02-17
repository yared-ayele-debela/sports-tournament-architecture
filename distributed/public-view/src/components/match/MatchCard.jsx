import { Link, useNavigate } from 'react-router-dom';
import Badge from '../common/Badge';
import { formatDateTime } from '../../utils/dateUtils';

const MatchCard = ({ match }) => {
  const navigate = useNavigate();
  const isLive = match.status === 'live' || match.status === 'in_progress';
  const isCompleted = match.status === 'completed';

  return (
    <div
      onClick={() => navigate(`/matches/${match.id}`)}
      className="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors cursor-pointer h-full flex flex-col"
    >
      <div className="p-4">
        {/* Live Indicator */}
        {isLive && (
          <div className="flex items-center justify-center mb-3">
            <span className="flex items-center gap-2 px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
              <span className="h-2 w-2 bg-red-600 rounded-full animate-pulse"></span>
              LIVE
            </span>
          </div>
        )}

        {/* Teams and Score */}
        <div className="space-y-3 mb-4">
          {/* Home Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 flex-1 min-w-0">
              {match.home_team?.logo && (
                <img
                  src={match.home_team.logo}
                  alt={match.home_team.name}
                  className="h-8 w-8 object-contain flex-shrink-0"
                />
              )}
              <span className="font-medium text-gray-900 text-sm truncate">
                {match.home_team?.name || match.home_team_name}
              </span>
            </div>
            {isCompleted || isLive ? (
              <span className="text-xl font-semibold text-gray-900 ml-2">
                {match.home_score ?? 0}
              </span>
            ) : null}
          </div>

          {/* VS or Score Separator */}
          <div className="text-center text-gray-500 text-xs">
            {isCompleted || isLive ? 'vs' : 'VS'}
          </div>

          {/* Away Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 flex-1 min-w-0">
              {match.away_team?.logo && (
                <img
                  src={match.away_team.logo}
                  alt={match.away_team.name}
                  className="h-8 w-8 object-contain flex-shrink-0"
                />
              )}
              <span className="font-medium text-gray-900 text-sm truncate">
                {match.away_team?.name || match.away_team_name}
              </span>
            </div>
            {isCompleted || isLive ? (
              <span className="text-xl font-semibold text-gray-900 ml-2">
                {match.away_score ?? 0}
              </span>
            ) : null}
          </div>
        </div>

        {/* Match Info */}
        <div className="mt-auto pt-3 border-t border-gray-200 space-y-2">
          <div className="flex items-center justify-between text-xs">
            <span className="text-gray-600">Date:</span>
            <span className="text-gray-900 font-medium">
              {formatDateTime(match.match_date)}
            </span>
          </div>
          {match.venue && (
            <div className="flex items-center justify-between text-xs">
              <span className="text-gray-600">Venue:</span>
              <span className="text-gray-900 font-medium truncate ml-2">{match.venue.name || match.venue}</span>
            </div>
          )}
          <div className="flex items-center justify-between text-xs">
            <span className="text-gray-600">Status:</span>
            <Badge variant="default">
              {match.status === 'in_progress' ? 'Live' : match.status}
            </Badge>
          </div>
        </div>
      </div>
    </div>
  );
};

export default MatchCard;
