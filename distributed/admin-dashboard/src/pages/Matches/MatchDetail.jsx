import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { matchesService } from '../../api/matches';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Edit, Trash2, Calendar, MapPin, Users, Clock, Trophy, CheckCircle } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

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

export default function MatchDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  const { data: match, isLoading, error } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchesService.get(id),
  });

  const { data: eventsData } = useQuery({
    queryKey: ['match-events', id],
    queryFn: () => matchesService.getEvents(id),
    enabled: !!match,
  });

  const events = Array.isArray(eventsData) ? eventsData : eventsData?.data || [];

  const statusMutation = useMutation({
    mutationFn: (status) => matchesService.updateStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries(['match', id]);
      queryClient.invalidateQueries(['matches']);
      toast.success('Match status updated successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to update match status');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: () => matchesService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['matches']);
      toast.success('Match deleted successfully');
      navigate('/matches');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete match');
    },
  });

  const handleStatusChange = (newStatus) => {
    if (window.confirm(`Are you sure you want to change the match status to "${newStatus}"?`)) {
      statusMutation.mutate(newStatus);
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

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/matches')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Matches
        </button>
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Match Details</h1>
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
          <div className="flex items-center space-x-2">
            {match.status !== 'completed' && (
              <button
                onClick={() => navigate(`/matches/${id}/finalize`)}
                className="btn btn-primary flex items-center"
              >
                <CheckCircle className="w-4 h-4 mr-2" />
                Finalize Match
              </button>
            )}
            <button
              onClick={() => navigate(`/matches/${id}/edit`)}
              className="btn btn-secondary flex items-center"
            >
              <Edit className="w-4 h-4 mr-2" />
              Edit
            </button>
            <button
              onClick={() => setDeleteConfirm(true)}
              className="btn btn-danger flex items-center"
            >
              <Trash2 className="w-4 h-4 mr-2" />
              Delete
            </button>
          </div>
        </div>
      </div>

      {/* Match Score Card */}
      <div className="card mb-6">
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
                ? 'bg-gray-100 text-gray-800'
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
              {match.created_at && (
                <div>
                  <p className="text-sm text-gray-500 mb-1">Created At</p>
                  <p className="text-gray-900">
                    {new Date(match.created_at).toLocaleString()}
                  </p>
                </div>
              )}
              {match.updated_at && (
                <div>
                  <p className="text-sm text-gray-500 mb-1">Updated At</p>
                  <p className="text-gray-900">
                    {new Date(match.updated_at).toLocaleString()}
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Status Management */}
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
          </div>
        </div>
      )}

      {activeTab === 'events' && (
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Match Events</h2>
          {events.length === 0 ? (
            <p className="text-gray-500 text-center py-8">No events recorded for this match</p>
          ) : (
            <div className="space-y-4">
              {events.map((event) => (
                <div
                  key={event.id}
                  className="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                >
                  <div className="flex items-center space-x-4">
                    <div className="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center font-bold text-primary-600">
                      {event.minute}'
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">
                        {EVENT_TYPE_LABELS[event.event_type] || event.event_type}
                      </p>
                      {event.description && (
                        <p className="text-sm text-gray-600">{event.description}</p>
                      )}
                      <p className="text-xs text-gray-500">
                        Player ID: {event.player_id} | Team ID: {event.team_id}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {activeTab === 'report' && (
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Match Report</h2>
          {match.match_report ? (
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
          ) : (
            <p className="text-gray-500 text-center py-8">No match report available</p>
          )}
        </div>
      )}

      <ConfirmDialog
        isOpen={deleteConfirm}
        title="Delete Match"
        message="Are you sure you want to delete this match? This action cannot be undone."
        onConfirm={() => {
          deleteMutation.mutate();
          setDeleteConfirm(false);
        }}
        onCancel={() => setDeleteConfirm(false)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
