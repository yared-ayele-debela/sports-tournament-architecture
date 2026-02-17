import { useState, useEffect } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Radio, RefreshCw, Wifi, WifiOff } from 'lucide-react';
import { matchService } from '../../api/matches';
import MatchCard from '../../components/match/MatchCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import { useLiveMatchesWebSocket } from '../../hooks/useMatchWebSocket';

const LiveMatches = () => {
  const queryClient = useQueryClient();
  const [matches, setMatches] = useState([]);

  // Fetch live matches
  const { data: liveMatchesData, isLoading, error, refetch, isRefetching } = useQuery({
    queryKey: ['liveMatches'],
    queryFn: () => matchService.getLive(),
    refetchInterval: 30000, // Fallback polling every 30 seconds (WebSocket is primary)
    staleTime: 10000,
    refetchOnMount: true,
    refetchOnWindowFocus: true,
  });

  // Initialize matches from initial fetch
  useEffect(() => {
    if (liveMatchesData) {
      const fetchedMatches = liveMatchesData?.data?.matches || liveMatchesData?.matches || liveMatchesData || [];
      setMatches(fetchedMatches);
    }
  }, [liveMatchesData]);

  // WebSocket handler for real-time updates
  const handleMatchUpdate = (update) => {
    const { type, data } = update;
    
    setMatches((prevMatches) => {
      const matchIndex = prevMatches.findIndex((m) => m.id === data.match_id);
      
      if (matchIndex === -1) {
        // Match not in list, might be a new live match - refetch
        refetch();
        return prevMatches;
      }

      const updatedMatches = [...prevMatches];
      const match = updatedMatches[matchIndex];

      switch (type) {
        case 'score':
          updatedMatches[matchIndex] = {
            ...match,
            home_score: data.home_score,
            away_score: data.away_score,
          };
          break;
        case 'minute':
          updatedMatches[matchIndex] = {
            ...match,
            current_minute: data.current_minute,
            status: data.status,
          };
          break;
        case 'status':
          updatedMatches[matchIndex] = {
            ...match,
            status: data.new_status,
            current_minute: data.current_minute,
          };
          // If match is no longer live, remove it from list
          if (data.new_status !== 'in_progress' && data.new_status !== 'live') {
            return prevMatches.filter((m) => m.id !== data.match_id);
          }
          break;
        case 'event':
          // Events are handled separately, but we can update match data if needed
          if (data.match) {
            updatedMatches[matchIndex] = {
              ...match,
              ...data.match,
            };
          }
          break;
        default:
          break;
      }

      return updatedMatches;
    });

    // Invalidate and refetch match details for the updated match
    queryClient.invalidateQueries(['match', data.match_id]);
    queryClient.invalidateQueries(['match-events', data.match_id]);
  };

  // Connect to WebSocket for live updates
  const { isConnected } = useLiveMatchesWebSocket(handleMatchUpdate);

  const liveMatches = matches;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 bg-red-600 rounded-full animate-pulse"></div>
              <h1 className="text-3xl font-bold text-gray-900">Live Matches</h1>
              {/* WebSocket Connection Status */}
              <div className="flex items-center gap-2 ml-4">
                {isConnected ? (
                  <>
                    <Wifi className="h-4 w-4 text-green-600" />
                    <span className="text-sm text-green-600 font-medium">Live</span>
                  </>
                ) : (
                  <>
                    <WifiOff className="h-4 w-4 text-gray-400" />
                    <span className="text-sm text-gray-400">Polling</span>
                  </>
                )}
              </div>
            </div>
            <button
              onClick={() => refetch()}
              disabled={isRefetching}
              className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <RefreshCw className={`h-4 w-4 ${isRefetching ? 'animate-spin' : ''}`} />
              {isRefetching ? 'Updating...' : 'Refresh'}
            </button>
          </div>
          <p className="text-gray-600">
            {isConnected 
              ? 'Real-time match updates via WebSocket. Scores and events update instantly.'
              : 'Real-time match updates. Scores and events update automatically every 30 seconds.'}
          </p>
        </div>

        {/* Live Indicator Banner */}
        {liveMatches.length > 0 && (
          <div className="bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg p-4 mb-6">
            <div className="flex items-center justify-center gap-2">
              <Radio className="h-5 w-5 animate-pulse" />
              <span className="font-bold text-lg">
                {liveMatches.length} {liveMatches.length === 1 ? 'Match' : 'Matches'} Live Now
              </span>
            </div>
          </div>
        )}

        {/* Results */}
        {isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load live matches. Please try again later."
            onRetry={() => refetch()}
          />
        ) : liveMatches.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {liveMatches.map((match) => (
              <MatchCard key={match.id} match={match} />
            ))}
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Radio className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">No Live Matches</h3>
            <p className="text-gray-600 mb-6">
              There are currently no matches in progress. Check back later or view upcoming matches.
            </p>
            <div className="flex gap-4 justify-center">
              <Link
                to="/matches"
                className="px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors"
              >
                View All Matches
              </Link>
              <Link
                to="/matches?status=upcoming"
                className="px-6 py-3 border-2 border-primary-600 text-primary-600 rounded-lg font-semibold hover:bg-primary-50 transition-colors"
              >
                Upcoming Matches
              </Link>
            </div>
          </div>
        )}

        {/* Auto-refresh indicator */}
        {liveMatches.length > 0 && (
          <div className="mt-6 text-center text-sm text-gray-500">
            <div className="flex items-center justify-center gap-2">
              {isConnected ? (
                <>
                  <div className="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                  <span>WebSocket connected - Real-time updates active</span>
                </>
              ) : (
                <>
                  <div className="h-2 w-2 bg-yellow-500 rounded-full animate-pulse"></div>
                  <span>Polling mode - Auto-refreshing every 30 seconds</span>
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default LiveMatches;
