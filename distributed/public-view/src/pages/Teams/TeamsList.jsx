import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search } from 'lucide-react';
import { teamService } from '../../api/teams';
import { tournamentService } from '../../api/tournaments';
import TeamCard from '../../components/team/TeamCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';

const TeamsList = () => {
  const [tournamentId, setTournamentId] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);

  // Fetch tournaments for filter dropdown
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournamentsForFilter'],
    queryFn: () => tournamentService.getAll({ limit: 100 }),
  });

  // Build query params
  const queryParams = {
    page: currentPage,
    limit: 20,
    ...(searchQuery.trim() && { search: searchQuery.trim() }),
  };

  // Fetch teams from tournament
  const { data: teamsData, isLoading, error } = useQuery({
    queryKey: ['tournamentTeams', tournamentId, queryParams],
    queryFn: () => teamService.getTournamentTeams(tournamentId, queryParams),
    enabled: !!tournamentId,
  });

  const teams = Array.isArray(teamsData?.data?.teams) 
    ? teamsData.data.teams 
    : Array.isArray(teamsData?.teams) 
      ? teamsData.teams 
      : Array.isArray(teamsData?.data) 
        ? teamsData.data 
        : [];
  const pagination = teamsData?.data?.pagination || teamsData?.pagination || null;
  const tournaments = tournamentsData?.data?.data || tournamentsData?.data || [];

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Teams</h1>
          <p className="text-gray-600">Browse teams by tournament</p>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-md p-4 mb-6">
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
                <option value="">Select a tournament</option>
                {tournaments.map((tournament) => (
                  <option key={tournament.id} value={tournament.id}>
                    {tournament.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Search
              </label>
              <div className="relative">
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
            </div>
          </div>
        </div>

        {/* Results */}
        {!tournamentId ? (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <p className="text-gray-600">Please select a tournament to view teams.</p>
          </div>
        ) : isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load teams. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : teams.length > 0 ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
              {teams.map((team) => (
                <TeamCard key={team.id} team={team} />
              ))}
            </div>

            {/* Simple Pagination */}
            {pagination && pagination.last_page > 1 && (
              <div className="flex justify-center gap-2">
                <button
                  onClick={() => setCurrentPage(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                  Previous
                </button>
                <span className="px-4 py-2 flex items-center">
                  Page {currentPage} of {pagination.last_page}
                </span>
                <button
                  onClick={() => setCurrentPage(currentPage + 1)}
                  disabled={currentPage >= pagination.last_page}
                  className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                  Next
                </button>
              </div>
            )}
          </>
        ) : (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <p className="text-gray-600">No teams found in this tournament.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default TeamsList;
