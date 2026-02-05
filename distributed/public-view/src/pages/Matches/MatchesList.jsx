import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search } from 'lucide-react';
import { matchService } from '../../api/matches';
import MatchCard from '../../components/match/MatchCard';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';

const MatchesList = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);

  // Build query params
  const queryParams = {
    page: currentPage,
    limit: 20,
    ...(searchQuery.trim() && { search: searchQuery.trim() }),
  };

  // Fetch matches
  const { data: matchesData, isLoading, error } = useQuery({
    queryKey: ['matches', queryParams],
    queryFn: () => matchService.getAll(queryParams),
    refetchInterval: 30000, // Auto-refresh every 30 seconds
  });

  const matches = matchesData?.data?.matches || matchesData?.data?.data?.matches || matchesData?.data || [];
  const pagination = matchesData?.data?.pagination || matchesData?.pagination || null;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Matches</h1>
          <p className="text-gray-600">Browse all matches and results</p>
        </div>

        {/* Search */}
        <div className="bg-white rounded-lg shadow-md p-4 mb-6">
          <div className="relative">
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
        </div>

        {/* Results */}
        {isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load matches. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : matches.length > 0 ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
              {matches.map((match) => (
                <MatchCard key={match.id} match={match} />
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
            <p className="text-gray-600">No matches found.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default MatchesList;
