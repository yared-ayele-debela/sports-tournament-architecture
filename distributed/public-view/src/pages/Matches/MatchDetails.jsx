import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft, Calendar, MapPin } from 'lucide-react';
import { matchService } from '../../api/matches';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import TeamLogo from '../../components/team/TeamLogo';
import { formatDate, formatDateTime } from '../../utils/dateUtils';
import Badge from '../../components/common/Badge';

const MatchDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  // Fetch match details
  const { data: matchData, isLoading: matchLoading, error: matchError } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchService.getById(id),
    refetchInterval: (data) => {
      const match = data?.data || data;
      return match?.status === 'live' || match?.status === 'in_progress' ? 30000 : false;
    },
  });

  const match = matchData?.data || matchData;
  const isLive = match?.status === 'live' || match?.status === 'in_progress';
  const isCompleted = match?.status === 'completed';

  if (matchLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Loading />
        </div>
      </div>
    );
  }

  if (matchError || !match) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <ErrorMessage
            message="Failed to load match details."
            onRetry={() => navigate('/matches')}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Back Button */}
        <button
          onClick={() => navigate(-1)}
          className="mb-4 flex items-center gap-2 text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Matches
        </button>

        {/* Match Header */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-6">
          <div className="text-center mb-6">
            {match.tournament && (
              <p className="text-sm text-gray-600 mb-2">{match.tournament.name}</p>
            )}
            {match.round_number && (
              <p className="text-sm text-gray-600 mb-2">Round {match.round_number}</p>
            )}
          </div>

          {/* Teams and Score */}
          <div className="grid grid-cols-3 gap-4 items-center mb-6">
            {/* Home Team */}
            <div className="text-right">
              <h3 className="text-xl font-bold text-gray-900 mb-2">
                {match.home_team?.name || 'Home Team'}
              </h3>
              <div className="flex justify-center">
                <TeamLogo
                  logo={match.home_team?.logo}
                  name={match.home_team?.name || 'Home Team'}
                  size="md"
                />
              </div>
            </div>

            {/* Score */}
            <div className="text-center">
              {(isCompleted || isLive) ? (
                <>
                  <div className="text-5xl font-bold text-gray-900 mb-2">
                    {match.home_score ?? 0} - {match.away_score ?? 0}
                  </div>
                  {isLive && match.current_minute && (
                    <div className="flex items-center justify-center gap-2">
                      <span className="h-3 w-3 bg-red-600 rounded-full animate-pulse"></span>
                      <span className="text-sm font-semibold text-red-600">
                        Live - {match.current_minute}'
                      </span>
                    </div>
                  )}
                  {isCompleted && (
                    <p className="text-sm text-gray-600 mt-2">Full Time</p>
                  )}
                </>
              ) : (
                <div className="text-lg text-gray-600">
                  {match.match_date ? formatDateTime(match.match_date) : 'TBD'}
                </div>
              )}
            </div>

            {/* Away Team */}
            <div className="text-left">
              <h3 className="text-xl font-bold text-gray-900 mb-2">
                {match.away_team?.name || 'Away Team'}
              </h3>
              <div className="flex justify-center">
                <TeamLogo
                  logo={match.away_team?.logo}
                  name={match.away_team?.name || 'Away Team'}
                  size="md"
                />
              </div>
            </div>
          </div>

          {/* Status Badge */}
          <div className="flex justify-center gap-2 mb-4">
            <Badge color={isLive ? 'red' : isCompleted ? 'green' : 'gray'}>
              {match.status === 'in_progress' ? 'Live' : match.status?.charAt(0).toUpperCase() + match.status?.slice(1)}
            </Badge>
          </div>
        </div>

        {/* Match Information */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-xl font-bold text-gray-900 mb-4">Match Information</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {match.match_date && (
              <div className="flex items-center gap-2">
                <Calendar className="h-5 w-5 text-gray-400" />
                <div>
                  <div className="text-sm text-gray-600">Date & Time</div>
                  <div className="font-medium text-gray-900">
                    {formatDateTime(match.match_date)}
                  </div>
                </div>
              </div>
            )}
            {match.venue && (
              <div className="flex items-center gap-2">
                <MapPin className="h-5 w-5 text-gray-400" />
                <div>
                  <div className="text-sm text-gray-600">Venue</div>
                  <div className="font-medium text-gray-900">
                    {match.venue.name || match.venue}
                  </div>
                  {match.venue.address && (
                    <div className="text-sm text-gray-600">{match.venue.address}</div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default MatchDetails;
