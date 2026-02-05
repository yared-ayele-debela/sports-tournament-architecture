import { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { Calendar, List, Filter, X, Search, Clock } from 'lucide-react';
import { matchService } from '../../api/matches';
import { tournamentService } from '../../api/tournaments';
import MatchCard from '../../components/match/MatchCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Pagination from '../../components/common/Pagination';
import Button from '../../components/common/Button';
import Breadcrumbs from '../../components/layout/Breadcrumbs';
import { formatDate, isDateToday, isDateTomorrow } from '../../utils/dateUtils';
import { MATCH_STATUS } from '../../utils/constants';

const MatchesList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  
  // Filter states
  const [tournamentId, setTournamentId] = useState(searchParams.get('tournament_id') || '');
  const [status, setStatus] = useState(searchParams.get('status') || 'all');
  const [searchQuery, setSearchQuery] = useState(searchParams.get('search') || '');
  const [startDate, setStartDate] = useState(searchParams.get('start_date') || '');
  const [endDate, setEndDate] = useState(searchParams.get('end_date') || '');
  const [teamId, setTeamId] = useState(searchParams.get('team_id') || '');
  
  // View and pagination
  const [viewMode, setViewMode] = useState(localStorage.getItem('matchesViewMode') || 'calendar');
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page')) || 1);
  const [perPage, setPerPage] = useState(parseInt(searchParams.get('per_page')) || 20);
  const [showFilters, setShowFilters] = useState(false);

  // Fetch tournaments for filter dropdown
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournamentsForFilter'],
    queryFn: () => tournamentService.getAll({ limit: 100 }),
  });

  // Build query params
  const queryParams = useMemo(() => {
    const params = {
      page: currentPage,
      limit: perPage,
    };

    if (tournamentId) {
      params.tournament_id = tournamentId;
    }

    if (status !== 'all') {
      params.status = status;
    }

    if (searchQuery.trim()) {
      params.search = searchQuery.trim();
    }

    if (startDate) {
      params.start_date = startDate;
    }

    if (endDate) {
      params.end_date = endDate;
    }

    if (teamId) {
      params.team_id = teamId;
    }

    // For date filtering, use 'date' parameter if only one date is provided
    if (startDate && !endDate) {
      params.date = startDate;
    }

    return params;
  }, [currentPage, perPage, tournamentId, status, searchQuery, startDate, endDate, teamId]);

  // Fetch matches
  const { data: matchesData, isLoading, error } = useQuery({
    queryKey: ['matches', queryParams],
    queryFn: () => matchService.getAll(queryParams),
    refetchInterval: status === 'live' || status === 'in_progress' ? 30000 : false, // Auto-refresh live matches
  });

  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams();
    
    if (tournamentId) params.set('tournament_id', tournamentId);
    if (status !== 'all') params.set('status', status);
    if (searchQuery) params.set('search', searchQuery);
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);
    if (teamId) params.set('team_id', teamId);
    if (currentPage > 1) params.set('page', currentPage.toString());
    if (perPage !== 20) params.set('per_page', perPage.toString());

    setSearchParams(params, { replace: true });
  }, [tournamentId, status, searchQuery, startDate, endDate, teamId, currentPage, perPage, setSearchParams]);

  // Save view mode to localStorage
  useEffect(() => {
    localStorage.setItem('matchesViewMode', viewMode);
  }, [viewMode]);

  // Handle filter reset
  const handleResetFilters = () => {
    setTournamentId('');
    setStatus('all');
    setSearchQuery('');
    setStartDate('');
    setEndDate('');
    setTeamId('');
    setCurrentPage(1);
  };

  // Extract data from response
  const matches = matchesData?.data?.matches || matchesData?.data?.data?.matches || matchesData?.data || [];
  const pagination = matchesData?.data?.pagination || matchesData?.pagination || null;
  const tournaments = tournamentsData?.data?.data || tournamentsData?.data || [];

  // Group matches by date for calendar view
  const groupedMatches = useMemo(() => {
    if (viewMode !== 'calendar') return null;

    const grouped = {};
    matches.forEach((match) => {
      if (!match.match_date) return;
      
      const matchDate = new Date(match.match_date);
      const dateKey = matchDate.toISOString().split('T')[0];
      const formattedDate = formatDate(match.match_date, 'EEEE, MMMM dd, yyyy');
      
      if (!grouped[dateKey]) {
        grouped[dateKey] = {
          date: dateKey,
          formattedDate,
          isToday: isDateToday(match.match_date),
          isTomorrow: isDateTomorrow(match.match_date),
          matches: [],
        };
      }
      grouped[dateKey].matches.push(match);
    });

    // Sort by date
    return Object.values(grouped).sort((a, b) => 
      new Date(a.date) - new Date(b.date)
    );
  }, [matches, viewMode]);

  // Sort matches chronologically for list view
  const sortedMatches = useMemo(() => {
    if (viewMode === 'calendar') return [];
    
    return [...matches].sort((a, b) => {
      const dateA = new Date(a.match_date || 0);
      const dateB = new Date(b.match_date || 0);
      return dateA - dateB;
    });
  }, [matches, viewMode]);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={[{ label: 'Matches', path: '/matches' }]} />

        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Matches</h1>
          <p className="text-gray-600">Browse all matches and results</p>
        </div>

        {/* Filters and Controls */}
        <div className="bg-white rounded-lg shadow-md p-4 mb-6">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {/* Search and Filter Toggle */}
            <div className="flex-1 flex gap-4">
              <div className="flex-1 relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => {
                    setSearchQuery(e.target.value);
                    setCurrentPage(1);
                  }}
                  placeholder="Search matches..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>
              <Button
                variant="outline"
                onClick={() => setShowFilters(!showFilters)}
                className="flex items-center gap-2"
              >
                <Filter className="h-4 w-4" />
                Filters
              </Button>
            </div>

            {/* View Toggle */}
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2 border border-gray-300 rounded-lg overflow-hidden">
                <button
                  onClick={() => setViewMode('calendar')}
                  className={`p-2 ${viewMode === 'calendar' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                  title="Calendar View"
                >
                  <Calendar className="h-5 w-5" />
                </button>
                <button
                  onClick={() => setViewMode('list')}
                  className={`p-2 ${viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                  title="List View"
                >
                  <List className="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>

          {/* Expanded Filters */}
          {showFilters && (
            <div className="mt-4 pt-4 border-t border-gray-200">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {/* Tournament Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Tournament
                  </label>
                  <select
                    value={tournamentId}
                    onChange={(e) => {
                      setTournamentId(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="">All Tournaments</option>
                    {tournaments.map((tournament) => (
                      <option key={tournament.id} value={tournament.id}>
                        {tournament.name}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Status Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Status
                  </label>
                  <select
                    value={status}
                    onChange={(e) => {
                      setStatus(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="all">All Status</option>
                    <option value={MATCH_STATUS.SCHEDULED}>Scheduled</option>
                    <option value="live">Live</option>
                    <option value="in_progress">In Progress</option>
                    <option value={MATCH_STATUS.COMPLETED}>Completed</option>
                    <option value={MATCH_STATUS.CANCELLED}>Cancelled</option>
                    <option value={MATCH_STATUS.POSTPONED}>Postponed</option>
                  </select>
                </div>

                {/* Team Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Team ID
                  </label>
                  <input
                    type="number"
                    value={teamId}
                    onChange={(e) => {
                      setTeamId(e.target.value);
                      setCurrentPage(1);
                    }}
                    placeholder="Enter team ID"
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>

                {/* Start Date Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Start Date
                  </label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => {
                      setStartDate(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>

                {/* End Date Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    End Date
                  </label>
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => {
                      setEndDate(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>
              </div>

              {/* Reset Filters Button */}
              <div className="mt-4 flex justify-end">
                <Button
                  variant="outline"
                  onClick={handleResetFilters}
                  className="flex items-center gap-2"
                >
                  <X className="h-4 w-4" />
                  Reset Filters
                </Button>
              </div>
            </div>
          )}
        </div>

        {/* Results */}
        {isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load matches. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : (viewMode === 'calendar' ? groupedMatches : sortedMatches).length > 0 ? (
          <>
            {/* Calendar View */}
            {viewMode === 'calendar' && groupedMatches && (
              <div className="space-y-8 mb-6">
                {groupedMatches.map((group) => (
                  <div key={group.date} className="bg-white rounded-lg shadow-md p-6">
                    <div className="flex items-center gap-3 mb-4 pb-4 border-b border-gray-200">
                      <Clock className="h-5 w-5 text-primary-600" />
                      <h2 className="text-xl font-bold text-gray-900">
                        {group.isToday && (
                          <span className="text-primary-600 mr-2">Today •</span>
                        )}
                        {group.isTomorrow && (
                          <span className="text-blue-600 mr-2">Tomorrow •</span>
                        )}
                        {group.formattedDate}
                      </h2>
                      <span className="text-sm text-gray-500">
                        ({group.matches.length} {group.matches.length === 1 ? 'match' : 'matches'})
                      </span>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                      {group.matches.map((match) => (
                        <MatchCard key={match.id} match={match} />
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* List View */}
            {viewMode === 'list' && (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                {sortedMatches.map((match) => (
                  <MatchCard key={match.id} match={match} />
                ))}
              </div>
            )}

            {/* Pagination */}
            {pagination && (
              <Pagination
                currentPage={pagination.current_page || currentPage}
                totalPages={pagination.last_page || 1}
                onPageChange={setCurrentPage}
                perPage={perPage}
                onPerPageChange={(newPerPage) => {
                  setPerPage(newPerPage);
                  setCurrentPage(1);
                }}
                showingFrom={pagination.from}
                showingTo={pagination.to}
                totalItems={pagination.total}
              />
            )}
          </>
        ) : (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Clock className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              No matches found
            </h3>
            <p className="text-gray-600 mb-4">
              Try adjusting your filters or search query.
            </p>
            <Button onClick={handleResetFilters}>Reset Filters</Button>
          </div>
        )}
      </div>
    </div>
  );
};

export default MatchesList;
