import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { teamsService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Edit, Trash2, Users, Trophy, BarChart3 } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const TABS = [
  { id: 'overview', label: 'Overview', icon: Trophy },
  { id: 'players', label: 'Players', icon: Users },
  { id: 'statistics', label: 'Statistics', icon: BarChart3 },
];

export default function TeamDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('overview');
  const [deleteConfirm, setDeleteConfirm] = useState(false);

  const { data: team, isLoading, error } = useQuery({
    queryKey: ['team', id],
    queryFn: () => teamsService.get(id),
  });

  const { data: playersData, isLoading: loadingPlayers } = useQuery({
    queryKey: ['team', id, 'players'],
    queryFn: () => teamsService.getPlayers(id, { per_page: 50 }),
    enabled: activeTab === 'players',
  });

  const { data: statisticsData, isLoading: loadingStatistics } = useQuery({
    queryKey: ['team', id, 'statistics'],
    queryFn: async () => {
      try {
        // Try overview endpoint first (includes more data)
        return await teamsService.getOverview(id);
      } catch (error) {
        // Fallback to statistics endpoint if overview fails
        return await teamsService.getStatistics(id);
      }
    },
    enabled: activeTab === 'statistics',
  });

  const deleteMutation = useMutation({
    mutationFn: () => teamsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['teams']);
      toast.success('Team deleted successfully');
      navigate('/teams');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete team');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !team) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Team not found'}
      </div>
    );
  }

  const players = playersData?.data || playersData || [];
  
  // Extract statistics from overview or statistics response
  let statistics = {};
  if (statisticsData) {
    if (statisticsData.statistics) {
      // From overview endpoint: { team: {...}, statistics: {...} }
      statistics = statisticsData.statistics;
    } else {
      // From statistics endpoint: direct statistics object
      statistics = statisticsData;
    }
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/teams')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Teams
        </button>
        <div className="flex justify-between items-start">
          <div className="flex items-center space-x-4">
            {team.logo && (
              <img
                src={team.logo}
                alt={team.name}
                className="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
                onError={(e) => {
                  e.target.style.display = 'none';
                }}
              />
            )}
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">{team.name}</h1>
              {team.tournament && (
                <p className="text-gray-600">
                  <Trophy className="w-4 h-4 inline mr-1" />
                  {team.tournament.name}
                </p>
              )}
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => navigate(`/teams/${id}/edit`)}
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
                  <p className="text-sm text-gray-500">ID</p>
                  <p className="font-medium text-gray-900">{team.id}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Name</p>
                  <p className="font-medium text-gray-900">{team.name}</p>
                </div>
                {team.tournament && (
                  <div>
                    <p className="text-sm text-gray-500">Tournament</p>
                    <p className="font-medium text-gray-900">{team.tournament.name}</p>
                  </div>
                )}
                <div>
                  <p className="text-sm text-gray-500">Total Players</p>
                  <p className="font-medium text-gray-900">
                    {team.players?.length || team.players_count || 0}
                  </p>
                </div>
                {team.coaches && team.coaches.length > 0 && (
                  <div className="md:col-span-2">
                    <p className="text-sm text-gray-500 mb-2">Coaches</p>
                    <div className="flex flex-wrap gap-2">
                      {team.coaches.map((coach, index) => (
                        <span
                          key={coach.id || index}
                          className="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium"
                        >
                          {coach.name || coach.email || `Coach ${index + 1}`}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
                {team.created_at && (
                  <div>
                    <p className="text-sm text-gray-500">Created At</p>
                    <p className="text-gray-900">
                      {new Date(team.created_at).toLocaleString()}
                    </p>
                  </div>
                )}
                {team.updated_at && (
                  <div>
                    <p className="text-sm text-gray-500">Updated At</p>
                    <p className="text-gray-900">
                      {new Date(team.updated_at).toLocaleString()}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'players' && (
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Team Players</h2>
            {loadingPlayers ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : players.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No players found for this team</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Position</th>
                      <th>Jersey Number</th>
                    </tr>
                  </thead>
                  <tbody>
                    {players.map((player) => (
                      <tr key={player.id}>
                        <td>{player.id}</td>
                        <td className="font-medium">
                          {player.full_name || player.name || 'N/A'}
                        </td>
                        <td>{player.position || 'N/A'}</td>
                        <td>{player.jersey_number || 'N/A'}</td>
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
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Team Statistics</h2>
            {loadingStatistics ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            ) : Object.keys(statistics).length === 0 ? (
              <p className="text-gray-500 text-center py-8">No statistics available</p>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {Object.entries(statistics).map(([key, value]) => {
                  if (typeof value === 'object' && value !== null) {
                    return null;
                  }
                  return (
                    <div key={key} className="p-4 bg-gray-50 rounded-lg">
                      <p className="text-sm text-gray-500 capitalize mb-1">
                        {key.replace(/_/g, ' ')}
                      </p>
                      <p className="text-2xl font-bold text-gray-900">{value}</p>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}
      </div>

      <ConfirmDialog
        isOpen={deleteConfirm}
        title="Delete Team"
        message="Are you sure you want to delete this team? This action cannot be undone."
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
