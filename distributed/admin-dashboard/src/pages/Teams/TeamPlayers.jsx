import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { teamsService, playersService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import { ArrowLeft, Plus, Edit, Trash2, Search, X } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import PlayerFormModal from './PlayerFormModal';

const POSITION_OPTIONS = [
  'Goalkeeper',
  'Defender',
  'Midfielder',
  'Forward',
];

export default function TeamPlayers() {
  const { id: teamId } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isCoach } = usePermissions();
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [deletePlayerId, setDeletePlayerId] = useState(null);
  const [showPlayerForm, setShowPlayerForm] = useState(false);
  const [editingPlayer, setEditingPlayer] = useState(null);

  // Fetch team details
  const { data: team, isLoading: loadingTeam } = useQuery({
    queryKey: ['team', teamId],
    queryFn: () => teamsService.get(teamId),
    enabled: !!teamId,
  });

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Fetch players for this team
  const { data: playersData, isLoading: loadingPlayers, isFetching } = useQuery({
    queryKey: ['team', teamId, 'players', page, debouncedSearchTerm],
    queryFn: async () => {
      const params = {
        per_page: 15,
        page,
        ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
      };
      return teamsService.getPlayers(teamId, params);
    },
    enabled: !!teamId,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => playersService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['team', teamId, 'players']);
      setDeletePlayerId(null);
      toast.success('Player deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete player');
    },
  });

  const handleDelete = () => {
    if (deletePlayerId) {
      deleteMutation.mutate(deletePlayerId);
    }
  };

  const handleEdit = (player) => {
    setEditingPlayer(player);
    setShowPlayerForm(true);
  };

  const handleCreate = () => {
    setEditingPlayer(null);
    setShowPlayerForm(true);
  };

  const handleFormClose = () => {
    setShowPlayerForm(false);
    setEditingPlayer(null);
  };

  const handleFormSuccess = () => {
    queryClient.invalidateQueries(['team', teamId, 'players']);
    handleFormClose();
  };

  // Extract players and pagination
  let players = [];
  let pagination = {};

  if (Array.isArray(playersData)) {
    players = playersData;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: players.length,
      from: 1,
      to: players.length,
    };
  } else if (playersData && typeof playersData === 'object') {
    if (playersData.data && Array.isArray(playersData.data)) {
      players = playersData.data;
    } else if (playersData.data && typeof playersData.data === 'object' && Array.isArray(playersData.data.data)) {
      players = playersData.data.data || [];
    } else if (Array.isArray(playersData.data)) {
      players = playersData.data;
    } else {
      players = [];
    }

    if (playersData.pagination) {
      pagination = playersData.pagination;
    } else if (playersData.data?.pagination) {
      pagination = playersData.data.pagination;
    } else {
      pagination = {
        current_page: page,
        last_page: 1,
        per_page: 15,
        total: players.length,
        from: 1,
        to: players.length,
      };
    }
  }

  if (loadingTeam) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!team) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        Team not found or you don't have access to this team
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back
        </button>
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">
              {team.name} - Players
            </h1>
            <p className="text-gray-600">Manage players for this team</p>
          </div>
          <button
            onClick={handleCreate}
            className="btn btn-primary flex items-center"
          >
            <Plus className="w-5 h-5 mr-2" />
            Add Player
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="card mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search players by name..."
            className="input pl-10"
          />
        </div>
      </div>

      {/* Players Table */}
      <div className="card relative">
        {isFetching && playersData && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
              <span className="text-sm text-gray-600">Loading...</span>
            </div>
          </div>
        )}

        {loadingPlayers && !playersData ? (
          <div className="flex justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
          </div>
        ) : players.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-600 text-lg mb-2">No players found</p>
            <p className="text-gray-500 mb-4">
              {debouncedSearchTerm
                ? 'No players match your search criteria.'
                : 'This team has no players yet.'}
            </p>
            {!debouncedSearchTerm && (
              <button
                onClick={handleCreate}
                className="btn btn-primary inline-flex items-center"
              >
                <Plus className="w-5 h-5 mr-2" />
                Add First Player
              </button>
            )}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Jersey Number</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {players.map((player) => (
                    <tr key={player.id}>
                      <td>{player.id}</td>
                      <td className="font-medium">
                        {player.full_name || player.name || 'N/A'}
                      </td>
                      <td>
                        <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                          {player.position || 'N/A'}
                        </span>
                      </td>
                      <td className="font-semibold">#{player.jersey_number || 'N/A'}</td>
                      <td>
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => handleEdit(player)}
                            className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                            title="Edit"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => setDeletePlayerId(player.id)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded"
                            title="Delete"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {pagination.last_page > 1 && (
              <div className="mt-6 flex items-center justify-between">
                <div className="text-sm text-gray-600">
                  Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total || 0} results
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => setPage(Math.max(1, page - 1))}
                    disabled={page === 1}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <div className="flex items-center space-x-1">
                    {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                      let pageNum;
                      if (pagination.last_page <= 5) {
                        pageNum = i + 1;
                      } else if (page <= 3) {
                        pageNum = i + 1;
                      } else if (page >= pagination.last_page - 2) {
                        pageNum = pagination.last_page - 4 + i;
                      } else {
                        pageNum = page - 2 + i;
                      }
                      return (
                        <button
                          key={pageNum}
                          onClick={() => setPage(pageNum)}
                          className={`px-3 py-1 rounded ${
                            page === pageNum
                              ? 'bg-primary-600 text-white'
                              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                          }`}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                  </div>
                  <button
                    onClick={() => setPage(Math.min(pagination.last_page, page + 1))}
                    disabled={page >= pagination.last_page}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      {/* Player Form Modal */}
      {showPlayerForm && (
        <PlayerFormModal
          teamId={teamId}
          player={editingPlayer}
          onClose={handleFormClose}
          onSuccess={handleFormSuccess}
        />
      )}

      {/* Delete Confirmation */}
      <ConfirmDialog
        isOpen={!!deletePlayerId}
        title="Delete Player"
        message="Are you sure you want to delete this player? This action cannot be undone."
        onConfirm={handleDelete}
        onCancel={() => setDeletePlayerId(null)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
