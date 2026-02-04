import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { playersService } from '../../api/teams';
import { teamsService } from '../../api/teams';
import { tournamentsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye, Filter, X } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const POSITION_OPTIONS = [
  'Goalkeeper',
  'Defender',
  'Midfielder',
  'Forward',
];

export default function PlayersList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [teamFilter, setTeamFilter] = useState('');
  const [tournamentFilter, setTournamentFilter] = useState('');
  const [deletePlayerId, setDeletePlayerId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();

  // Check permissions
  const canManagePlayers = hasPermission('manage_players') || isAdmin();

  // Block access if user doesn't have permission
  if (!canManagePlayers) {
    return <Unauthorized message="You do not have permission to manage players." />;
  }

  // Fetch tournaments for filter
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Fetch teams for the selected tournament
  const { data: teamsData } = useQuery({
    queryKey: ['teams', 'tournament', tournamentFilter],
    queryFn: () => {
      if (!tournamentFilter) return { data: [] };
      return teamsService.list({ tournament_id: tournamentFilter, per_page: 100 });
    },
    enabled: !!tournamentFilter,
  });

  const teams = teamsData?.data || teamsData || [];

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Update team filter when tournament changes
  useEffect(() => {
    if (tournamentFilter && teamFilter) {
      // Check if current team belongs to selected tournament
      const currentTeam = teams.find(t => t.id === parseInt(teamFilter));
      if (!currentTeam) {
        setTeamFilter('');
      }
    }
  }, [tournamentFilter, teams, teamFilter]);

  // Build query params
  const queryParams = {
    per_page: 15,
    page,
    ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
    ...(teamFilter && { team_id: teamFilter }),
  };

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['players', page, debouncedSearchTerm, teamFilter],
    queryFn: async () => {
      const result = await playersService.list(queryParams);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => playersService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['players']);
      setDeletePlayerId(null);
      toast.success('Player deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete player');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deletePlayerId) {
      deleteMutation.mutate(deletePlayerId);
    }
  };

  const clearFilters = () => {
    setTeamFilter('');
    setTournamentFilter('');
    setSearchTerm('');
    setDebouncedSearchTerm('');
    setPage(1);
  };

  // Extract players and pagination
  let players = [];
  let pagination = {};

  if (Array.isArray(data)) {
    players = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    players = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? players.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? players.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? players.length,
        from: data.from ?? 1,
        to: data.to ?? players.length,
      };
    }
  }

  // Sync page state
  useEffect(() => {
    if (pagination.current_page && pagination.current_page !== page) {
      setPage(pagination.current_page);
    }
  }, [pagination.current_page, page]);

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  const hasActiveFilters = teamFilter || tournamentFilter || debouncedSearchTerm;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Players</h1>
        <button
          onClick={() => navigate('/players/new')}
          className="btn btn-primary flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Create Player
        </button>
      </div>

      {/* Filters */}
      <div className="card mb-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center">
            <Filter className="w-5 h-5 mr-2" />
            Filters
          </h2>
          {hasActiveFilters && (
            <button
              onClick={clearFilters}
              className="text-sm text-primary-600 hover:text-primary-700 flex items-center"
            >
              <X className="w-4 h-4 mr-1" />
              Clear Filters
            </button>
          )}
        </div>

        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="md:col-span-2">
            <label htmlFor="search" className="label">Search</label>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                ref={searchInputRef}
                id="search"
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search players..."
                className="input pl-10"
              />
            </div>
          </div>

          <div>
            <label htmlFor="tournament" className="label">Tournament</label>
            <select
              id="tournament"
              value={tournamentFilter}
              onChange={(e) => {
                setTournamentFilter(e.target.value);
                setTeamFilter(''); // Reset team filter when tournament changes
                setPage(1);
              }}
              className="input"
            >
              <option value="">All Tournaments</option>
              {tournaments.map((tournament) => (
                <option key={tournament.id} value={tournament.id}>
                  {tournament.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="team" className="label">Team</label>
            <select
              id="team"
              value={teamFilter}
              onChange={(e) => {
                setTeamFilter(e.target.value);
                setPage(1);
              }}
              className="input"
              disabled={!tournamentFilter}
            >
              <option value="">All Teams</option>
              {teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
            {!tournamentFilter && (
              <p className="mt-1 text-sm text-gray-500">Select a tournament first</p>
            )}
          </div>
        </form>
      </div>

      {/* Table */}
      <div className="card relative">
        {isFetching && data && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
              <span className="text-sm text-gray-600">Loading...</span>
            </div>
          </div>
        )}

        {error ? (
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {error?.response?.data?.message || 'Failed to load players'}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Team</th>
                    <th>Position</th>
                    <th>Jersey Number</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {players.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="text-center py-8 text-gray-500">
                        No players found
                      </td>
                    </tr>
                  ) : (
                    players.map((player) => (
                      <tr key={player.id}>
                        <td>{player.id}</td>
                        <td className="font-medium">
                          {player.full_name || player.name || 'N/A'}
                        </td>
                        <td>{player.team?.name || 'N/A'}</td>
                        <td>
                          <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                            {player.position || 'N/A'}
                          </span>
                        </td>
                        <td className="font-semibold">#{player.jersey_number || 'N/A'}</td>
                        <td>
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => navigate(`/players/${player.id}`)}
                              className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                              title="View"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => navigate(`/players/${player.id}/edit`)}
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
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {pagination.last_page > 1 && (
              <div className="mt-6 flex items-center justify-between">
                <div className="text-sm text-gray-600">
                  Showing {pagination.from} to {pagination.to} of {pagination.total} results
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => {
                      const newPage = Math.max(1, page - 1);
                      console.log('Previous button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
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
                          onClick={() => {
                            console.log('Page button clicked, going to page:', pageNum);
                            setPage(pageNum);
                          }}
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
                    onClick={() => {
                      const newPage = Math.min(pagination.last_page, page + 1);
                      console.log('Next button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
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
