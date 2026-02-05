import { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { Grid, List, Filter, X, Search, Calendar } from 'lucide-react';
import { tournamentService } from '../../api/tournaments';
import TournamentCard from '../../components/tournament/TournamentCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Pagination from '../../components/common/Pagination';
import Button from '../../components/common/Button';
import Breadcrumbs from '../../components/layout/Breadcrumbs';
import { formatDate } from '../../utils/dateUtils';
import { TOURNAMENT_STATUS } from '../../utils/constants';

const TournamentsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  
  // Filter states
  const [sportId, setSportId] = useState(searchParams.get('sport_id') || '');
  const [status, setStatus] = useState(searchParams.get('status') || 'all');
  const [searchQuery, setSearchQuery] = useState(searchParams.get('search') || '');
  const [startDate, setStartDate] = useState(searchParams.get('start_date') || '');
  const [endDate, setEndDate] = useState(searchParams.get('end_date') || '');
  
  // Sort states
  const [sortBy, setSortBy] = useState(searchParams.get('sort_by') || 'start_date');
  const [sortOrder, setSortOrder] = useState(searchParams.get('sort_order') || 'desc');
  
  // View and pagination
  const [viewMode, setViewMode] = useState(localStorage.getItem('tournamentsViewMode') || 'grid');
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page')) || 1);
  const [perPage, setPerPage] = useState(parseInt(searchParams.get('per_page')) || 20);
  const [showFilters, setShowFilters] = useState(false);

  // Fetch sports for filter dropdown
  const { data: sportsData } = useQuery({
    queryKey: ['sports'],
    queryFn: () => tournamentService.getSports(),
  });

  // Build query params
  const queryParams = useMemo(() => {
    const params = {
      page: currentPage,
      limit: perPage,
    };

    if (status !== 'all') {
      params.status = status;
    }

    if (sportId) {
      params.sport_id = sportId;
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

    if (sortBy) {
      params.sort_by = sortBy;
    }

    if (sortOrder) {
      params.sort_order = sortOrder;
    }

    return params;
  }, [currentPage, perPage, status, sportId, searchQuery, startDate, endDate, sortBy, sortOrder]);

  // Fetch tournaments
  const { data: tournamentsData, isLoading, error } = useQuery({
    queryKey: ['tournaments', queryParams],
    queryFn: () => tournamentService.getAll(queryParams),
  });

  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams();
    
    if (status !== 'all') params.set('status', status);
    if (sportId) params.set('sport_id', sportId);
    if (searchQuery) params.set('search', searchQuery);
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);
    if (sortBy) params.set('sort_by', sortBy);
    if (sortOrder) params.set('sort_order', sortOrder);
    if (currentPage > 1) params.set('page', currentPage.toString());
    if (perPage !== 20) params.set('per_page', perPage.toString());

    setSearchParams(params, { replace: true });
  }, [status, sportId, searchQuery, startDate, endDate, sortBy, sortOrder, currentPage, perPage, setSearchParams]);

  // Save view mode to localStorage
  useEffect(() => {
    localStorage.setItem('tournamentsViewMode', viewMode);
  }, [viewMode]);

  // Handle filter reset
  const handleResetFilters = () => {
    setSportId('');
    setStatus('all');
    setSearchQuery('');
    setStartDate('');
    setEndDate('');
    setCurrentPage(1);
  };

  // Extract data from response
  const tournaments = tournamentsData?.data?.data || tournamentsData?.data || [];
  const pagination = tournamentsData?.data?.pagination || tournamentsData?.pagination || null;
  const sports = sportsData?.data || [];

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={[{ label: 'Tournaments', path: '/tournaments' }]} />

        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Tournaments</h1>
          <p className="text-gray-600">Browse all available tournaments</p>
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
                  placeholder="Search tournaments..."
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

            {/* View Toggle and Sort */}
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2 border border-gray-300 rounded-lg overflow-hidden">
                <button
                  onClick={() => setViewMode('grid')}
                  className={`p-2 ${viewMode === 'grid' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                >
                  <Grid className="h-5 w-5" />
                </button>
                <button
                  onClick={() => setViewMode('list')}
                  className={`p-2 ${viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                >
                  <List className="h-5 w-5" />
                </button>
              </div>

              <select
                value={`${sortBy}_${sortOrder}`}
                onChange={(e) => {
                  const [field, order] = e.target.value.split('_');
                  setSortBy(field);
                  setSortOrder(order);
                  setCurrentPage(1);
                }}
                className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="start_date_desc">Start Date (Newest)</option>
                <option value="start_date_asc">Start Date (Oldest)</option>
                <option value="end_date_desc">End Date (Newest)</option>
                <option value="end_date_asc">End Date (Oldest)</option>
                <option value="status_asc">Status (A-Z)</option>
                <option value="status_desc">Status (Z-A)</option>
              </select>
            </div>
          </div>

          {/* Expanded Filters */}
          {showFilters && (
            <div className="mt-4 pt-4 border-t border-gray-200">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {/* Sport Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Sport
                  </label>
                  <select
                    value={sportId}
                    onChange={(e) => {
                      setSportId(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="">All Sports</option>
                    {sports.map((sport) => (
                      <option key={sport.id} value={sport.id}>
                        {sport.name}
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
                    <option value={TOURNAMENT_STATUS.ONGOING}>Ongoing</option>
                    <option value={TOURNAMENT_STATUS.UPCOMING}>Upcoming</option>
                    <option value={TOURNAMENT_STATUS.COMPLETED}>Completed</option>
                  </select>
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
            message="Failed to load tournaments. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : tournaments.length > 0 ? (
          <>
            {/* Tournament Grid/List */}
            <div
              className={
                viewMode === 'grid'
                  ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6'
                  : 'space-y-4 mb-6'
              }
            >
              {tournaments.map((tournament) => (
                <TournamentCard
                  key={tournament.id}
                  tournament={tournament}
                  viewMode={viewMode}
                />
              ))}
            </div>

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
            <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              No tournaments found
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

export default TournamentsList;
