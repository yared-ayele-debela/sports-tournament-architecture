import { Link, useNavigate } from 'react-router-dom';
import { Calendar, MapPin, Clock, ArrowRight } from 'lucide-react';
import Badge from '../common/Badge';
import TeamLogo from '../team/TeamLogo';
import { formatDateTime, formatTime } from '../../utils/dateUtils';
import { STATUS_COLORS } from '../../utils/constants';

const MatchCard = ({ match }) => {
  const navigate = useNavigate();
  const isLive = match.status === 'live' || match.status === 'in_progress';
  const isCompleted = match.status === 'completed';
  const isScheduled = match.status === 'scheduled';

  return (
    <div
      onClick={() => navigate(`/matches/${match.id}`)}
      className="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer border border-gray-100 overflow-hidden h-full flex flex-col group"
    >
      {/* Live Indicator Header */}
      {isLive && (
        <div className="bg-gradient-to-r from-red-600 to-red-700 text-white py-2 px-4 text-center">
          <div className="flex items-center justify-center gap-2">
            <span className="h-2 w-2 bg-white rounded-full animate-pulse"></span>
            <span className="text-sm font-bold">LIVE NOW</span>
            {match.current_minute && (
              <span className="text-xs opacity-90">{match.current_minute}'</span>
            )}
          </div>
        </div>
      )}

      {/* Match Content */}
      <div className="p-6 flex-1 flex flex-col">
        {/* Tournament/Competition Name */}
        {match.tournament && (
          <div className="text-center mb-4">
            <p className="text-xs font-semibold text-primary-600 uppercase tracking-wide">
              {match.tournament.name}
            </p>
            {match.round_number && (
              <p className="text-xs text-gray-500 mt-1">Round {match.round_number}</p>
            )}
          </div>
        )}

        {/* Teams and Score */}
        <div className="space-y-4 mb-6">
          {/* Home Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3 flex-1 min-w-0">
              <div className="flex-shrink-0 bg-gray-50 p-1 rounded-lg">
                <TeamLogo
                  logo={match.home_team?.logo}
                  name={match.home_team?.name || match.home_team_name || 'Home Team'}
                  size="md"
                />
              </div>
              <span className="font-semibold text-gray-900 truncate">
                {match.home_team?.name || match.home_team_name || 'Home Team'}
              </span>
            </div>
            {(isCompleted || isLive) && (
              <div className="flex-shrink-0">
                <span className="text-3xl font-bold text-gray-900">
                  {match.home_score ?? 0}
                </span>
              </div>
            )}
          </div>

          {/* VS Separator */}
          <div className="flex items-center justify-center my-2">
            <div className="flex-1 border-t border-gray-300"></div>
            <div className="px-4">
              {isCompleted || isLive ? (
                <span className="text-sm font-semibold text-gray-500">VS</span>
              ) : (
                <span className="text-xs font-semibold text-gray-400 uppercase">vs</span>
              )}
            </div>
            <div className="flex-1 border-t border-gray-300"></div>
          </div>

          {/* Away Team */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3 flex-1 min-w-0">
              <div className="flex-shrink-0 bg-gray-50 p-1 rounded-lg">
                <TeamLogo
                  logo={match.away_team?.logo}
                  name={match.away_team?.name || match.away_team_name || 'Away Team'}
                  size="md"
                />
              </div>
              <span className="font-semibold text-gray-900 truncate">
                {match.away_team?.name || match.away_team_name || 'Away Team'}
              </span>
            </div>
            {(isCompleted || isLive) && (
              <div className="flex-shrink-0">
                <span className="text-3xl font-bold text-gray-900">
                  {match.away_score ?? 0}
                </span>
              </div>
            )}
          </div>
        </div>

        {/* Match Info */}
        <div className="mt-auto space-y-3 pt-4 border-t border-gray-200">
          {match.match_date && (
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <Calendar className="h-4 w-4 text-primary-600" />
              <span className="font-medium text-gray-700">
                {formatDateTime(match.match_date)}
              </span>
            </div>
          )}
          {match.venue && (
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <MapPin className="h-4 w-4 text-primary-600" />
              <span className="truncate">
                {match.venue.name || match.venue}
              </span>
            </div>
          )}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <Clock className="h-4 w-4 text-primary-600" />
              <span>Status:</span>
            </div>
            <Badge
              variant={
                isLive ? 'danger' :
                match.status === 'scheduled' ? 'info' :
                match.status === 'completed' ? 'default' : 'warning'
              }
            >
              {match.status === 'in_progress' ? 'Live' : match.status?.charAt(0).toUpperCase() + match.status?.slice(1)}
            </Badge>
          </div>
        </div>

        {/* View Details Button */}
        <Link
          to={`/matches/${match.id}`}
          className="mt-4 flex items-center justify-center gap-2 w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm rounded-lg transition-colors group-hover:shadow-md"
          onClick={(e) => e.stopPropagation()}
        >
          View Details
          <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
        </Link>
      </div>
    </div>
  );
};

export default MatchCard;
