import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { matchesService } from '../../api/matches';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import { Search, Eye, Calendar, Trophy, Clock } from 'lucide-react';

export default function MyMatches() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);
  const navigate = useNavigate();
  const toast = useToast();
  const { isCoach } = usePermissions();

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Build query params - backend will automatically filter by coach's teams
  const queryParams = {
    per_page: 15,
    page,
    ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
    ...(statusFilter && { status: statusFilter }),
  };

  // Fetch coach's matches - the API should filter by coach automatically
  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['matches', 'my-matches', page, debouncedSearchTerm, statusFilter],
    queryFn: async () => {
      const result = await matchesService.list(queryParams);
      return result;
    },
    retry: false,
  });

  // Extract matches - handle different response structures
  let matches = [];
  let pagination = {};

  if (!data) {
    matches = [];
  } else if (Array.isArray(data)) {
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
    // Handle { data: [...], pagination: {...} } structure
    if (Array.isArray(data.data)) {
      matches = data.data;
    } else if (data.data && typeof data.data === 'object' && Array.isArray(data.data.data)) {
      matches = data.data.data || [];
    }

    if (data.pagination) {
      pagination = data.pagination;
    } else if (data.data?.pagination) {
      pagination = data.data.pagination;
    } else {
      pagination = {
        current_page: page,
        last_page: 1,
        per_page: 15,
        total: matches.length,
        from: 1,
        to: matches.length,
      };
    }
  }

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Failed to load your matches'}
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">My Matches</h1>
          <p className="text-gray-600 mt-1">View matches for your teams</p>
        </div>
      </div>

      {/* Filters */}
      <div className="card mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search matches..."
              className="input pl-10"
            />
          </div>
          <div>
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setPage(1);
              }}
              className="input"
            >
              <option value="">All Status</option>
              <option value="scheduled">Scheduled</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
      </div>

      {/* Matches Table */}
      <div className="card relative">
        {isFetching && data && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
              <span className="text-sm text-gray-600">Loading...</span>
            </div>
          </div>
        )}

        {matches.length === 0 ? (
          <div className="text-center py-12">
            <Calendar className="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600 text-lg mb-2">No matches found</p>
            <p className="text-gray-500">
              {statusFilter
                ? 'No matches found with the selected status.'
                : 'You have no matches scheduled for your teams yet.'}
            </p>
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Tournament</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {matches.map((match) => (
                    <tr key={match.id} className="hover:bg-gray-50 transition-colors">
                      <td>{match.id}</td>
                      <td className="font-medium">
                        {match.home_team?.name || 'TBD'}
                      </td>
                      <td className="font-medium">
                        {match.away_team?.name || 'TBD'}
                      </td>
                      <td>
                        {match.tournament?.name || 'N/A'}
                      </td>
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
                              : match.status === 'in_progress'
                              ? 'bg-blue-100 text-blue-800'
                              : match.status === 'cancelled'
                              ? 'bg-red-100 text-red-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {match.status || 'scheduled'}
                        </span>
                      </td>
                      <td>
                        {match.home_score !== null && match.away_score !== null ? (
                          <span className="font-semibold">
                            {match.home_score} - {match.away_score}
                          </span>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                      <td>
                        <button
                          onClick={() => navigate(`/matches/my-matches/${match.id}`)}
                          className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                          title="View Details"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
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
    </div>
  );
}
