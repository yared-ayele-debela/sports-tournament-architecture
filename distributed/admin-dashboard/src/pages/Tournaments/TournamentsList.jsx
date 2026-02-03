import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { tournamentsService, sportsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye, Filter, X } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const STATUS_OPTIONS = [
  { value: '', label: 'All Status' },
  { value: 'planned', label: 'Planned' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export default function TournamentsList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [sportFilter, setSportFilter] = useState('');
  const [deleteTournamentId, setDeleteTournamentId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();

  // Check permissions
  const canManageTournaments = hasPermission('manage_tournaments') || isAdmin();

  // Block access if user doesn't have permission
  if (!canManageTournaments) {
    return <Unauthorized message="You do not have permission to manage tournaments." />;
  }

  // Fetch sports for filter
  const { data: sportsData } = useQuery({
    queryKey: ['sports'],
    queryFn: () => sportsService.list(),
  });

  const sports = Array.isArray(sportsData) ? sportsData : sportsData?.data || [];

  // Debounce search term
  useEffect(() => {
    console.log('TournamentsList - Search term changed:', searchTerm);
    const timer = setTimeout(() => {
      console.log('TournamentsList - Setting debounced search term:', searchTerm);
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['tournaments', page, debouncedSearchTerm, statusFilter, sportFilter],
    queryFn: async () => {
      // Build query params inside the function to get current values
      const queryParams = {
        per_page: 15,
        page,
        ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
        ...(statusFilter && { status: statusFilter }),
        ...(sportFilter && { sport_id: sportFilter }),
      };
      console.log('TournamentsList - Fetching with params:', queryParams);
      const result = await tournamentsService.list(queryParams);
      console.log('TournamentsList - Result:', result);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => tournamentsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['tournaments']);
      setDeleteTournamentId(null);
      toast.success('Tournament deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete tournament');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    console.log('TournamentsList - Manual search triggered with term:', searchTerm);
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteTournamentId) {
      deleteMutation.mutate(deleteTournamentId);
    }
  };

  const clearFilters = () => {
    setStatusFilter('');
    setSportFilter('');
    setSearchTerm('');
    setDebouncedSearchTerm('');
    setPage(1);
  };

  // Extract tournaments and pagination
  let tournaments = [];
  let pagination = {};

  if (Array.isArray(data)) {
    tournaments = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    tournaments = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? tournaments.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? tournaments.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? tournaments.length,
        from: data.from ?? 1,
        to: data.to ?? tournaments.length,
      };
    }
  }

  // Remove the automatic page sync to prevent pagination loops
  // The page state should be controlled by user interactions only

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  const hasActiveFilters = statusFilter || sportFilter || debouncedSearchTerm;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Tournaments</h1>
        <button
          onClick={() => navigate('/tournaments/new')}
          className="btn btn-primary flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Create Tournament
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

        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                placeholder="Search tournaments..."
                className="input pl-10"
              />
            </div>
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
            <label htmlFor="sport" className="label">Sport</label>
            <select
              id="sport"
              value={sportFilter}
              onChange={(e) => {
                setSportFilter(e.target.value);
                setPage(1);
              }}
              className="input"
            >
              <option value="">All Sports</option>
              {sports.map((sport) => (
                <option key={sport.id} value={sport.id}>
                  {sport.name}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-end">
            <button type="submit" className="btn btn-primary w-full">
              Search
            </button>
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
            {error?.response?.data?.message || 'Failed to load tournaments'}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Sport</th>
                    <th>Location</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {tournaments.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="text-center py-8 text-gray-500">
                        No tournaments found
                      </td>
                    </tr>
                  ) : (
                    tournaments.map((tournament) => (
                      <tr key={tournament.id}>
                        <td>{tournament.id}</td>
                        <td className="font-medium">{tournament.name}</td>
                        <td>{tournament.sport?.name || 'N/A'}</td>
                        <td>{tournament.location || 'N/A'}</td>
                        <td>
                          {tournament.start_date
                            ? new Date(tournament.start_date).toLocaleDateString()
                            : 'N/A'}
                        </td>
                        <td>
                          {tournament.end_date
                            ? new Date(tournament.end_date).toLocaleDateString()
                            : 'N/A'}
                        </td>
                        <td>
                          <span
                            className={`px-2 py-1 rounded-full text-xs font-medium ${
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
                        </td>
                        <td>
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => navigate(`/tournaments/${tournament.id}`)}
                              className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                              title="View"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => navigate(`/tournaments/${tournament.id}/edit`)}
                              className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                              title="Edit"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => setDeleteTournamentId(tournament.id)}
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
        isOpen={!!deleteTournamentId}
        title="Delete Tournament"
        message="Are you sure you want to delete this tournament? This action cannot be undone."
        onConfirm={handleDelete}
        onCancel={() => setDeleteTournamentId(null)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
