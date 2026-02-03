import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { tournamentsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Edit, Trash2, Calendar, MapPin, Trophy, BarChart3, Users, Activity } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const TABS = [
  { id: 'overview', label: 'Overview', icon: Trophy },
  { id: 'matches', label: 'Matches', icon: Calendar },
  { id: 'teams', label: 'Teams', icon: Users },
  { id: 'standings', label: 'Standings', icon: BarChart3 },
  { id: 'statistics', label: 'Statistics', icon: Activity },
];

const STATUS_OPTIONS = [
  { value: 'planned', label: 'Planned' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export default function TournamentDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('overview');
  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [statusUpdate, setStatusUpdate] = useState(null);

  const { data: tournament, isLoading, error } = useQuery({
    queryKey: ['tournament', id],
    queryFn: () => tournamentsService.get(id),
  });

  const { data: matchesData, isLoading: loadingMatches } = useQuery({
    queryKey: ['tournament', id, 'matches'],
    queryFn: () => tournamentsService.getMatches(id, { per_page: 20 }),
    enabled: activeTab === 'matches',
  });

  const { data: teamsData, isLoading: loadingTeams } = useQuery({
    queryKey: ['tournament', id, 'teams'],
    queryFn: () => tournamentsService.getTeams(id, { per_page: 20 }),
    enabled: activeTab === 'teams',
  });

  const { data: standingsData, isLoading: loadingStandings } = useQuery({
    queryKey: ['tournament', id, 'standings'],
    queryFn: () => tournamentsService.getStandings(id),
    enabled: activeTab === 'standings',
  });

  const { data: statisticsData, isLoading: loadingStatistics } = useQuery({
    queryKey: ['tournament', id, 'statistics'],
    queryFn: () => tournamentsService.getStatistics(id),
    enabled: activeTab === 'statistics',
  });

  const deleteMutation = useMutation({
    mutationFn: () => tournamentsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['tournaments']);
      toast.success('Tournament deleted successfully');
      navigate('/tournaments');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete tournament');
    },
  });

  const statusMutation = useMutation({
    mutationFn: (status) => tournamentsService.updateStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries(['tournament', id]);
      toast.success('Tournament status updated successfully');
      setStatusUpdate(null);
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to update status');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !tournament) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Tournament not found'}
      </div>
    );
  }

  // Handle matches data structure - API returns { data: { matches: [...] } } or { matches: [...] }
  const matches = matchesData?.data?.matches || matchesData?.matches || matchesData?.data || matchesData || [];
  const teams = teamsData?.data || teamsData || [];
  const standings = standingsData || [];
  // Handle statistics data structure - API returns { data: { statistics: {...} } }
  const statistics = statisticsData?.data?.statistics || statisticsData?.statistics || statisticsData?.data || statisticsData || {};

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/tournaments')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Tournaments
        </button>
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">{tournament.name}</h1>
            <div className="flex items-center space-x-4 text-sm text-gray-600">
              <span className="flex items-center">
                <Trophy className="w-4 h-4 mr-1" />
                {tournament.sport?.name || 'N/A'}
              </span>
              {tournament.location && (
                <span className="flex items-center">
                  <MapPin className="w-4 h-4 mr-1" />
                  {tournament.location}
                </span>
              )}
              <span className="flex items-center">
                <Calendar className="w-4 h-4 mr-1" />
                {tournament.start_date
                  ? new Date(tournament.start_date).toLocaleDateString()
                  : 'N/A'}{' '}
                -{' '}
                {tournament.end_date
                  ? new Date(tournament.end_date).toLocaleDateString()
                  : 'N/A'}
              </span>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <select
              value={tournament.status || 'planned'}
              onChange={(e) => setStatusUpdate(e.target.value)}
              className="input text-sm"
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            {statusUpdate && statusUpdate !== tournament.status && (
              <button
                onClick={() => statusMutation.mutate(statusUpdate)}
                disabled={statusMutation.isLoading}
                className="btn btn-primary text-sm"
              >
                {statusMutation.isLoading ? 'Updating...' : 'Update Status'}
              </button>
            )}
            <button
              onClick={() => navigate(`/tournaments/${id}/edit`)}
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

      {/* Tabs */}
      <div className="border-b border-gray-200 mb-6">
        <nav className="flex space-x-8">
          {TABS.map((tab) => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <Icon className="w-5 h-5 mr-2" />
                {tab.label}
              </button>
            );
          })}
        </nav>
      </div>

      {/* Tab Content */}
      <div className="card">
        {activeTab === 'overview' && (
          <div className="space-y-6">
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-500">Status</p>
                  <span
                    className={`inline-block px-3 py-1 rounded-full text-sm font-medium mt-1 ${
                      tournament.status === 'ongoing'
                        ? 'bg-green-100 text-green-800'
                        : tournament.status === 'completed'
                        ? 'bg-gray-100 text-gray-800'
                        : tournament.status === 'cancelled'
                        ? 'bg-red-100 text-red-800'
                        : 'bg-blue-100 text-blue-800'
                    }`}
                  >
                    {tournament.status || 'planned'}
                  </span>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Sport</p>
                  <p className="font-medium text-gray-900 mt-1">
                    {tournament.sport?.name || 'N/A'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Location</p>
                  <p className="font-medium text-gray-900 mt-1">
                    {tournament.location || 'N/A'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Start Date</p>
                  <p className="font-medium text-gray-900 mt-1">
                    {tournament.start_date
                      ? new Date(tournament.start_date).toLocaleDateString()
                      : 'N/A'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">End Date</p>
                  <p className="font-medium text-gray-900 mt-1">
                    {tournament.end_date
                      ? new Date(tournament.end_date).toLocaleDateString()
                      : 'N/A'}
                  </p>
                </div>
                {tournament.created_by && (
                  <div>
                    <p className="text-sm text-gray-500">Created By</p>
                    <p className="font-medium text-gray-900 mt-1">
                      User ID: {tournament.created_by}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'matches' && (
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Tournament Matches</h2>
            {loadingMatches ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : matches.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No matches found for this tournament</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Home Team</th>
                      <th>Away Team</th>
                      <th>Date</th>
                      <th>Status</th>
                      {matches.length > 0 && matches[0].home_score !== undefined && <th>Score</th>}
                    </tr>
                  </thead>
                  <tbody>
                    {matches.map((match) => (
                      <tr 
                        key={match.id}
                        className="hover:bg-gray-50 transition-colors"
                      >
                        <td className="font-medium">{match.id}</td>
                        <td>{match.home_team?.name || match.team1?.name || match.team_1_name || 'N/A'}</td>
                        <td>{match.away_team?.name || match.team2?.name || match.team_2_name || 'N/A'}</td>
                        <td>
                          {match.match_date
                            ? new Date(match.match_date).toLocaleDateString()
                            : 'N/A'}
                        </td>
                        <td>
                          <span
                            className={`px-2 py-1 rounded-full text-xs font-medium ${
                              match.status === 'completed'
                                ? 'bg-green-100 text-green-800'
                                : match.status === 'ongoing'
                                ? 'bg-blue-100 text-blue-800'
                                : 'bg-gray-100 text-gray-800'
                            }`}
                          >
                            {match.status || 'scheduled'}
                          </span>
                        </td>
                        {match.home_score !== undefined && match.away_score !== undefined && (
                          <td className="font-semibold">
                            {match.home_score} - {match.away_score}
                          </td>
                        )}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {activeTab === 'teams' && (
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Participating Teams</h2>
            {loadingTeams ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : teams.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No teams found for this tournament</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Sport</th>
                    </tr>
                  </thead>
                  <tbody>
                    {teams.map((team) => (
                      <tr key={team.id}>
                        <td>{team.id}</td>
                        <td className="font-medium">{team.name}</td>
                        <td>{team.sport?.name || 'N/A'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {activeTab === 'standings' && (
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Tournament Standings</h2>
            {loadingStandings ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : standings.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No standings available</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Position</th>
                      <th>Team</th>
                      <th>Played</th>
                      <th>Won</th>
                      <th>Drawn</th>
                      <th>Lost</th>
                      <th>Points</th>
                    </tr>
                  </thead>
                  <tbody>
                    {standings.map((standing, index) => (
                      <tr key={standing.team_id || index}>
                        <td className="font-bold">{standing.position || index + 1}</td>
                        <td className="font-medium">{standing.team_name || 'N/A'}</td>
                        <td>{standing.played || 0}</td>
                        <td>{standing.won || 0}</td>
                        <td>{standing.drawn || 0}</td>
                        <td>{standing.lost || 0}</td>
                        <td className="font-bold">{standing.points || 0}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {activeTab === 'statistics' && (
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Tournament Statistics</h2>
            {loadingStatistics ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : Object.keys(statistics).length === 0 ? (
              <p className="text-gray-500 text-center py-8">No statistics available</p>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {Object.entries(statistics).map(([key, value]) => {
                  // Skip nested objects - they'll be displayed separately
                  if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    return null;
                  }
                  
                  return (
                    <div key={key} className="p-4 bg-gray-50 rounded-lg">
                      <p className="text-sm text-gray-500 capitalize mb-1">
                        {key.replace(/_/g, ' ')}
                      </p>
                      <p className="text-2xl font-bold text-gray-900">
                        {typeof value === 'number' && value % 1 !== 0 
                          ? value.toFixed(2) 
                          : value}
                      </p>
                    </div>
                  );
                })}
                
                {/* Display nested statistics objects */}
                {statistics.top_scorer && (
                  <div className="p-4 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-500 mb-1">Top Scorer</p>
                    <p className="text-lg font-semibold text-gray-900">
                      {statistics.top_scorer.player_name || 'N/A'}
                    </p>
                    <p className="text-sm text-gray-600">
                      {statistics.top_scorer.goals || 0} goals
                    </p>
                  </div>
                )}
                
                {statistics.best_attack && (
                  <div className="p-4 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-500 mb-1">Best Attack</p>
                    <p className="text-lg font-semibold text-gray-900">
                      Team ID: {statistics.best_attack.team_id || 'N/A'}
                    </p>
                    <p className="text-sm text-gray-600">
                      {statistics.best_attack.goals_scored || 0} goals scored
                    </p>
                  </div>
                )}
                
                {statistics.best_defense && (
                  <div className="p-4 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-500 mb-1">Best Defense</p>
                    <p className="text-lg font-semibold text-gray-900">
                      Team ID: {statistics.best_defense.team_id || 'N/A'}
                    </p>
                    <p className="text-sm text-gray-600">
                      {statistics.best_defense.goals_conceded || 0} goals conceded
                    </p>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>

      <ConfirmDialog
        isOpen={deleteConfirm}
        title="Delete Tournament"
        message="Are you sure you want to delete this tournament? This action cannot be undone."
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
