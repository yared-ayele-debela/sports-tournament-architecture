import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { matchesService } from '../../api/matches';
import { teamsService } from '../../api/teams';
import { resultsService } from '../../api/results';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import Modal from '../../components/common/Modal';
import { ArrowLeft, Calendar, MapPin, Clock, Trophy, CheckCircle, Plus, X, Target, AlertCircle, Users, Trash2, Pencil } from 'lucide-react';

const STATUS_OPTIONS = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

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

export default function MyMatchDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();
  const [activeTab, setActiveTab] = useState('overview');
  const [showEventForm, setShowEventForm] = useState(false);
  const [editingEventId, setEditingEventId] = useState(null);
  const [deleteEventId, setDeleteEventId] = useState(null);
  const [showFinalizeModal, setShowFinalizeModal] = useState(false);
  const [finalizeForm, setFinalizeForm] = useState({
    home_score: '',
    away_score: '',
    completed_at: new Date().toISOString().slice(0, 16),
  });
  const [finalizeEvents, setFinalizeEvents] = useState([]);
  const [finalizeErrors, setFinalizeErrors] = useState({});
  const [showReportForm, setShowReportForm] = useState(false);
  const [reportForm, setReportForm] = useState({
    summary: '',
    referee: '',
    attendance: '',
    home_score: '',
    away_score: '',
    duration_minutes: 90,
  });
  const [reportErrors, setReportErrors] = useState({});
  const [autoUpdateEnabled, setAutoUpdateEnabled] = useState(false);
  const [countdown, setCountdown] = useState(60);
  const autoUpdateIntervalRef = useRef(null);
  const countdownIntervalRef = useRef(null);
  const matchMinuteRef = useRef(null);
  const minuteMutationRef = useRef(null);
  const isUpdatingRef = useRef(false); // Prevent duplicate updates

  // Check if user can record events
  const canRecordEvents = hasPermission('record_events') || isAdmin();

  const { data: match, isLoading, error } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchesService.get(id),
  });

  const { data: eventsData, refetch: refetchEvents } = useQuery({
    queryKey: ['match-events', id],
    queryFn: () => matchesService.getEvents(id),
    enabled: !!match,
  });

  const events = Array.isArray(eventsData) ? eventsData : eventsData?.data || [];

  // Event form state
  const [eventForm, setEventForm] = useState({
    team_id: match?.home_team_id || '',
    player_id: '',
    event_type: 'goal',
    minute: match?.current_minute || 0,
    description: '',
  });

  // Fetch players dynamically based on selected team in event form
  const { data: selectedTeamPlayersData, isLoading: loadingPlayers } = useQuery({
    queryKey: ['team-squad', eventForm.team_id],
    queryFn: () => teamsService.getSquad(eventForm.team_id, { per_page: 100 }),
    enabled: !!eventForm.team_id && showEventForm,
  });

  // Extract players from squad response
  const selectedTeamPlayers = (() => {
    if (!selectedTeamPlayersData) return [];
    if (Array.isArray(selectedTeamPlayersData)) return selectedTeamPlayersData;
    if (Array.isArray(selectedTeamPlayersData.data)) return selectedTeamPlayersData.data;
    if (selectedTeamPlayersData.data?.data && Array.isArray(selectedTeamPlayersData.data.data)) {
      return selectedTeamPlayersData.data.data;
    }
    if (selectedTeamPlayersData.data?.players && Array.isArray(selectedTeamPlayersData.data.players)) {
      return selectedTeamPlayersData.data.players;
    }
    return [];
  })();

  // Fetch players for home team (for event display)
  const { data: homeTeamPlayersData } = useQuery({
    queryKey: ['team-squad', match?.home_team_id],
    queryFn: () => teamsService.getSquad(match?.home_team_id, { per_page: 100 }),
    enabled: !!match?.home_team_id && activeTab === 'events' && !showEventForm,
  });

  // Fetch players for away team (for event display)
  const { data: awayTeamPlayersData } = useQuery({
    queryKey: ['team-squad', match?.away_team_id],
    queryFn: () => teamsService.getSquad(match?.away_team_id, { per_page: 100 }),
    enabled: !!match?.away_team_id && activeTab === 'events' && !showEventForm,
  });

  // Get players for team (for displaying events)
  const getPlayersForTeam = (teamId) => {
    if (!teamId) return [];
    const teamIdNum = typeof teamId === 'string' ? parseInt(teamId) : teamId;
    
    if (teamIdNum === match?.home_team_id) {
      const players = homeTeamPlayersData;
      if (Array.isArray(players)) return players;
      if (players?.data && Array.isArray(players.data)) return players.data;
      if (players?.data?.data && Array.isArray(players.data.data)) return players.data.data;
      if (players?.data?.players && Array.isArray(players.data.players)) return players.data.players;
      return [];
    }
    
    if (teamIdNum === match?.away_team_id) {
      const players = awayTeamPlayersData;
      if (Array.isArray(players)) return players;
      if (players?.data && Array.isArray(players.data)) return players.data;
      if (players?.data?.data && Array.isArray(players.data.data)) return players.data.data;
      if (players?.data?.players && Array.isArray(players.data.players)) return players.data.players;
      return [];
    }
    
    return [];
  };

  const createEventMutation = useMutation({
    mutationFn: (data) => matchesService.createEvent(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['match-events', id]);
      queryClient.invalidateQueries(['match', id]);
      refetchEvents();
      toast.success('Event recorded successfully');
      setShowEventForm(false);
      setEditingEventId(null);
      setEventForm({
        team_id: match?.home_team_id || '',
        player_id: '',
        event_type: 'goal',
        minute: match?.current_minute || 0,
        description: '',
      });
      // Invalidate team squad queries to refresh player lists
      queryClient.invalidateQueries(['team-squad']);
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to record event');
    },
  });

  const updateEventMutation = useMutation({
    mutationFn: ({ eventId, data }) => matchesService.updateEvent(eventId, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['match-events', id]);
      queryClient.invalidateQueries(['match', id]);
      refetchEvents();
      toast.success('Event updated successfully');
      setShowEventForm(false);
      setEditingEventId(null);
      setEventForm({
        team_id: match?.home_team_id || '',
        player_id: '',
        event_type: 'goal',
        minute: match?.current_minute || 0,
        description: '',
      });
      queryClient.invalidateQueries(['team-squad']);
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to update event');
    },
  });

  const deleteEventMutation = useMutation({
    mutationFn: (eventId) => matchesService.deleteEvent(eventId),
    onSuccess: () => {
      queryClient.invalidateQueries(['match-events', id]);
      queryClient.invalidateQueries(['match', id]);
      refetchEvents();
      toast.success('Event deleted successfully');
      setDeleteEventId(null);
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete event');
    },
  });

  // Status update mutation
  const statusMutation = useMutation({
    mutationFn: ({ status, currentMinute }) => matchesService.updateStatus(id, status, currentMinute),
    onSuccess: () => {
      queryClient.invalidateQueries(['match', id]);
      queryClient.invalidateQueries(['matches']);
      toast.success('Match status updated successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to update match status');
    },
  });

  // Minute update mutation
  const minuteMutation = useMutation({
    mutationFn: (currentMinute) => matchesService.updateMinute(id, currentMinute),
    onSuccess: (data, variables) => {
      // Update the ref with the new minute value
      matchMinuteRef.current = variables;
      // Clear updating flag after successful update
      isUpdatingRef.current = false;
      queryClient.invalidateQueries(['match', id]);
      queryClient.invalidateQueries(['matches']);
      // Only show toast for manual updates, not auto-updates
      if (!autoUpdateEnabled) {
        toast.success('Match minute updated successfully');
      }
    },
    onError: (error, variables) => {
      // Clear updating flag on error and revert ref to previous value
      isUpdatingRef.current = false;
      // Revert to previous minute (variables is the attempted new minute, so subtract 1)
      matchMinuteRef.current = Math.max(0, variables - 1);
      toast.error(error?.response?.data?.message || 'Failed to update match minute');
    },
  });

  // Store mutation function in ref for use in intervals
  useEffect(() => {
    minuteMutationRef.current = minuteMutation;
  }, [minuteMutation]);

  // Handle status change
  const handleStatusChange = (newStatus) => {
    if (window.confirm(`Are you sure you want to change the match status to "${newStatus}"?`)) {
      statusMutation.mutate({ status: newStatus, currentMinute: null });
    }
  };

  // Handle minute update
  const handleMinuteUpdate = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const minute = parseInt(formData.get('current_minute'));
    
    if (isNaN(minute) || minute < 0 || minute > 120) {
      toast.error('Please enter a valid minute (0-120)');
      return;
    }

    minuteMutation.mutate(minute);
  };

  // Update match minute ref when match data changes
  useEffect(() => {
    matchMinuteRef.current = match?.current_minute ?? 0;
  }, [match?.current_minute]);

  // Countdown timer for auto-update
  useEffect(() => {
    // Clear any existing interval first
    if (countdownIntervalRef.current) {
      clearInterval(countdownIntervalRef.current);
      countdownIntervalRef.current = null;
    }

    if (autoUpdateEnabled && (match?.status === 'in_progress' || match?.status === 'live')) {
      // Reset countdown when auto-update starts
      setCountdown(60);
      
      // Countdown timer (updates every second)
      countdownIntervalRef.current = setInterval(() => {
        setCountdown((prev) => {
          const newCountdown = prev - 1;
          
          if (newCountdown <= 0) {
            // Prevent duplicate updates
            if (isUpdatingRef.current) {
              return 60; // Already updating, just reset countdown
            }

            // When countdown reaches 0, update the minute
            const currentMinute = matchMinuteRef.current ?? 0;
            const newMinute = currentMinute + 1;

            // Stop at 120 minutes
            if (newMinute > 120) {
              setAutoUpdateEnabled(false);
              toast.info('Match minute reached maximum (120 minutes). Auto-update stopped.');
              return 60; // Return 60 to prevent further updates
            }

            // Set updating flag
            isUpdatingRef.current = true;
            
            // Update the ref immediately to prevent duplicate increments
            matchMinuteRef.current = newMinute;

            // Update minute using ref to avoid stale closure
            if (minuteMutationRef.current) {
              minuteMutationRef.current.mutate(newMinute);
            } else {
              isUpdatingRef.current = false;
            }
            
            return 60; // Reset to 60 after update
          }
          
          return newCountdown;
        });
      }, 1000);

      return () => {
        if (countdownIntervalRef.current) {
          clearInterval(countdownIntervalRef.current);
          countdownIntervalRef.current = null;
        }
      };
    } else {
      setCountdown(60);
    }
  }, [autoUpdateEnabled, match?.status]);


  // Stop auto-update when match is completed
  useEffect(() => {
    if (match?.status === 'completed' && autoUpdateEnabled) {
      setAutoUpdateEnabled(false);
      toast.info('Match completed. Auto-update stopped.');
    }
  }, [match?.status, autoUpdateEnabled]);

  // Cleanup intervals on unmount
  useEffect(() => {
    return () => {
      if (autoUpdateIntervalRef.current) {
        clearInterval(autoUpdateIntervalRef.current);
        autoUpdateIntervalRef.current = null;
      }
      if (countdownIntervalRef.current) {
        clearInterval(countdownIntervalRef.current);
        countdownIntervalRef.current = null;
      }
      isUpdatingRef.current = false;
    };
  }, []);

  // Toggle auto-update
  const toggleAutoUpdate = () => {
    if (match?.status !== 'in_progress' && match?.status !== 'live') {
      toast.error('Auto-update is only available for matches in progress');
      return;
    }

    if (match?.current_minute >= 120) {
      toast.error('Match minute is already at maximum (120 minutes)');
      return;
    }

    setAutoUpdateEnabled(!autoUpdateEnabled);
    if (!autoUpdateEnabled) {
      toast.success('Auto-update enabled. Match minute will update every 60 seconds.');
    } else {
      toast.info('Auto-update disabled.');
    }
  };

  // Save match report mutation
  const saveReportMutation = useMutation({
    mutationFn: (data) => matchesService.saveReport(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['match', id]);
      queryClient.invalidateQueries(['matches']);
      toast.success('Match report saved successfully');
      setShowReportForm(false);
      setReportForm({
        summary: '',
        referee: '',
        attendance: '',
        home_score: '',
        away_score: '',
        duration_minutes: 90,
      });
      setReportErrors({});
    },
    onError: (error) => {
      if (error?.response?.data?.errors) {
        setReportErrors(error.response.data.errors);
      } else {
        toast.error(error?.response?.data?.message || 'Failed to save match report');
      }
    },
  });

  // Finalize match mutation - also updates match status to completed
  const finalizeMatchMutation = useMutation({
    mutationFn: async (data) => {
      // First, finalize the match result
      await resultsService.finalizeMatch(id, data);
      // Then, update the match status to completed
      await matchesService.updateStatus(id, 'completed');
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['match', id]);
      queryClient.invalidateQueries(['matches']);
      queryClient.invalidateQueries(['match-events', id]);
      toast.success('Match finalized and status updated to completed');
      setShowFinalizeModal(false);
      setFinalizeForm({
        home_score: '',
        away_score: '',
        completed_at: new Date().toISOString().slice(0, 16),
      });
      setFinalizeErrors({});
    },
    onError: (error) => {
      if (error?.response?.data?.errors) {
        setFinalizeErrors(error.response.data.errors);
      } else {
        toast.error(error?.response?.data?.message || 'Failed to finalize match');
      }
    },
  });

  // Initialize finalize form with current match data when modal opens
  useEffect(() => {
    if (showFinalizeModal && match) {
      setFinalizeForm({
        home_score: match?.home_score?.toString() || '',
        away_score: match?.away_score?.toString() || '',
        completed_at: match?.completed_at 
          ? new Date(match.completed_at).toISOString().slice(0, 16)
          : new Date().toISOString().slice(0, 16),
      });
      setFinalizeErrors({});
    }
  }, [showFinalizeModal, match]);

  // Handle finalize form change
  const handleFinalizeChange = (e) => {
    const { name, value } = e.target;
    setFinalizeForm((prev) => ({
      ...prev,
      [name]: value,
    }));
    // Clear error for this field when user starts typing
    if (finalizeErrors[name]) {
      setFinalizeErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  // Handle finalize form submit
  const handleFinalizeSubmit = (e) => {
    e.preventDefault();
    setFinalizeErrors({});

    const submitData = {
      home_score: parseInt(finalizeForm.home_score),
      away_score: parseInt(finalizeForm.away_score),
      completed_at: new Date(finalizeForm.completed_at).toISOString(),
    };

    // Validate scores
    if (isNaN(submitData.home_score) || submitData.home_score < 0) {
      setFinalizeErrors({ home_score: 'Home score must be a valid number' });
      return;
    }

    if (isNaN(submitData.away_score) || submitData.away_score < 0) {
      setFinalizeErrors({ away_score: 'Away score must be a valid number' });
      return;
    }

    // Validate completed_at
    if (!finalizeForm.completed_at || isNaN(new Date(finalizeForm.completed_at).getTime())) {
      setFinalizeErrors({ completed_at: 'Completed date must be valid' });
      return;
    }

    finalizeMatchMutation.mutate(submitData);
  };

  // Handle report form change
  const handleReportChange = (e) => {
    const { name, value } = e.target;
    setReportForm((prev) => ({ ...prev, [name]: value }));
    // Clear error for the field being changed
    if (reportErrors[name]) {
      setReportErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  // Handle report form submit
  const handleReportSubmit = (e) => {
    e.preventDefault();
    setReportErrors({}); // Clear previous errors

    const submitData = {
      summary: reportForm.summary.trim(),
      referee: reportForm.referee.trim(),
      attendance: parseInt(reportForm.attendance),
      home_score: parseInt(reportForm.home_score),
      away_score: parseInt(reportForm.away_score),
      duration_minutes: parseInt(reportForm.duration_minutes) || 90,
    };

    // Basic validation
    if (!submitData.summary) {
      setReportErrors({ summary: 'Summary is required' });
      return;
    }

    if (!submitData.referee) {
      setReportErrors({ referee: 'Referee name is required' });
      return;
    }

    if (isNaN(submitData.attendance) || submitData.attendance < 0) {
      setReportErrors({ attendance: 'Attendance must be a valid non-negative number' });
      return;
    }

    if (isNaN(submitData.home_score) || submitData.home_score < 0) {
      setReportErrors({ home_score: 'Home score must be a valid non-negative number' });
      return;
    }

    if (isNaN(submitData.away_score) || submitData.away_score < 0) {
      setReportErrors({ away_score: 'Away score must be a valid non-negative number' });
      return;
    }

    saveReportMutation.mutate(submitData);
  };

  // Initialize report form with existing report data when editing
  useEffect(() => {
    if (showReportForm && match?.match_report && typeof match.match_report === 'object') {
      setReportForm({
        summary: match.match_report.summary || '',
        referee: match.match_report.referee || '',
        attendance: match.match_report.attendance?.toString() || '',
        home_score: match?.home_score?.toString() || '',
        away_score: match?.away_score?.toString() || '',
        duration_minutes: match.match_report.duration_minutes || 90,
      });
      setReportErrors({});
    } else if (showReportForm && match) {
      // Pre-fill with match scores if available
      setReportForm((prev) => ({
        ...prev,
        home_score: match?.home_score?.toString() || '',
        away_score: match?.away_score?.toString() || '',
      }));
    }
  }, [showReportForm, match]);

  // Handle edit event
  const handleEditEvent = (event) => {
    setEditingEventId(event.id);
    setEventForm({
      team_id: event.team_id,
      player_id: event.player_id,
      event_type: event.event_type,
      minute: event.minute,
      description: event.description || '',
    });
    setShowEventForm(true);
  };

  // Handle cancel edit
  const handleCancelEdit = () => {
    setEditingEventId(null);
    setShowEventForm(false);
    setEventForm({
      team_id: match?.home_team_id || '',
      player_id: '',
      event_type: 'goal',
      minute: match?.current_minute || 0,
      description: '',
    });
  };

  const handleEventSubmit = (e) => {
    e.preventDefault();
    if (!eventForm.team_id || !eventForm.player_id || !eventForm.minute) {
      toast.error('Please fill in all required fields');
      return;
    }
    
    if (editingEventId) {
      // Update existing event
      updateEventMutation.mutate({ eventId: editingEventId, data: eventForm });
    } else {
      // Create new event
      createEventMutation.mutate(eventForm);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !match) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Match not found'}
      </div>
    );
  }

  const isFinalized = match.status === 'completed';

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/matches/my-matches')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to My Matches
        </button>
        <div>
          <div className="flex items-center justify-between mb-2">
            <h1 className="text-3xl font-bold text-gray-900">Match Details</h1>
            {isFinalized && (
              <div className="flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                <CheckCircle className="w-5 h-5" />
                <span className="font-semibold">Finalized Match</span>
              </div>
            )}
          </div>
          <div className="flex items-center space-x-4 text-gray-600">
            {match.tournament && (
              <span className="flex items-center">
                <Trophy className="w-4 h-4 mr-1" />
                {match.tournament.name}
              </span>
            )}
            {match.match_date && (
              <span className="flex items-center">
                <Calendar className="w-4 h-4 mr-1" />
                {new Date(match.match_date).toLocaleString()}
              </span>
            )}
          </div>
        </div>
      </div>

      {/* Match Score Card - Highlighted for finalized matches */}
      <div className={`card mb-6 ${isFinalized ? 'border-2 border-green-500 bg-green-50' : ''}`}>
        <div className="flex items-center justify-between mb-4">
          <div className="flex-1 text-center">
            <h3 className="text-xl font-bold text-gray-900 mb-2">
              {match.home_team?.name || match.home_team_name || 'Home Team'}
            </h3>
            <div className="text-4xl font-bold text-primary-600">
              {match.home_score !== null ? match.home_score : '-'}
            </div>
          </div>
          <div className="px-8">
            <div className="text-2xl font-bold text-gray-400">VS</div>
          </div>
          <div className="flex-1 text-center">
            <h3 className="text-xl font-bold text-gray-900 mb-2">
              {match.away_team?.name || match.away_team_name || 'Away Team'}
            </h3>
            <div className="text-4xl font-bold text-primary-600">
              {match.away_score !== null ? match.away_score : '-'}
            </div>
          </div>
        </div>
        <div className="text-center">
          <span
            className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${
              match.status === 'in_progress'
                ? 'bg-green-100 text-green-800'
                : match.status === 'completed'
                ? 'bg-green-100 text-green-800 font-bold'
                : match.status === 'cancelled'
                ? 'bg-red-100 text-red-800'
                : 'bg-blue-100 text-blue-800'
            }`}
          >
            {match.status || 'scheduled'}
          </span>
          {match.current_minute !== null && (
            <span className="ml-3 text-gray-600">
              <Clock className="w-4 h-4 inline mr-1" />
              {match.current_minute}' minute
            </span>
          )}
        </div>
        {isFinalized && (
          <div className="mt-4 text-center">
            <p className="text-sm text-gray-600">
              Final Score: <span className="font-bold text-gray-900">{match.home_score} - {match.away_score}</span>
            </p>
          </div>
        )}
      </div>

      {/* Tabs */}
      <div className="mb-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            {[
              { id: 'overview', label: 'Overview' },
              { id: 'events', label: 'Events' },
              { id: 'report', label: 'Report' },
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          {/* Finalized Match Banner */}
          {isFinalized && (
            <div className="card bg-green-50 border-2 border-green-500">
              <div className="flex items-center gap-3">
                <CheckCircle className="w-6 h-6 text-green-600" />
                <div>
                  <h3 className="font-bold text-green-900">Match Finalized</h3>
                  <p className="text-sm text-green-700">
                    This match has been completed. Final score: {match.home_score} - {match.away_score}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Match Information */}
          <div className="card">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Match Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p className="text-sm text-gray-500 mb-1">ID</p>
                <p className="font-medium text-gray-900">{match.id}</p>
              </div>
              {match.tournament && (
                <div>
                  <p className="text-sm text-gray-500 mb-1">Tournament</p>
                  <p className="font-medium text-gray-900">{match.tournament.name}</p>
                </div>
              )}
              {match.venue && (
                <div>
                  <p className="text-sm text-gray-500 mb-1 flex items-center">
                    <MapPin className="w-4 h-4 mr-1" />
                    Venue
                  </p>
                  <p className="font-medium text-gray-900">
                    {match.venue.name}
                    {match.venue.location && ` - ${match.venue.location}`}
                  </p>
                </div>
              )}
              {match.match_date && (
                <div>
                  <p className="text-sm text-gray-500 mb-1 flex items-center">
                    <Calendar className="w-4 h-4 mr-1" />
                    Date & Time
                  </p>
                  <p className="font-medium text-gray-900">
                    {new Date(match.match_date).toLocaleString()}
                  </p>
                </div>
              )}
              <div>
                <p className="text-sm text-gray-500 mb-1">Round Number</p>
                <p className="font-medium text-gray-900">{match.round_number || 'N/A'}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500 mb-1">Referee ID</p>
                <p className="font-medium text-gray-900">{match.referee_id || 'N/A'}</p>
              </div>
              {isFinalized && (
                <>
                  <div>
                    <p className="text-sm text-gray-500 mb-1">Final Score</p>
                    <p className="font-bold text-lg text-gray-900">
                      {match.home_score} - {match.away_score}
                    </p>
                  </div>
                  {match.current_minute !== null && (
                    <div>
                      <p className="text-sm text-gray-500 mb-1">Match Duration</p>
                      <p className="font-medium text-gray-900">{match.current_minute} minutes</p>
                    </div>
                  )}
                </>
              )}
            </div>
          </div>

          {/* Finalize Match Button */}
          {canRecordEvents && !isFinalized && (
            <div className="card">
              <div className="flex justify-between items-center">
                <div>
                  <h2 className="text-xl font-semibold text-gray-900 mb-1">Finalize Match</h2>
                  <p className="text-sm text-gray-600">Set final scores and complete the match</p>
                </div>
                <button
                  onClick={() => setShowFinalizeModal(true)}
                  className="btn btn-primary flex items-center"
                >
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Finalize Match
                </button>
              </div>
            </div>
          )}

          {/* Match Minute Update */}
          {canRecordEvents && (match.status === 'in_progress' || match.status === 'live') && (
            <div className="card">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Update Match Minute</h2>
              
              {/* Auto-Update Toggle */}
              <div className="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="text-lg font-semibold text-gray-900">Auto-Update Minute</h3>
                      {autoUpdateEnabled && (
                        <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium flex items-center gap-1">
                          <span className="h-2 w-2 bg-green-600 rounded-full animate-pulse"></span>
                          Active
                        </span>
                      )}
                    </div>
                    <p className="text-sm text-gray-600">
                      Automatically increment match minute every 60 seconds. 
                      {autoUpdateEnabled && (
                        <span className="font-medium text-green-700 ml-1">
                          Next update in {countdown} seconds
                        </span>
                      )}
                    </p>
                    {autoUpdateEnabled && match.current_minute !== null && (
                      <p className="text-xs text-gray-500 mt-1">
                        Current: {match.current_minute}' | Will stop at 120'
                      </p>
                    )}
                  </div>
                  <button
                    type="button"
                    onClick={toggleAutoUpdate}
                    disabled={match.current_minute >= 120}
                    className={`btn ${autoUpdateEnabled ? 'btn-secondary' : 'btn-primary'}`}
                  >
                    {autoUpdateEnabled ? (
                      <>
                        <X className="w-4 h-4 mr-2" />
                        Stop Auto-Update
                      </>
                    ) : (
                      <>
                        <Clock className="w-4 h-4 mr-2" />
                        Start Auto-Update
                      </>
                    )}
                  </button>
                </div>
              </div>

              {/* Manual Update Form */}
              <div className="border-t border-gray-200 pt-4">
                <h3 className="text-md font-semibold text-gray-900 mb-3">Manual Update</h3>
                <form onSubmit={handleMinuteUpdate} className="flex items-end gap-4">
                  <div className="flex-1">
                    <label htmlFor="current_minute" className="block text-sm font-medium text-gray-700 mb-2">
                      Current Minute
                    </label>
                    <div className="flex items-center gap-2">
                      <input
                        id="current_minute"
                        name="current_minute"
                        type="number"
                        min="0"
                        max="120"
                        defaultValue={match.current_minute || 0}
                        className="input w-32"
                        placeholder="0"
                        required
                        disabled={autoUpdateEnabled}
                      />
                      <span className="text-gray-600">minutes</span>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">
                      {autoUpdateEnabled 
                        ? 'Disable auto-update to manually set the minute'
                        : 'Enter the current minute of the match (0-120)'}
                    </p>
                  </div>
                  <button
                    type="submit"
                    disabled={minuteMutation.isLoading || autoUpdateEnabled}
                    className="btn btn-primary"
                  >
                    {minuteMutation.isLoading ? 'Updating...' : 'Update Minute'}
                  </button>
                </form>
              </div>
            </div>
          )}

          {/* Status Management */}
          {canRecordEvents && (
            <div className="card">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Status Management</h2>
              <div className="flex flex-wrap gap-2">
                {STATUS_OPTIONS.map((status) => (
                  <button
                    key={status.value}
                    onClick={() => handleStatusChange(status.value)}
                    disabled={statusMutation.isLoading || match.status === status.value}
                    className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                      match.status === status.value
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    } disabled:opacity-50 disabled:cursor-not-allowed`}
                  >
                    {status.label}
                  </button>
                ))}
              </div>
              {statusMutation.isLoading && (
                <p className="mt-2 text-sm text-gray-500">Updating status...</p>
              )}
            </div>
          )}
        </div>
      )}

      {activeTab === 'events' && (
        <div className="space-y-6">
          {/* Add Event Button */}
          {canRecordEvents && !isFinalized && (
            <div className="card">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold text-gray-900">Match Events</h2>
                <button
                  onClick={() => {
                    if (showEventForm) {
                      handleCancelEdit();
                    } else {
                      setShowEventForm(true);
                    }
                  }}
                  className="btn btn-primary flex items-center"
                >
                  <Plus className="w-4 h-4 mr-2" />
                  {showEventForm ? 'Cancel' : 'Add Event'}
                </button>
              </div>
            </div>
          )}

          {/* Event Form */}
          {showEventForm && canRecordEvents && !isFinalized && (
            <div className="card">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                {editingEventId ? 'Edit Event' : 'Record New Event'}
              </h3>
              <form onSubmit={handleEventSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Team <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={eventForm.team_id}
                      onChange={(e) => {
                        // Reset player selection when team changes
                        setEventForm({ ...eventForm, team_id: e.target.value, player_id: '' });
                      }}
                      className="input"
                      required
                    >
                      <option value="">Select Team</option>
                      {match.home_team_id && (
                        <option value={match.home_team_id}>
                          {match.home_team?.name || 'Home Team'}
                        </option>
                      )}
                      {match.away_team_id && (
                        <option value={match.away_team_id}>
                          {match.away_team?.name || 'Away Team'}
                        </option>
                      )}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Player <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={eventForm.player_id}
                      onChange={(e) => setEventForm({ ...eventForm, player_id: e.target.value })}
                      className="input"
                      required
                      disabled={!eventForm.team_id || loadingPlayers}
                    >
                      <option value="">
                        {loadingPlayers 
                          ? 'Loading players...' 
                          : !eventForm.team_id 
                          ? 'Select a team first' 
                          : 'Select Player'}
                      </option>
                      {selectedTeamPlayers.map((player) => (
                        <option key={player.id} value={player.id}>
                          {player.full_name || player.name || `Player ${player.id}`}
                          {player.jersey_number && ` (#${player.jersey_number})`}
                          {player.position && ` - ${player.position}`}
                        </option>
                      ))}
                    </select>
                    {eventForm.team_id && !loadingPlayers && selectedTeamPlayers.length === 0 && (
                      <p className="text-sm text-gray-500 mt-1">No players found for this team</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Event Type <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={eventForm.event_type}
                      onChange={(e) => setEventForm({ ...eventForm, event_type: e.target.value })}
                      className="input"
                      required
                    >
                      <option value="goal">Goal</option>
                      <option value="yellow_card">Yellow Card</option>
                      <option value="red_card">Red Card</option>
                      <option value="substitution">Substitution</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Minute <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="120"
                      value={eventForm.minute}
                      onChange={(e) => setEventForm({ ...eventForm, minute: parseInt(e.target.value) || 0 })}
                      className="input"
                      required
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Description (Optional)
                    </label>
                    <textarea
                      value={eventForm.description}
                      onChange={(e) => setEventForm({ ...eventForm, description: e.target.value })}
                      className="input"
                      rows="3"
                      placeholder="Add event description..."
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-2">
                  <button
                    type="button"
                    onClick={handleCancelEdit}
                    className="btn btn-secondary"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={editingEventId ? updateEventMutation.isLoading : createEventMutation.isLoading}
                    className="btn btn-primary"
                  >
                    {editingEventId 
                      ? (updateEventMutation.isLoading ? 'Updating...' : 'Update Event')
                      : (createEventMutation.isLoading ? 'Recording...' : 'Record Event')
                    }
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* Events List */}
          <div className="card">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Match Events</h2>
            {events.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No events recorded for this match</p>
            ) : (
              <div className="space-y-4">
                {events.map((event) => {
                  const eventTeam = event.team_id === match?.home_team_id 
                    ? match.home_team 
                    : match?.away_team;
                  const eventPlayers = getPlayersForTeam(event.team_id);
                  const eventPlayer = eventPlayers.find(p => p.id === event.player_id);
                  const EventIcon = EVENT_TYPE_ICONS[event.event_type] || AlertCircle;
                  
                  return (
                    <div
                      key={event.id}
                      className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                      <div className="flex items-center space-x-4 flex-1">
                        <div className="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center font-bold text-primary-600 flex-shrink-0">
                          {event.minute}'
                        </div>
                        <div className="flex items-center space-x-3 flex-1">
                          <EventIcon className={`w-5 h-5 ${
                            event.event_type === 'goal' ? 'text-green-600' :
                            event.event_type === 'red_card' ? 'text-red-600' :
                            event.event_type === 'yellow_card' ? 'text-yellow-600' :
                            'text-blue-600'
                          }`} />
                          <div className="flex-1">
                            <div className="flex items-center space-x-2 mb-1">
                              <p className="font-medium text-gray-900">
                                {EVENT_TYPE_LABELS[event.event_type] || event.event_type}
                              </p>
                              <span className="px-2 py-1 bg-primary-100 text-primary-800 rounded text-xs font-medium">
                                {eventTeam?.name || `Team ${event.team_id}`}
                              </span>
                            </div>
                            {eventPlayer && (
                              <p className="text-sm text-gray-700 font-medium">
                                {eventPlayer.full_name || eventPlayer.name || `Player ${event.player_id}`}
                                {eventPlayer.jersey_number && ` (#${eventPlayer.jersey_number})`}
                              </p>
                            )}
                            {event.description && (
                              <p className="text-sm text-gray-600 mt-1">{event.description}</p>
                            )}
                          </div>
                        </div>
                      </div>
                      {canRecordEvents && !isFinalized && (
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => handleEditEvent(event)}
                            className="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                            title="Edit event"
                          >
                            <Pencil className="w-5 h-5" />
                          </button>
                          <button
                            onClick={() => setDeleteEventId(event.id)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="Delete event"
                          >
                            <X className="w-5 h-5" />
                          </button>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {activeTab === 'report' && (
        <div className="space-y-6">
          {/* Report Header */}
          <div className="card">
            <div className="flex justify-between items-center">
              <h2 className="text-xl font-semibold text-gray-900">Match Report</h2>
              {canRecordEvents && (
                <button
                  onClick={() => setShowReportForm(!showReportForm)}
                  className="btn btn-primary flex items-center"
                >
                  {showReportForm ? (
                    <>
                      <X className="w-4 h-4 mr-2" />
                      Cancel
                    </>
                  ) : (
                    <>
                      <Plus className="w-4 h-4 mr-2" />
                      {match.match_report ? 'Edit Report' : 'Create Report'}
                    </>
                  )}
                </button>
              )}
            </div>
          </div>

          {/* Report Form */}
          {showReportForm && canRecordEvents && (
            <div className="card">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                {match.match_report ? 'Edit Match Report' : 'Create Match Report'}
              </h3>
              <form onSubmit={handleReportSubmit} className="space-y-6">
                {/* Match Info */}
                {match.home_team && match.away_team && (
                  <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-600 mb-1">Match</p>
                    <p className="font-semibold text-gray-900">
                      {match.home_team.name || match.home_team_name} vs{' '}
                      {match.away_team.name || match.away_team_name}
                    </p>
                  </div>
                )}

                {/* Summary */}
                <div>
                  <label htmlFor="summary" className="block text-sm font-medium text-gray-700 mb-1">
                    Summary <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    id="summary"
                    name="summary"
                    value={reportForm.summary}
                    onChange={handleReportChange}
                    rows="6"
                    className={`input ${reportErrors.summary ? 'border-red-500' : ''}`}
                    placeholder="Enter match summary..."
                    required
                  />
                  {reportErrors.summary && (
                    <p className="mt-1 text-sm text-red-600">
                      {Array.isArray(reportErrors.summary) ? reportErrors.summary[0] : reportErrors.summary}
                    </p>
                  )}
                </div>

                {/* Referee and Attendance */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="referee" className="block text-sm font-medium text-gray-700 mb-1">
                      Referee <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="referee"
                      name="referee"
                      type="text"
                      value={reportForm.referee}
                      onChange={handleReportChange}
                      className={`input ${reportErrors.referee ? 'border-red-500' : ''}`}
                      placeholder="Referee name"
                      required
                    />
                    {reportErrors.referee && (
                      <p className="mt-1 text-sm text-red-600">
                        {Array.isArray(reportErrors.referee) ? reportErrors.referee[0] : reportErrors.referee}
                      </p>
                    )}
                  </div>

                  <div>
                    <label htmlFor="attendance" className="block text-sm font-medium text-gray-700 mb-1">
                      Attendance <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="attendance"
                      name="attendance"
                      type="number"
                      min="0"
                      value={reportForm.attendance}
                      onChange={handleReportChange}
                      className={`input ${reportErrors.attendance ? 'border-red-500' : ''}`}
                      placeholder="Number of attendees"
                      required
                    />
                    {reportErrors.attendance && (
                      <p className="mt-1 text-sm text-red-600">
                        {Array.isArray(reportErrors.attendance) ? reportErrors.attendance[0] : reportErrors.attendance}
                      </p>
                    )}
                  </div>
                </div>

                {/* Scores */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="home_score" className="block text-sm font-medium text-gray-700 mb-1">
                      Home Team Score <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="home_score"
                      name="home_score"
                      type="number"
                      min="0"
                      value={reportForm.home_score}
                      onChange={handleReportChange}
                      className={`input ${reportErrors.home_score ? 'border-red-500' : ''}`}
                      required
                    />
                    {reportErrors.home_score && (
                      <p className="mt-1 text-sm text-red-600">
                        {Array.isArray(reportErrors.home_score) ? reportErrors.home_score[0] : reportErrors.home_score}
                      </p>
                    )}
                  </div>

                  <div>
                    <label htmlFor="away_score" className="block text-sm font-medium text-gray-700 mb-1">
                      Away Team Score <span className="text-red-500">*</span>
                    </label>
                    <input
                      id="away_score"
                      name="away_score"
                      type="number"
                      min="0"
                      value={reportForm.away_score}
                      onChange={handleReportChange}
                      className={`input ${reportErrors.away_score ? 'border-red-500' : ''}`}
                      required
                    />
                    {reportErrors.away_score && (
                      <p className="mt-1 text-sm text-red-600">
                        {Array.isArray(reportErrors.away_score) ? reportErrors.away_score[0] : reportErrors.away_score}
                      </p>
                    )}
                  </div>
                </div>

                {/* Duration */}
                <div>
                  <label htmlFor="duration_minutes" className="block text-sm font-medium text-gray-700 mb-1">
                    Duration (minutes)
                  </label>
                  <input
                    id="duration_minutes"
                    name="duration_minutes"
                    type="number"
                    min="1"
                    max="180"
                    value={reportForm.duration_minutes}
                    onChange={handleReportChange}
                    className="input"
                  />
                </div>

                {/* Submit Button */}
                <div className="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    onClick={() => {
                      setShowReportForm(false);
                      setReportErrors({});
                    }}
                    className="btn btn-secondary"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={saveReportMutation.isLoading}
                    className="btn btn-primary"
                  >
                    {saveReportMutation.isLoading ? 'Saving...' : 'Save Report'}
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* Display Existing Report */}
          {!showReportForm && match.match_report && (
            <div className="card">
              <div className="space-y-6">
                {typeof match.match_report === 'string' ? (
                  <div className="prose max-w-none">
                    <p className="text-gray-700 whitespace-pre-wrap">{match.match_report}</p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {match.match_report.summary && (
                      <div className="md:col-span-2">
                        <p className="text-sm text-gray-500 mb-2">Summary</p>
                        <p className="text-gray-900 whitespace-pre-wrap">{match.match_report.summary}</p>
                      </div>
                    )}
                    {match.match_report.referee && (
                      <div>
                        <p className="text-sm text-gray-500 mb-1">Referee</p>
                        <p className="font-medium text-gray-900">{match.match_report.referee}</p>
                      </div>
                    )}
                    {match.match_report.attendance && (
                      <div>
                        <p className="text-sm text-gray-500 mb-1">Attendance</p>
                        <p className="font-medium text-gray-900">{match.match_report.attendance}</p>
                      </div>
                    )}
                    {match.match_report.weather && (
                      <div>
                        <p className="text-sm text-gray-500 mb-1">Weather</p>
                        <p className="font-medium text-gray-900">{match.match_report.weather}</p>
                      </div>
                    )}
                    {match.match_report.created_at && (
                      <div>
                        <p className="text-sm text-gray-500 mb-1">Created At</p>
                        <p className="text-gray-900">
                          {new Date(match.match_report.created_at).toLocaleString()}
                        </p>
                      </div>
                    )}
                    {match.match_report.updated_at && (
                      <div>
                        <p className="text-sm text-gray-500 mb-1">Updated At</p>
                        <p className="text-gray-900">
                          {new Date(match.match_report.updated_at).toLocaleString()}
                        </p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* No Report Message */}
          {!showReportForm && !match.match_report && (
            <div className="card">
              <p className="text-gray-500 text-center py-8">No match report available</p>
            </div>
          )}
        </div>
      )}

      {/* Delete Event Confirmation */}
      <ConfirmDialog
        isOpen={!!deleteEventId}
        title="Delete Event"
        message="Are you sure you want to delete this event? This action cannot be undone."
        onConfirm={() => {
          deleteEventMutation.mutate(deleteEventId);
        }}
        onClose={() => setDeleteEventId(null)}
        confirmText="Delete"
        cancelText="Cancel"
        isLoading={deleteEventMutation.isLoading}
      />

      {/* Finalize Match Modal */}
      <Modal
        isOpen={showFinalizeModal}
        onClose={() => {
          setShowFinalizeModal(false);
          setFinalizeErrors({});
        }}
        title="Finalize Match Result"
        size="lg"
      >
        <form onSubmit={handleFinalizeSubmit} className="space-y-6">
          {/* Match Info */}
          {match.home_team && match.away_team && (
            <div className="mb-4 p-3 bg-gray-50 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Match</p>
              <p className="font-semibold text-gray-900">
                {match.home_team.name || match.home_team_name} vs{' '}
                {match.away_team.name || match.away_team_name}
              </p>
            </div>
          )}

          {/* Scores */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="home_score" className="block text-sm font-medium text-gray-700 mb-1">
                Home Team Score <span className="text-red-500">*</span>
              </label>
              <input
                id="home_score"
                name="home_score"
                type="number"
                min="0"
                value={finalizeForm.home_score}
                onChange={handleFinalizeChange}
                className={`input ${finalizeErrors.home_score ? 'border-red-500' : ''}`}
                required
              />
              {finalizeErrors.home_score && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(finalizeErrors.home_score) ? finalizeErrors.home_score[0] : finalizeErrors.home_score}
                </p>
              )}
            </div>

            <div>
              <label htmlFor="away_score" className="block text-sm font-medium text-gray-700 mb-1">
                Away Team Score <span className="text-red-500">*</span>
              </label>
              <input
                id="away_score"
                name="away_score"
                type="number"
                min="0"
                value={finalizeForm.away_score}
                onChange={handleFinalizeChange}
                className={`input ${finalizeErrors.away_score ? 'border-red-500' : ''}`}
                required
              />
              {finalizeErrors.away_score && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(finalizeErrors.away_score) ? finalizeErrors.away_score[0] : finalizeErrors.away_score}
                </p>
              )}
            </div>
          </div>

          {/* Completed At */}
          <div>
            <label htmlFor="completed_at" className="block text-sm font-medium text-gray-700 mb-1">
              Completed At <span className="text-red-500">*</span>
            </label>
            <input
              id="completed_at"
              name="completed_at"
              type="datetime-local"
              value={finalizeForm.completed_at}
              onChange={handleFinalizeChange}
              className={`input ${finalizeErrors.completed_at ? 'border-red-500' : ''}`}
              required
            />
            {finalizeErrors.completed_at && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(finalizeErrors.completed_at) ? finalizeErrors.completed_at[0] : finalizeErrors.completed_at}
              </p>
            )}
          </div>

         
          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={() => {
                setShowFinalizeModal(false);
                setFinalizeErrors({});
              }}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={finalizeMatchMutation.isLoading}
              className="btn btn-primary"
            >
              {finalizeMatchMutation.isLoading ? 'Finalizing...' : 'Finalize Match'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
