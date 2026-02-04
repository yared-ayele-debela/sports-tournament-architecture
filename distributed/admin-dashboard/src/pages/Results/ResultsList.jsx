import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { resultsService } from '../../api/results';
import { tournamentsService } from '../../api/tournaments';
import { teamsService } from '../../api/teams';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Filter, X, Eye } from 'lucide-react';

export default function ResultsList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [tournamentFilter, setTournamentFilter] = useState('');
  const [teamFilter, setTeamFilter] = useState('');
  const navigate = useNavigate();
  const { hasPermission, isAdmin } = usePermissions();

  // Check permissions - users can view results if they can record events or are admin
  const canViewResults = hasPermission('record_events') || hasPermission('submit_reports') || isAdmin();

  // Block access if user doesn't have permission
  if (!canViewResults) {
    return <Unauthorized message="You do not have permission to view results." />;
  }

  // Fetch tournaments
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
    ...(teamFilter && { team_id: teamFilter }),
  };

  const { data, isLoading, error } = useQuery({
    queryKey: ['results', tournamentFilter, page, teamFilter],
    queryFn: async () => {
      if (!tournamentFilter) return { data: [] };
      return resultsService.getResults(tournamentFilter, queryParams);
    },
    enabled: !!tournamentFilter,
    retry: false,
  });

  const clearFilters = () => {
    setTeamFilter('');
    setTournamentFilter('');
    setSearchTerm('');
    setDebouncedSearchTerm('');
    setPage(1);
  };

  // Extract results and pagination
  let results = [];
  let pagination = {};

  if (Array.isArray(data)) {
    results = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    results = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? results.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? results.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? results.length,
        from: data.from ?? 1,
        to: data.to ?? results.length,
      };
    }
  }

  const hasActiveFilters = teamFilter || tournamentFilter || debouncedSearchTerm;

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Match Results</h1>
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

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              <option value="">Select a tournament</option>
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
        </div>
      </div>

      {/* Results Table */}
      <div className="card">
        {error ? (
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {error?.response?.data?.message || 'Failed to load results'}
          </div>
        ) : !tournamentFilter ? (
          <div className="p-8 text-center text-gray-500">
            Please select a tournament to view results
          </div>
        ) : results.length === 0 ? (
          <div className="p-8 text-center text-gray-500">No results found</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Match ID</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Score</th>
                    <th>Completed At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {results.map((result) => (
                    <tr key={result.id}>
                      <td>{result.id}</td>
                      <td>{result.match_id || 'N/A'}</td>
                      <td className="font-medium">
                        {result.home_team?.name || `Team ${result.home_team_id || 'N/A'}`}
                      </td>
                      <td className="font-medium">
                        {result.away_team?.name || `Team ${result.away_team_id || 'N/A'}`}
                      </td>
                      <td>
                        <span className="font-bold">
                          {result.home_score ?? '-'} - {result.away_score ?? '-'}
                        </span>
                      </td>
                      <td>
                        {result.completed_at
                          ? new Date(result.completed_at).toLocaleString()
                          : 'N/A'}
                      </td>
                      <td>
                        <button
                          onClick={() => navigate(`/results/${result.id}`)}
                          className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                          title="View"
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
                  Showing {pagination.from} to {pagination.to} of {pagination.total} results
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => setPage(page - 1)}
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
                    onClick={() => setPage(page + 1)}
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
