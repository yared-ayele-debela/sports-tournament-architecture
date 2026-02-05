import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, Filter } from 'lucide-react';
import { teamService } from '../../api/teams';
import PlayerCard from './PlayerCard';
import Loading from '../common/Loading';
import ErrorMessage from '../common/ErrorMessage';
import Pagination from '../common/Pagination';
import Button from '../common/Button';

const TeamPlayers = ({ teamId }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [positionFilter, setPositionFilter] = useState('all');
  const [sortBy, setSortBy] = useState('name');
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(20);

  // Fetch players
  const { data: playersData, isLoading, error } = useQuery({
    queryKey: ['teamPlayers', teamId, currentPage, perPage],
    queryFn: () => teamService.getPlayers(teamId, { page: currentPage, limit: perPage }),
    enabled: !!teamId,
  });

  const players = playersData?.data?.data || playersData?.data || [];
  const pagination = playersData?.data?.pagination || playersData?.pagination || null;

  // Get unique positions
  const positions = useMemo(() => {
    const uniquePositions = [...new Set(players.map(p => p.position).filter(Boolean))];
    return uniquePositions.sort();
  }, [players]);

  // Filter and sort players
  const filteredAndSortedPlayers = useMemo(() => {
    let filtered = [...players];

    // Apply search filter
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(player =>
        (player.name || player.full_name || '').toLowerCase().includes(query) ||
        (player.position || '').toLowerCase().includes(query)
      );
    }

    // Apply position filter
    if (positionFilter !== 'all') {
      filtered = filtered.filter(player => player.position === positionFilter);
    }

    // Sort players
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'name':
          return (a.name || a.full_name || '').localeCompare(b.name || b.full_name || '');
        case 'position':
          return (a.position || '').localeCompare(b.position || '');
        case 'jersey_number':
          return (a.jersey_number || 999) - (b.jersey_number || 999);
        default:
          return 0;
      }
    });

    return filtered;
  }, [players, searchQuery, positionFilter, sortBy]);

  if (isLoading) {
    return <Loading />;
  }

  if (error) {
    return (
      <ErrorMessage
        message="Failed to load players. Please try again later."
        onRetry={() => window.location.reload()}
      />
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h3 className="text-xl font-bold text-gray-900">Players</h3>

        {/* Search and Filters */}
        <div className="flex flex-col sm:flex-row gap-4 flex-1 max-w-2xl">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search players..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <select
            value={positionFilter}
            onChange={(e) => setPositionFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="all">All Positions</option>
            {positions.map((position) => (
              <option key={position} value={position}>
                {position}
              </option>
            ))}
          </select>

          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="name">Sort by Name</option>
            <option value="position">Sort by Position</option>
            <option value="jersey_number">Sort by Jersey Number</option>
          </select>
        </div>
      </div>

      {/* Players Grid */}
      {filteredAndSortedPlayers.length > 0 ? (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            {filteredAndSortedPlayers.map((player) => (
              <PlayerCard key={player.id} player={player} />
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
        <div className="text-center py-12 text-gray-500">
          <p>No players found.</p>
          {searchQuery && (
            <Button
              variant="outline"
              onClick={() => setSearchQuery('')}
              className="mt-4"
            >
              Clear Search
            </Button>
          )}
        </div>
      )}
    </div>
  );
};

export default TeamPlayers;
