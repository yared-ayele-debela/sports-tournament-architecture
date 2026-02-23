import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Calendar, MapPin, Radio, Target, AlertCircle, Users, Wifi, WifiOff } from 'lucide-react';
import { matchService } from '../../api/matches';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import TeamLogo from '../../components/team/TeamLogo';
import { formatDate, formatDateTime } from '../../utils/dateUtils';
import Badge from '../../components/common/Badge';
import { useMatchWebSocket } from '../../hooks/useMatchWebSocket';

const MatchDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [localMatch, setLocalMatch] = useState(null);
  const [localEvents, setLocalEvents] = useState([]);

  // WebSocket handlers (defined before useMatchWebSocket to avoid reference errors)
  const handleEventRecorded = useCallback((data) => {
    // Update query cache directly (prevents refetch)
    queryClient.setQueryData(['match-events', id], (oldData) => {
      const existingEvents = oldData?.data?.events || oldData?.data || oldData || [];
      const newEvent = data.event;
      
      // Check if event already exists (idempotency)
      if (existingEvents.find((e) => e.id === newEvent.id)) {
        return oldData;
      }
      
      const updatedEvents = [...existingEvents, newEvent].sort((a, b) => (a.minute || 0) - (b.minute || 0));
      
      // Return in same format as API response
      if (oldData?.data) {
        return { ...oldData, data: { ...oldData.data, events: updatedEvents } };
      }
      return updatedEvents;
    });
    
    // Update local events state
    setLocalEvents((prev) => {
      const newEvent = data.event;
      if (prev.find((e) => e.id === newEvent.id)) {
        return prev;
      }
      return [...prev, newEvent].sort((a, b) => (a.minute || 0) - (b.minute || 0));
    });

    // Update match data if provided
    if (data.match) {
      queryClient.setQueryData(['match', id], (oldData) => {
        const matchData = oldData?.data || oldData;
        return {
          ...oldData,
          data: { ...matchData, ...data.match }
        };
      });
      
      setLocalMatch((prev) => ({
        ...prev,
        ...data.match,
      }));
    }
  }, [id, queryClient]);

  const handleScoreUpdated = useCallback((data) => {
    // Update query cache directly (prevents refetch)
    queryClient.setQueryData(['match', id], (oldData) => {
      const matchData = oldData?.data || oldData;
      return {
        ...oldData,
        data: {
          ...matchData,
          home_score: data.home_score,
          away_score: data.away_score,
          current_minute: data.current_minute,
          status: data.status,
        }
      };
    });
    
    setLocalMatch((prev) => ({
      ...prev,
      home_score: data.home_score,
      away_score: data.away_score,
      current_minute: data.current_minute,
      status: data.status,
    }));
  }, [id, queryClient]);

  const handleMinuteUpdated = useCallback((data) => {
    // Update query cache directly
    queryClient.setQueryData(['match', id], (oldData) => {
      const matchData = oldData?.data || oldData;
      return {
        ...oldData,
        data: {
          ...matchData,
          current_minute: data.current_minute,
          status: data.status,
          ...(data.home_score !== undefined && { home_score: data.home_score }),
          ...(data.away_score !== undefined && { away_score: data.away_score }),
        }
      };
    });
    
    setLocalMatch((prev) => ({
      ...prev,
      current_minute: data.current_minute,
      status: data.status,
      ...(data.home_score !== undefined && { home_score: data.home_score }),
      ...(data.away_score !== undefined && { away_score: data.away_score }),
    }));
  }, [id, queryClient]);

  const handleStatusChanged = useCallback((data) => {
    // Update query cache directly
    queryClient.setQueryData(['match', id], (oldData) => {
      const matchData = oldData?.data || oldData;
      return {
        ...oldData,
        data: {
          ...matchData,
          status: data.new_status,
          current_minute: data.current_minute,
          ...(data.home_score !== undefined && { home_score: data.home_score }),
          ...(data.away_score !== undefined && { away_score: data.away_score }),
        }
      };
    });
    
    setLocalMatch((prev) => ({
      ...prev,
      status: data.new_status,
      current_minute: data.current_minute,
      ...(data.home_score !== undefined && { home_score: data.home_score }),
      ...(data.away_score !== undefined && { away_score: data.away_score }),
    }));
  }, [id, queryClient]);

  // Connect to WebSocket for this match
  const { isConnected } = useMatchWebSocket(id, {
    enabled: true,
    onEventRecorded: handleEventRecorded,
    onScoreUpdated: handleScoreUpdated,
    onMinuteUpdated: handleMinuteUpdated,
    onStatusChanged: handleStatusChanged,
  });

  // Fetch match details
  const { data: matchData, isLoading: matchLoading, error: matchError } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchService.getById(id),
    refetchInterval: (data) => {
      const match = data?.data || data;
      // Only poll if WebSocket is not connected (fallback)
      return (match?.status === 'live' || match?.status === 'in_progress') && !isConnected ? 30000 : false;
    },
    staleTime: 10000,
    refetchOnMount: true,
    refetchOnWindowFocus: false,
  });

  // Fetch match events
  const { data: eventsData, isLoading: eventsLoading } = useQuery({
    queryKey: ['match-events', id],
    queryFn: () => matchService.getEvents(id),
    enabled: !!matchData,
    refetchInterval: (data) => {
      const match = matchData?.data || matchData;
      // Only poll if WebSocket is not connected (fallback)
      return (match?.status === 'live' || match?.status === 'in_progress') && !isConnected ? 30000 : false;
    },
    staleTime: 10000,
    refetchOnMount: true,
    refetchOnWindowFocus: false,
  });

  // Initialize local state from fetched data (only if not already set to preserve WebSocket updates)
  useEffect(() => {
    if (matchData && !localMatch) {
      const fetchedMatch = matchData?.data || matchData;
      setLocalMatch(fetchedMatch);
    }
  }, [matchData, localMatch]);

  useEffect(() => {
    if (eventsData && localEvents.length === 0) {
      const fetchedEvents = eventsData?.data?.events || eventsData?.data || eventsData || [];
      setLocalEvents(fetchedEvents);
    }
  }, [eventsData, localEvents.length]);

  const match = localMatch || matchData?.data || matchData;
  const isLive = match?.status === 'live' || match?.status === 'in_progress';
  const isCompleted = match?.status === 'completed';
  const events = localEvents.length > 0 ? localEvents : (eventsData?.data?.events || eventsData?.data || eventsData || []);
  
  // Event type labels and icons
  const EVENT_TYPE_LABELS = {
    goal: 'Goal',
    yellow_card: 'Yellow Card',
    red_card: 'Red Card',
    substitution: 'Substitution',
  };

  const EVENT_TYPE_ICONS = {
    goal: Target,
    yellow_card: AlertCircle,
    red_card: AlertCircle,
    substitution: Users,
  };

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
          className="mb-6 flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
        >
          <ArrowLeft className="h-4 w-4" />
          <span className="text-sm font-medium">Back to Matches</span>
        </button>

        {/* Match Header */}
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
          {/* Tournament and Round Info */}
          <div className="text-center mb-6">
            {match.tournament && (
              <p className="text-sm text-gray-600 mb-1">{match.tournament.name}</p>
            )}
            {match.round_number && (
              <p className="text-xs text-gray-500">Round {match.round_number}</p>
            )}
          </div>

          {/* Teams and Score */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center mb-6">
            {/* Home Team */}
            <div className="text-center md:text-right">
              <div className="flex flex-col items-center md:items-end gap-3">
                <TeamLogo
                  logo={match.home_team?.logo}
                  name={match.home_team?.name || 'Home Team'}
                  size="lg"
                />
                <h3 className="text-lg font-semibold text-gray-900">
                  {match.home_team?.name || 'Home Team'}
                </h3>
              </div>
            </div>

            {/* Score */}
            <div className="text-center">
              {(isCompleted || isLive) ? (
                <div className="space-y-3">
                  <div className="text-4xl md:text-5xl font-bold text-gray-900">
                    {match.home_score ?? 0} - {match.away_score ?? 0}
                  </div>
                  {isLive && match.current_minute && (
                    <div className="flex items-center justify-center gap-2">
                      <span className="h-2 w-2 bg-red-600 rounded-full animate-pulse"></span>
                      <span className="text-sm font-semibold text-red-600">
                        Live - {match.current_minute}'
                      </span>
                    </div>
                  )}
                  {isCompleted && (
                    <p className="text-sm text-gray-600">Full Time</p>
                  )}
                </div>
              ) : (
                <div className="space-y-2">
                  <div className="text-sm text-gray-600 font-medium">VS</div>
                  <div className="text-sm text-gray-600">
                    {match.match_date ? formatDateTime(match.match_date) : 'TBD'}
                  </div>
                </div>
              )}
            </div>

            {/* Away Team */}
            <div className="text-center md:text-left">
              <div className="flex flex-col items-center md:items-start gap-3">
                <TeamLogo
                  logo={match.away_team?.logo}
                  name={match.away_team?.name || 'Away Team'}
                  size="lg"
                />
                <h3 className="text-lg font-semibold text-gray-900">
                  {match.away_team?.name || 'Away Team'}
                </h3>
              </div>
            </div>
          </div>

          {/* Status Badge */}
          <div className="flex justify-center">
            <Badge variant={isLive ? 'danger' : isCompleted ? 'default' : 'info'}>
              {match.status === 'in_progress' ? 'Live' : match.status?.charAt(0).toUpperCase() + match.status?.slice(1)}
            </Badge>
          </div>
        </div>

        {/* Live Indicator Banner */}
        {isLive && (
          <div className="bg-red-100 border border-red-200 rounded-lg p-4 mb-6">
            <div className="flex items-center justify-center gap-2">
              <span className="h-2 w-2 bg-red-600 rounded-full animate-pulse"></span>
              <span className="font-semibold text-red-800 text-sm">LIVE MATCH</span>
              {isConnected ? (
                <span className="text-xs text-red-700 ml-2 flex items-center gap-1">
                  <Wifi className="h-3 w-3" />
                  Real-time updates
                </span>
              ) : (
                <span className="text-xs text-red-700 ml-2">Polling mode</span>
              )}
            </div>
          </div>
        )}

        {/* Match Information */}
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Match Information</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {match.match_date && (
              <div className="flex items-start gap-3">
                <Calendar className="h-5 w-5 text-gray-500 mt-0.5" />
                <div>
                  <div className="text-xs text-gray-600 mb-1">Date & Time</div>
                  <div className="text-sm font-medium text-gray-900">
                    {formatDateTime(match.match_date)}
                  </div>
                </div>
              </div>
            )}
            {match.venue && (
              <div className="flex items-start gap-3">
                <MapPin className="h-5 w-5 text-gray-500 mt-0.5" />
                <div>
                  <div className="text-xs text-gray-600 mb-1">Venue</div>
                  <div className="text-sm font-medium text-gray-900">
                    {match.venue.name || match.venue}
                  </div>
                  {match.venue.address && (
                    <div className="text-xs text-gray-600 mt-1">{match.venue.address}</div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Match Events */}
        {(isLive || isCompleted) && (
          <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Match Events</h3>
              {isLive && (
                <div className="flex items-center gap-2 text-xs text-gray-600">
                  {isConnected ? (
                    <>
                      <Wifi className="h-3 w-3 text-green-600" />
                      <span>Real-time</span>
                    </>
                  ) : (
                    <>
                      <div className="h-2 w-2 bg-yellow-500 rounded-full animate-pulse"></div>
                      <span>Polling</span>
                    </>
                  )}
                </div>
              )}
            </div>
            {eventsLoading ? (
              <Loading />
            ) : events.length > 0 ? (
              <div className="space-y-3">
                {events
                  .sort((a, b) => (a.minute || 0) - (b.minute || 0))
                  .map((event) => {
                    const EventIcon = EVENT_TYPE_ICONS[event.event_type] || AlertCircle;
                    // Use event.team if available, otherwise fallback to match teams
                    const eventTeam = event.team || 
                      (event.team_id === match?.home_team_id 
                        ? match.home_team 
                        : match?.away_team);
                    
                    return (
                      <div
                        key={event.id}
                        className="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
                      >
                        <div className="w-10 h-10 border border-gray-300 rounded-full flex items-center justify-center font-semibold text-sm text-gray-700 flex-shrink-0">
                          {event.minute}'
                        </div>
                        <div className="flex items-center gap-3 flex-1 min-w-0">
                          <EventIcon className={`w-4 h-4 flex-shrink-0 ${
                            event.event_type === 'goal' ? 'text-green-600' :
                            event.event_type === 'red_card' ? 'text-red-600' :
                            event.event_type === 'yellow_card' ? 'text-yellow-600' :
                            'text-gray-600'
                          }`} />
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1 flex-wrap">
                              <p className="text-sm font-medium text-gray-900">
                                {EVENT_TYPE_LABELS[event.event_type] || event.event_type}
                              </p>
                              {eventTeam && (
                                <span className="px-2 py-0.5 border border-gray-300 rounded text-xs font-medium text-gray-700">
                                  {eventTeam.name || eventTeam?.name || `Team ${event.team_id || event.team?.id}`}
                                </span>
                              )}
                            </div>
                            {event.player && (
                              <p className="text-sm text-gray-700">
                                {event.player.full_name || event.player.name || `Player ${event.player_id || event.player?.id}`}
                                {event.player.jersey_number && ` (#${event.player.jersey_number})`}
                              </p>
                            )}
                            {event.description && (
                              <p className="text-xs text-gray-600 mt-1">{event.description}</p>
                            )}
                          </div>
                        </div>
                      </div>
                    );
                  })}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500 text-sm">
                No events recorded yet.
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default MatchDetails;
