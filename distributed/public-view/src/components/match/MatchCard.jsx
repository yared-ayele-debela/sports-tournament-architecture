import { Link } from 'react-router-dom';
import Card from '../common/Card';
import Badge from '../common/Badge';
import { formatDateTime, formatTime } from '../../utils/dateUtils';
import { STATUS_COLORS } from '../../utils/constants';

const MatchCard = ({ match }) => {
  const isLive = match.status === 'live' || match.status === 'in_progress';
  const isCompleted = match.status === 'completed';

  return (
    <Card
      hover
      onClick={() => window.location.href = `/matches/${match.id}`}
      className="h-full"
      role="button"
      tabIndex={0}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          window.location.href = `/matches/${match.id}`;
        }
      }}
    >
      <div className="p-6">
        {/* Live Indicator */}
        {isLive && (
          <div className="flex items-center justify-center mb-4">
            <span className="flex items-center gap-2 px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
              <span className="h-2 w-2 bg-red-600 rounded-full animate-pulse"></span>
              LIVE
            </span>
          </div>
        )}

        {/* Teams and Score */}
        <div className="space-y-4">
          {/* Home Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3 flex-1">
              {match.home_team?.logo && (
                <img
                  src={match.home_team.logo}
                  alt={match.home_team.name}
                  className="h-10 w-10 object-contain"
                />
              )}
              <span className="font-medium text-gray-900">
                {match.home_team?.name || match.home_team_name}
              </span>
            </div>
            {isCompleted || isLive ? (
              <span className="text-2xl font-bold text-gray-900">
                {match.home_score ?? 0}
              </span>
            ) : null}
          </div>

          {/* VS or Score Separator */}
          <div className="text-center text-gray-500 text-sm">
            {isCompleted || isLive ? 'vs' : 'VS'}
          </div>

          {/* Away Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3 flex-1">
              {match.away_team?.logo && (
                <img
                  src={match.away_team.logo}
                  alt={match.away_team.name}
                  className="h-10 w-10 object-contain"
                />
              )}
              <span className="font-medium text-gray-900">
                {match.away_team?.name || match.away_team_name}
              </span>
            </div>
            {isCompleted || isLive ? (
              <span className="text-2xl font-bold text-gray-900">
                {match.away_score ?? 0}
              </span>
            ) : null}
          </div>
        </div>

        {/* Match Info */}
        <div className="mt-4 pt-4 border-t border-gray-200 space-y-2">
          <div className="flex items-center justify-between text-sm">
            <span className="text-gray-600">Date & Time:</span>
            <span className="text-gray-900 font-medium">
              {formatDateTime(match.match_date)}
            </span>
          </div>
          {match.venue && (
            <div className="flex items-center justify-between text-sm">
              <span className="text-gray-600">Venue:</span>
              <span className="text-gray-900 font-medium">{match.venue.name || match.venue}</span>
            </div>
          )}
          <div className="flex items-center justify-between text-sm">
            <span className="text-gray-600">Status:</span>
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

        {/* View Details Link */}
        <Link
          to={`/matches/${match.id}`}
          className="mt-4 block text-center text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors"
        >
          View Details →
        </Link>
      </div>
    </Card>
  );
};

export default MatchCard;
