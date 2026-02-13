import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { teamsService } from '../../api/teams';
import { tournamentsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye, Filter, X, Users } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function TeamsList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [tournamentFilter, setTournamentFilter] = useState('');
  const [deleteTeamId, setDeleteTeamId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();

  // Check permissions
  const canManageTeams = hasPermission('manage_teams') || isAdmin();

  // Block access if user doesn't have permission
  if (!canManageTeams) {
    return <Unauthorized message="You do not have permission to manage teams." />;
  }

  // Fetch tournaments for filter
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Build query params - tournament_id is required
  const queryParams = {
    per_page: 15,
    page,
    ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
    ...(tournamentFilter && { tournament_id: tournamentFilter }),
  };

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['teams', page, debouncedSearchTerm, tournamentFilter],
    queryFn: async () => {
      if (!tournamentFilter) {
        // Return empty result if no tournament is selected
        return { data: [], pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 } };
      }
      const result = await teamsService.list(queryParams);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
    enabled: !!tournamentFilter, // Only fetch when tournament is selected
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => teamsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['teams']);
      setDeleteTeamId(null);
      toast.success('Team deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete team');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteTeamId) {
      deleteMutation.mutate(deleteTeamId);
    }
  };

  const clearFilters = () => {
    setTournamentFilter('');
    setSearchTerm('');
    setDebouncedSearchTerm('');
    setPage(1);
  };

  // Extract teams and pagination
  let teams = [];
  let pagination = {};


  if (Array.isArray(data)) {
    teams = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    // Handle nested data structure: { data: { data: [...], pagination: {...} } }
    if (data.data && Array.isArray(data.data)) {
      teams = data.data;
    } else if (data.data && typeof data.data === 'object' && Array.isArray(data.data.data)) {
      // Double nested: { data: { data: [...], pagination: {...} } }
      teams = data.data.data || [];
    } else if (Array.isArray(data.data)) {
      teams = data.data;
    } else {
      teams = [];
    }

    // Extract pagination
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? teams.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? teams.length,
      };
    } else if (data.data && data.data.pagination) {
      pagination = {
        current_page: data.data.pagination.current_page ?? 1,
        last_page: data.data.pagination.last_page ?? 1,
        per_page: data.data.pagination.per_page ?? 15,
        total: data.data.pagination.total ?? teams.length,
        from: data.data.pagination.from ?? 1,
        to: data.data.pagination.to ?? teams.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? teams.length,
        from: data.from ?? 1,
        to: data.to ?? teams.length,
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

  const hasActiveFilters = tournamentFilter || debouncedSearchTerm;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Teams</h1>
        <button
          onClick={() => navigate('/teams/new')}
          className="btn btn-primary flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Create Team
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
                placeholder="Search teams..."
                className="input pl-10"
              />
            </div>
          </div>

          <div>
            <label htmlFor="tournament" className="label">
              Tournament <span className="text-red-500">*</span>
            </label>
            <select
              id="tournament"
              value={tournamentFilter}
              onChange={(e) => {
                setTournamentFilter(e.target.value);
                setPage(1);
              }}
              className="input"
              required
            >
              <option value="">Select a tournament</option>
              {tournaments.map((tournament) => (
                <option key={tournament.id} value={tournament.id}>
                  {tournament.name}
                </option>
              ))}
            </select>
            <p className="mt-1 text-sm text-gray-500">Select a tournament to view its teams</p>
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

        {!tournamentFilter ? (
          <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-700">
            <p className="font-medium mb-1">Select a tournament to view teams</p>
            <p className="text-sm">Please select a tournament from the filter above to see the teams.</p>
          </div>
        ) : error ? (
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {error?.response?.data?.message || error?.message || 'Failed to load teams'}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Tournament</th>
                    <th>Players</th>
                    <th>Coaches</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {teams.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="text-center py-8 text-gray-500">
                        No teams found
                      </td>
                    </tr>
                  ) : (
                    teams.map((team) => (
                      <tr key={team.id}>
                        <td>{team.id}</td>
                        <td className="font-medium">
                          <div className="flex items-center space-x-2">
                            {team.logo && (
                              <img
                                src={team.logo}
                                alt={team.name}
                                className="w-8 h-8 rounded-full object-cover"
                                onError={(e) => {
                                  e.target.style.display = 'none';
                                }}
                              />
                            )}
                            <span>{team.name}</span>
                          </div>
                        </td>
                        <td>
                          {team.tournament?.name || team.tournament_id || 'N/A'}
                        </td>
                        <td>{team.players?.length || team.players_count || 0}</td>
                        <td>
                          {team.coaches_list && team.coaches_list.length > 0 ? (
                            <span className="text-sm">
                              {team.coaches_list.join(', ')}
                            </span>
                          ) : (
                            <span className="text-gray-400">No coaches</span>
                          )}
                        </td>
                        <td>
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => navigate(`/teams/${team.id}`)}
                              className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                              title="View"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => navigate(`/teams/${team.id}/edit`)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                              title="Edit"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => navigate(`/teams/${team.id}/players`)}
                              className="p-2 text-green-600 hover:bg-green-50 rounded"
                              title="See Players"
                            >
                              <Users className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => setDeleteTeamId(team.id)}
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
        isOpen={!!deleteTeamId}
        title="Delete Team"
        message="Are you sure you want to delete this team? This action cannot be undone."
        onConfirm={handleDelete}
        onCancel={() => setDeleteTeamId(null)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
