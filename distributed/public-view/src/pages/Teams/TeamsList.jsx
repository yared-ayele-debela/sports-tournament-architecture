import { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { Grid, List, Filter, X, Search } from 'lucide-react';
import { teamService } from '../../api/teams';
import { tournamentService } from '../../api/tournaments';
import TeamCard from '../../components/team/TeamCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Pagination from '../../components/common/Pagination';
import Button from '../../components/common/Button';
import Breadcrumbs from '../../components/layout/Breadcrumbs';

const TeamsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  
  // Filter states
  const [tournamentId, setTournamentId] = useState(searchParams.get('tournament_id') || '');
  const [sportId, setSportId] = useState(searchParams.get('sport_id') || '');
  const [searchQuery, setSearchQuery] = useState(searchParams.get('search') || '');
  
  // Sort states
  const [sortBy, setSortBy] = useState(searchParams.get('sort_by') || 'name');
  const [sortOrder, setSortOrder] = useState(searchParams.get('sort_order') || 'asc');
  
  // View and pagination
  const [viewMode, setViewMode] = useState(localStorage.getItem('teamsViewMode') || 'grid');
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page')) || 1);
  const [perPage, setPerPage] = useState(parseInt(searchParams.get('per_page')) || 20);
  const [showFilters, setShowFilters] = useState(false);

  // Fetch tournaments for filter dropdown
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournamentsForFilter'],
    queryFn: () => tournamentService.getAll({ limit: 100 }),
  });

  // Fetch sports for filter dropdown
  const { data: sportsData } = useQuery({
    queryKey: ['sports'],
    queryFn: () => tournamentService.getSports(),
  });

  // Build query params for teams
  const queryParams = useMemo(() => {
    const params = {
      page: currentPage,
      limit: perPage,
    };

    if (searchQuery.trim()) {
      params.search = searchQuery.trim();
    }

    return params;
  }, [currentPage, perPage, searchQuery]);

  // Fetch teams from tournament
  const { data: teamsData, isLoading, error } = useQuery({
    queryKey: ['tournamentTeams', tournamentId, queryParams],
    queryFn: () => teamService.getTournamentTeams(tournamentId, queryParams),
    enabled: !!tournamentId, // Only fetch if tournament is selected
  });

  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams();
    
    if (tournamentId) params.set('tournament_id', tournamentId);
    if (sportId) params.set('sport_id', sportId);
    if (searchQuery) params.set('search', searchQuery);
    if (sortBy) params.set('sort_by', sortBy);
    if (sortOrder) params.set('sort_order', sortOrder);
    if (currentPage > 1) params.set('page', currentPage.toString());
    if (perPage !== 20) params.set('per_page', perPage.toString());

    setSearchParams(params, { replace: true });
  }, [tournamentId, sportId, searchQuery, sortBy, sortOrder, currentPage, perPage, setSearchParams]);

  // Save view mode to localStorage
  useEffect(() => {
    localStorage.setItem('teamsViewMode', viewMode);
  }, [viewMode]);

  // Handle filter reset
  const handleResetFilters = () => {
    setTournamentId('');
    setSportId('');
    setSearchQuery('');
    setCurrentPage(1);
  };

  // Extract data from response
  // Team service returns: { success: true, data: { teams: [...], pagination: {...} } }
  const teams = Array.isArray(teamsData?.data?.teams) 
    ? teamsData.data.teams 
    : Array.isArray(teamsData?.teams) 
      ? teamsData.teams 
      : Array.isArray(teamsData?.data) 
        ? teamsData.data 
        : [];
  const pagination = teamsData?.data?.pagination || teamsData?.pagination || null;
  const tournaments = tournamentsData?.data?.data || tournamentsData?.data || [];
  const sports = sportsData?.data || [];

  // Filter tournaments by sport if sport filter is selected
  const filteredTournaments = useMemo(() => {
    if (!sportId) return tournaments;
    return tournaments.filter(t => t.sport_id === parseInt(sportId));
  }, [tournaments, sportId]);

  // Sort teams
  const sortedTeams = useMemo(() => {
    const teamsToSort = [...teams];
    
    // Add tournament name to each team for display
    const teamsWithTournament = teamsToSort.map(team => {
      const tournament = tournaments.find(t => t.id === team.tournament_id);
      return {
        ...team,
        tournament_name: tournament?.name || '',
      };
    });

    // Apply search filter
    let filtered = teamsWithTournament;
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(team =>
        team.name.toLowerCase().includes(query) ||
        team.tournament_name.toLowerCase().includes(query)
      );
    }

    // Sort teams
    filtered.sort((a, b) => {
      let comparison = 0;
      
      switch (sortBy) {
        case 'name':
          comparison = a.name.localeCompare(b.name);
          break;
        case 'wins':
          comparison = (a.match_stats?.wins || 0) - (b.match_stats?.wins || 0);
          break;
        case 'losses':
          comparison = (a.match_stats?.losses || 0) - (b.match_stats?.losses || 0);
          break;
        case 'tournament':
          comparison = a.tournament_name.localeCompare(b.tournament_name);
          break;
        default:
          comparison = 0;
      }
      
      return sortOrder === 'asc' ? comparison : -comparison;
    });

    return filtered;
  }, [teams, tournaments, searchQuery, sortBy, sortOrder]);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={[{ label: 'Teams', path: '/teams' }]} />

        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Teams</h1>
          <p className="text-gray-600">Browse all teams across tournaments</p>
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
                  placeholder="Search teams..."
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
                <option value="wins_desc">Wins (High to Low)</option>
                <option value="wins_asc">Wins (Low to High)</option>
                <option value="losses_desc">Losses (High to Low)</option>
                <option value="losses_asc">Losses (Low to High)</option>
                <option value="tournament_asc">Tournament (A-Z)</option>
                <option value="tournament_desc">Tournament (Z-A)</option>
              </select>
            </div>
          </div>

          {/* Expanded Filters */}
          {showFilters && (
            <div className="mt-4 pt-4 border-t border-gray-200">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    {filteredTournaments.map((tournament) => (
                      <option key={tournament.id} value={tournament.id}>
                        {tournament.name}
                      </option>
                    ))}
                  </select>
                </div>

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
                      // Reset tournament if sport changes
                      if (e.target.value) {
                        setTournamentId('');
                      }
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
        {!tournamentId ? (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <p className="text-gray-600 mb-4">
              Please select a tournament to view teams.
            </p>
            <p className="text-sm text-gray-500">
              Use the filters above to select a tournament.
            </p>
          </div>
        ) : isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load teams. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : sortedTeams.length > 0 ? (
          <>
            {/* Team Grid/List */}
            <div
              className={
                viewMode === 'grid'
                  ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6'
                  : 'space-y-4 mb-6'
              }
            >
              {sortedTeams.map((team) => (
                <TeamCard key={team.id} team={team} viewMode={viewMode} />
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
            <p className="text-gray-600 mb-4">
              No teams found in this tournament.
            </p>
            <p className="text-sm text-gray-500 mb-4">
              Try adjusting your filters or search query.
            </p>
            <Button onClick={handleResetFilters}>Reset Filters</Button>
          </div>
        )}
      </div>
    </div>
  );
};

export default TeamsList;
