import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Filter } from 'lucide-react';
import { teamService } from '../../api/teams';
import MatchCard from '../match/MatchCard';
import Loading from '../common/Loading';
import ErrorMessage from '../common/ErrorMessage';
import Pagination from '../common/Pagination';
import { formatDate, isDateToday, isDateTomorrow } from '../../utils/dateUtils';
import Badge from '../common/Badge';

const TeamMatches = ({ teamId, team }) => {
  const [statusFilter, setStatusFilter] = useState('all');
  const [dateFilter, setDateFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(20);

  // Build query params
  const queryParams = useMemo(() => {
    const params = {
      page: currentPage,
      limit: perPage,
    };

    if (statusFilter !== 'all') {
      params.status = statusFilter;
    }

    if (dateFilter) {
      params.date = dateFilter;
    }

    return params;
  }, [currentPage, perPage, statusFilter, dateFilter]);

  // Fetch team matches
  const { data: matchesData, isLoading, error } = useQuery({
    queryKey: ['teamMatches', teamId, queryParams],
    queryFn: () => teamService.getMatches(teamId, queryParams),
    enabled: !!teamId,
  });

  const matches = matchesData?.data?.data || matchesData?.data || matchesData?.matches || [];
  const pagination = matchesData?.data?.pagination || matchesData?.pagination || null;

  // Enhance matches with result information
  const enhancedMatches = useMemo(() => {
    return matches.map(match => {
      const isHome = match.home_team_id === team.id || match.home_team?.id === team.id;
      const teamScore = isHome ? match.home_score : match.away_score;
      const opponentScore = isHome ? match.away_score : match.home_score;
      
      let result = null;
      if (match.status === 'completed') {
        if (teamScore > opponentScore) result = 'Win';
        else if (teamScore < opponentScore) result = 'Loss';
        else result = 'Draw';
      }

      return {
        ...match,
        is_home: isHome,
        result,
        opponent_team: isHome ? match.away_team : match.home_team,
      };
    });
  }, [matches, team.id]);

  // Group matches by date
  const groupedMatches = useMemo(() => {
    const grouped = {};
    enhancedMatches.forEach((match) => {
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

    return Object.values(grouped).sort((a, b) => 
      new Date(b.date) - new Date(a.date)
    );
  }, [enhancedMatches]);

  if (isLoading) {
    return <Loading />;
  }

  if (error) {
    return (
      <ErrorMessage
        message="Failed to load matches. Please try again later."
        onRetry={() => window.location.reload()}
      />
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h3 className="text-xl font-bold text-gray-900">Matches</h3>

        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-4">
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="all">All Status</option>
            <option value="completed">Completed</option>
            <option value="scheduled">Upcoming</option>
            <option value="live">Live</option>
            <option value="in_progress">In Progress</option>
          </select>

          <input
            type="date"
            value={dateFilter}
            onChange={(e) => {
              setDateFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            placeholder="Filter by date"
          />
        </div>
      </div>

      {/* Matches List */}
      {groupedMatches.length > 0 ? (
        <>
          <div className="space-y-6 mb-6">
            {groupedMatches.map((group) => (
              <div key={group.date}>
                <div className="flex items-center gap-3 mb-4 pb-2 border-b border-gray-200">
                  <Calendar className="h-5 w-5 text-primary-600" />
                  <h4 className="text-lg font-semibold text-gray-900">
                    {group.isToday && <span className="text-primary-600 mr-2">Today •</span>}
                    {group.isTomorrow && <span className="text-blue-600 mr-2">Tomorrow •</span>}
                    {group.formattedDate}
                  </h4>
                </div>

                <div className="space-y-4">
                  {group.matches.map((match) => (
                    <div key={match.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="flex-1">
                          <div className="flex items-center gap-4 mb-2">
                            <span className="text-sm text-gray-600">
                              {formatDate(match.match_date, 'HH:mm')}
                            </span>
                            {match.venue && (
                              <span className="text-sm text-gray-600">
                                {match.venue.name}
                              </span>
                            )}
                            <Badge
                              variant={match.is_home ? 'info' : 'default'}
                              size="sm"
                            >
                              {match.is_home ? 'Home' : 'Away'}
                            </Badge>
                          </div>
                          <div className="flex items-center gap-3">
                            <span className="font-medium text-gray-900">
                              vs {match.opponent_team?.name || 'TBD'}
                            </span>
                            {match.result && (
                              <Badge
                                variant={
                                  match.result === 'Win' ? 'success' :
                                  match.result === 'Loss' ? 'danger' : 'default'
                                }
                                size="sm"
                              >
                                {match.result}
                              </Badge>
                            )}
                          </div>
                        </div>
                        {match.status === 'completed' && (
                          <div className="text-2xl font-bold text-gray-900">
                            {match.home_score ?? 0} - {match.away_score ?? 0}
                          </div>
                        )}
                        <a
                          href={`/matches/${match.id}`}
                          className="text-primary-600 hover:text-primary-700 font-medium text-sm whitespace-nowrap"
                        >
                          View Match →
                        </a>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
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
          <p>No matches found.</p>
        </div>
      )}
    </div>
  );
};

export default TeamMatches;
