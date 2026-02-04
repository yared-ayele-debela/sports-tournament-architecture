import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { matchesService } from '../../api/matches';
import { tournamentsService } from '../../api/tournaments';
import { teamsService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye, Filter, X } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const STATUS_OPTIONS = [
  { value: '', label: 'All Status' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export default function MatchesList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [tournamentFilter, setTournamentFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [teamFilter, setTeamFilter] = useState('');
  const [deleteMatchId, setDeleteMatchId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();

  // Check permissions
  const canManageMatches = hasPermission('manage_matches') || isAdmin();

  // Block access if user doesn't have permission
  if (!canManageMatches) {
    return <Unauthorized message="You do not have permission to manage matches." />;
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
    ...(tournamentFilter && { tournament_id: tournamentFilter }),
    ...(statusFilter && { status: statusFilter }),
    ...(teamFilter && { team_id: teamFilter }),
  };

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['matches', page, debouncedSearchTerm, tournamentFilter, statusFilter, teamFilter],
    queryFn: async () => {
      const result = await matchesService.list(queryParams);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => matchesService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['matches']);
      setDeleteMatchId(null);
      toast.success('Match deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete match');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteMatchId) {
      deleteMutation.mutate(deleteMatchId);
    }
  };

  const clearFilters = () => {
    setTournamentFilter('');
    setStatusFilter('');
    setTeamFilter('');
    setSearchTerm('');
    setDebouncedSearchTerm('');
    setPage(1);
  };

  // Extract matches and pagination
  let matches = [];
  let pagination = {};

  if (Array.isArray(data)) {
    matches = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    matches = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? matches.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? matches.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? matches.length,
        from: data.from ?? 1,
        to: data.to ?? matches.length,
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

  const hasActiveFilters = tournamentFilter || statusFilter || teamFilter || debouncedSearchTerm;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Matches</h1>
        <button
          onClick={() => navigate('/matches/new')}
          className="btn btn-primary flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Create Match
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

        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                placeholder="Search matches..."
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
                setTeamFilter('');
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
            <label htmlFor="status" className="label">Status</label>
            <select
              id="status"
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setPage(1);
              }}
              className="input"
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
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
            {error?.response?.data?.message || 'Failed to load matches'}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tournament</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Score</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Round</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {matches.length === 0 ? (
                    <tr>
                      <td colSpan="9" className="text-center py-8 text-gray-500">
                        No matches found
                      </td>
                    </tr>
                  ) : (
                    matches.map((match) => (
                      <tr key={match.id}>
                        <td>{match.id}</td>
                        <td>{match.tournament?.name || 'N/A'}</td>
                        <td className="font-medium">
                          {match.home_team?.name || match.home_team_name || 'N/A'}
                        </td>
                        <td className="font-medium">
                          {match.away_team?.name || match.away_team_name || 'N/A'}
                        </td>
                        <td>
                          {match.home_score !== null && match.away_score !== null ? (
                            <span className="font-bold">
                              {match.home_score} - {match.away_score}
                            </span>
                          ) : (
                            <span className="text-gray-400">-</span>
                          )}
                        </td>
                        <td>
                          {match.match_date
                            ? new Date(match.match_date).toLocaleString()
                            : 'N/A'}
                        </td>
                        <td>
                          <span
                            className={`px-2 py-1 rounded-full text-xs font-medium ${
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
                        </td>
                        <td>{match.round_number || 'N/A'}</td>
                        <td>
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => navigate(`/matches/${match.id}`)}
                              className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                              title="View"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => navigate(`/matches/${match.id}/edit`)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                              title="Edit"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => setDeleteMatchId(match.id)}
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
        isOpen={!!deleteMatchId}
        title="Delete Match"
        message="Are you sure you want to delete this match? This action cannot be undone."
        onConfirm={handleDelete}
        onCancel={() => setDeleteMatchId(null)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
