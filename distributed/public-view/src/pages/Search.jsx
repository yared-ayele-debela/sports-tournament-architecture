import { useState, useEffect, useMemo } from 'react';
import { useSearchParams, useNavigate, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { 
  Search, 
  Trophy, 
  Users, 
  Activity, 
  User,
  Calendar,
  MapPin,
  ArrowRight,
  X,
  Clock,
  TrendingUp
} from 'lucide-react';
import { searchService } from '../api/search';
import Loading from '../components/common/Loading';
import ErrorMessage from '../components/common/ErrorMessage';
import Breadcrumbs from '../components/layout/Breadcrumbs';
import Badge from '../components/common/Badge';
import Card from '../components/common/Card';
import { formatDate, formatDateTime } from '../utils/dateUtils';
import { STATUS_COLORS } from '../utils/constants';
import { debounce } from '../utils/formatUtils';

const resultTypes = [
  { id: 'all', label: 'All', icon: Search },
  { id: 'tournaments', label: 'Tournaments', icon: Trophy },
  { id: 'teams', label: 'Teams', icon: Users },
  { id: 'matches', label: 'Matches', icon: Activity },
  { id: 'players', label: 'Players', icon: User },
];

const POPULAR_SEARCHES = [
  'Premier League',
  'Champions League',
  'World Cup',
  'Manchester United',
  'Barcelona',
];

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');
  const [activeTab, setActiveTab] = useState(searchParams.get('type') || 'all');
  const [dateFilter, setDateFilter] = useState(searchParams.get('date') || '');
  const [tournamentFilter, setTournamentFilter] = useState(searchParams.get('tournament') || '');
  const [recentSearches, setRecentSearches] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);

  // Load recent searches from localStorage
  useEffect(() => {
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      try {
        setRecentSearches(JSON.parse(saved));
      } catch (e) {
        console.error('Failed to parse recent searches', e);
      }
    }
  }, []);

  // Update URL when search query changes
  useEffect(() => {
    if (searchQuery) {
      const params = new URLSearchParams();
      params.set('q', searchQuery);
      if (activeTab !== 'all') params.set('type', activeTab);
      if (dateFilter) params.set('date', dateFilter);
      if (tournamentFilter) params.set('tournament', tournamentFilter);
      setSearchParams(params, { replace: true });
    }
  }, [searchQuery, activeTab, dateFilter, tournamentFilter, setSearchParams]);

  // Fetch search results
  const { data: searchData, isLoading, error } = useQuery({
    queryKey: ['search', searchQuery, activeTab, dateFilter, tournamentFilter],
    queryFn: async () => {
      if (!searchQuery.trim()) return null;

      // Save to recent searches
      if (searchQuery.trim()) {
        const updated = [
          searchQuery.trim(),
          ...recentSearches.filter((s) => s !== searchQuery.trim())
        ].slice(0, 10);
        setRecentSearches(updated);
        localStorage.setItem('recentSearches', JSON.stringify(updated));
      }

      // Perform search based on active tab
      if (activeTab === 'all') {
        return await searchService.searchAll(searchQuery);
      } else if (activeTab === 'tournaments') {
        return await searchService.searchTournaments(searchQuery);
      } else if (activeTab === 'teams') {
        return await searchService.searchTeams(searchQuery);
      } else if (activeTab === 'matches') {
        return await searchService.searchMatches(searchQuery, {
          date: dateFilter || undefined,
          tournament_id: tournamentFilter || undefined,
        });
      }
      return null;
    },
    enabled: !!searchQuery.trim(),
    staleTime: 30000, // 30 seconds
  });

  // Debounced search handler
  const debouncedSearch = useMemo(
    () => debounce((query) => {
      setSearchQuery(query);
      setShowSuggestions(false);
    }, 500),
    []
  );

  const handleSearchChange = (e) => {
    const value = e.target.value;
    setSearchQuery(value);
    setShowSuggestions(true);
    if (value.trim()) {
      debouncedSearch(value);
    }
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      setShowSuggestions(false);
      debouncedSearch(searchQuery);
    }
  };

  const handleSuggestionClick = (suggestion) => {
    setSearchQuery(suggestion);
    setShowSuggestions(false);
    debouncedSearch(suggestion);
  };

  const handlePopularSearch = (term) => {
    setSearchQuery(term);
    setShowSuggestions(false);
    debouncedSearch(term);
  };

  const clearFilters = () => {
    setDateFilter('');
    setTournamentFilter('');
  };

  // Extract results from API response
  const results = useMemo(() => {
    if (!searchData?.data) return { tournaments: [], teams: [], matches: [], players: [] };

    const data = searchData.data;

    if (activeTab === 'all') {
      return {
        tournaments: data.tournaments || [],
        teams: data.teams || [],
        matches: data.matches || [],
        players: [], // Players not available in meta search
      };
    } else if (activeTab === 'tournaments') {
      return {
        tournaments: data.tournaments || data.data || [],
        teams: [],
        matches: [],
        players: [],
      };
    } else if (activeTab === 'teams') {
      return {
        tournaments: [],
        teams: data.teams || data.data || [],
        matches: [],
        players: [], // Players might be in team data
      };
    } else if (activeTab === 'matches') {
      return {
        tournaments: [],
        teams: [],
        matches: data.matches || data.data || [],
        players: [],
      };
    }

    return { tournaments: [], teams: [], matches: [], players: [] };
  }, [searchData, activeTab]);

  const totalResults = results.tournaments.length + results.teams.length + results.matches.length + results.players.length;

  const breadcrumbs = [
    { label: 'Home', path: '/' },
    { label: 'Search', path: '/search' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={breadcrumbs} />

        {/* Search Header */}
        <div className="mb-8">
          <h1 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
            Search
          </h1>

          {/* Search Bar */}
          <form onSubmit={handleSearchSubmit} className="relative mb-4">
            <div className="relative">
              <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={handleSearchChange}
                onFocus={() => setShowSuggestions(true)}
                placeholder="Search tournaments, teams, players, matches..."
                className="w-full pl-12 pr-12 py-4 text-lg border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                autoFocus
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => {
                    setSearchQuery('');
                    setShowSuggestions(false);
                  }}
                  className="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <X className="h-6 w-6" />
                </button>
              )}
            </div>

            {/* Suggestions Dropdown */}
            {showSuggestions && !searchQuery && (
              <div className="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-96 overflow-y-auto">
                {/* Recent Searches */}
                {recentSearches.length > 0 && (
                  <div>
                    <div className="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      Recent Searches
                    </div>
                    {recentSearches.map((search, index) => (
                      <button
                        key={index}
                        onClick={() => handleSuggestionClick(search)}
                        className="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2"
                      >
                        <Clock className="h-4 w-4 text-gray-400" />
                        {search}
                      </button>
                    ))}
                  </div>
                )}

                {/* Popular Searches */}
                <div>
                  <div className="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <TrendingUp className="h-4 w-4" />
                    Popular Searches
                  </div>
                  {POPULAR_SEARCHES.map((search, index) => (
                    <button
                      key={index}
                      onClick={() => handlePopularSearch(search)}
                      className="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                      {search}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </form>

          {/* Filters */}
          {searchQuery && (
            <div className="flex flex-wrap gap-4 mb-4">
              {activeTab === 'matches' && (
                <>
                  <input
                    type="date"
                    value={dateFilter}
                    onChange={(e) => setDateFilter(e.target.value)}
                    placeholder="Filter by date"
                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  />
                  <input
                    type="text"
                    value={tournamentFilter}
                    onChange={(e) => setTournamentFilter(e.target.value)}
                    placeholder="Filter by tournament ID"
                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  />
                </>
              )}
              {(dateFilter || tournamentFilter) && (
                <button
                  onClick={clearFilters}
                  className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                  Clear Filters
                </button>
              )}
            </div>
          )}
        </div>

        {/* Results Tabs */}
        {searchQuery && (
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
            <div className="border-b border-gray-200 dark:border-gray-700">
              <nav className="flex overflow-x-auto">
                {resultTypes.map((type) => {
                  const Icon = type.icon;
                  const count = type.id === 'all' 
                    ? totalResults 
                    : type.id === 'tournaments' 
                    ? results.tournaments.length
                    : type.id === 'teams'
                    ? results.teams.length
                    : type.id === 'matches'
                    ? results.matches.length
                    : results.players.length;

                  return (
                    <button
                      key={type.id}
                      onClick={() => setActiveTab(type.id)}
                      className={`
                        flex items-center gap-2 px-6 py-4 font-medium text-sm whitespace-nowrap
                        border-b-2 transition-colors relative
                        ${
                          activeTab === type.id
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        }
                      `}
                    >
                      <Icon className="h-5 w-5" />
                      {type.label}
                      {count > 0 && (
                        <span className="ml-2 px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded-full">
                          {count}
                        </span>
                      )}
                    </button>
                  );
                })}
              </nav>
            </div>

            {/* Results Content */}
            <div className="p-6">
              {isLoading ? (
                <Loading />
              ) : error ? (
                <ErrorMessage message="Failed to load search results" />
              ) : !searchQuery.trim() ? (
                <div className="text-center py-12">
                  <Search className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-500 dark:text-gray-400">
                    Enter a search term to find tournaments, teams, players, and matches.
                  </p>
                </div>
              ) : totalResults === 0 ? (
                <div className="text-center py-12">
                  <Search className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-600 dark:text-gray-400 mb-2">
                    No results found for "{searchQuery}"
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-500">
                    Try different keywords or check your spelling.
                  </p>
                </div>
              ) : (
                <div className="space-y-6">
                  {/* All Results */}
                  {activeTab === 'all' && (
                    <>
                      {results.tournaments.length > 0 && (
                        <div>
                          <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <Trophy className="h-5 w-5" />
                              Tournaments ({results.tournaments.length})
                            </h2>
                            <Link
                              to={`/search?q=${encodeURIComponent(searchQuery)}&type=tournaments`}
                              className="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            >
                              View all <ArrowRight className="h-4 w-4" />
                            </Link>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {results.tournaments.slice(0, 6).map((tournament) => (
                              <TournamentResultCard key={tournament.id} tournament={tournament} />
                            ))}
                          </div>
                        </div>
                      )}

                      {results.teams.length > 0 && (
                        <div>
                          <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <Users className="h-5 w-5" />
                              Teams ({results.teams.length})
                            </h2>
                            <Link
                              to={`/search?q=${encodeURIComponent(searchQuery)}&type=teams`}
                              className="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            >
                              View all <ArrowRight className="h-4 w-4" />
                            </Link>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {results.teams.slice(0, 6).map((team) => (
                              <TeamResultCard key={team.id} team={team} />
                            ))}
                          </div>
                        </div>
                      )}

                      {results.matches.length > 0 && (
                        <div>
                          <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <Activity className="h-5 w-5" />
                              Matches ({results.matches.length})
                            </h2>
                            <Link
                              to={`/search?q=${encodeURIComponent(searchQuery)}&type=matches`}
                              className="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            >
                              View all <ArrowRight className="h-4 w-4" />
                            </Link>
                          </div>
                          <div className="space-y-4">
                            {results.matches.slice(0, 5).map((match) => (
                              <MatchResultCard key={match.id} match={match} />
                            ))}
                          </div>
                        </div>
                      )}

                      {results.players.length > 0 && (
                        <div>
                          <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <User className="h-5 w-5" />
                              Players ({results.players.length})
                            </h2>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {results.players.map((player) => (
                              <PlayerResultCard key={player.id} player={player} />
                            ))}
                          </div>
                        </div>
                      )}
                    </>
                  )}

                  {/* Tournament Results */}
                  {activeTab === 'tournaments' && (
                    <div>
                      <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Tournament Results ({results.tournaments.length})
                      </h2>
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {results.tournaments.map((tournament) => (
                          <TournamentResultCard key={tournament.id} tournament={tournament} />
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Team Results */}
                  {activeTab === 'teams' && (
                    <div>
                      <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Team Results ({results.teams.length})
                      </h2>
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {results.teams.map((team) => (
                          <TeamResultCard key={team.id} team={team} />
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Match Results */}
                  {activeTab === 'matches' && (
                    <div>
                      <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Match Results ({results.matches.length})
                      </h2>
                      <div className="space-y-4">
                        {results.matches.map((match) => (
                          <MatchResultCard key={match.id} match={match} />
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Player Results */}
                  {activeTab === 'players' && (
                    <div>
                      <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Player Results ({results.players.length})
                      </h2>
                      {results.players.length === 0 ? (
                        <p className="text-gray-500 dark:text-gray-400">
                          Player search is not yet available. Try searching for teams to find players.
                        </p>
                      ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                          {results.players.map((player) => (
                            <PlayerResultCard key={player.id} player={player} />
                          ))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Empty State */}
        {!searchQuery && (
          <div className="text-center py-12">
            <Search className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-500 dark:text-gray-400 mb-2">
              Start searching for tournaments, teams, players, and matches
            </p>
            {recentSearches.length > 0 && (
              <div className="mt-6">
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">Recent Searches:</p>
                <div className="flex flex-wrap justify-center gap-2">
                  {recentSearches.slice(0, 5).map((search, index) => (
                    <button
                      key={index}
                      onClick={() => handlePopularSearch(search)}
                      className="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                      {search}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

// Tournament Result Card
const TournamentResultCard = ({ tournament }) => {
  return (
    <Card
      hover
      onClick={() => window.location.href = `/tournaments/${tournament.id}`}
      className="h-full"
    >
      <div className="p-4">
        <div className="flex items-start gap-3 mb-3">
          {tournament.logo && (
            <img
              src={tournament.logo}
              alt={tournament.name}
              className="h-12 w-12 object-contain flex-shrink-0"
            />
          )}
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-2">
              {tournament.name}
            </h3>
            {tournament.sport && (
              <Badge color="blue" className="text-xs">
                {tournament.sport.name || tournament.sport}
              </Badge>
            )}
          </div>
        </div>
        <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
          {tournament.start_date && tournament.end_date && (
            <div className="flex items-center gap-1">
              <Calendar className="h-4 w-4" />
              <span>
                {formatDate(tournament.start_date)} - {formatDate(tournament.end_date)}
              </span>
            </div>
          )}
          {tournament.status && (
            <Badge color={STATUS_COLORS[tournament.status] || 'gray'} className="text-xs">
              {tournament.status}
            </Badge>
          )}
        </div>
      </div>
    </Card>
  );
};

// Team Result Card
const TeamResultCard = ({ team }) => {
  return (
    <Card
      hover
      onClick={() => window.location.href = `/teams/${team.id}`}
      className="h-full"
    >
      <div className="p-4">
        <div className="flex items-center gap-3 mb-3">
          {team.logo && (
            <img
              src={team.logo}
              alt={team.name}
              className="h-12 w-12 object-contain flex-shrink-0"
            />
          )}
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
              {team.name}
            </h3>
            {team.tournament && (
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {team.tournament.name || team.tournament}
              </p>
            )}
          </div>
        </div>
      </div>
    </Card>
  );
};

// Match Result Card
const MatchResultCard = ({ match }) => {
  const isLive = match.status === 'live' || match.status === 'in_progress';
  const isCompleted = match.status === 'completed';

  return (
    <Card
      hover
      onClick={() => window.location.href = `/matches/${match.id}`}
      className="h-full"
    >
      <div className="p-4">
        <div className="flex items-center justify-between mb-3">
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-2">
              <span className="font-semibold text-gray-900 dark:text-white">
                {match.home_team?.name || 'TBD'}
              </span>
              {isCompleted && (
                <>
                  <span className="text-2xl font-bold text-gray-900 dark:text-white">
                    {match.home_score ?? 0}
                  </span>
                  <span className="text-gray-500">-</span>
                  <span className="text-2xl font-bold text-gray-900 dark:text-white">
                    {match.away_score ?? 0}
                  </span>
                </>
              )}
              {isLive && (
                <>
                  <span className="text-2xl font-bold text-red-600">
                    {match.home_score ?? 0}
                  </span>
                  <span className="text-gray-500">-</span>
                  <span className="text-2xl font-bold text-red-600">
                    {match.away_score ?? 0}
                  </span>
                </>
              )}
              <span className="font-semibold text-gray-900 dark:text-white">
                {match.away_team?.name || 'TBD'}
              </span>
            </div>
            <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
              {match.match_date && (
                <div className="flex items-center gap-1">
                  <Calendar className="h-4 w-4" />
                  {formatDateTime(match.match_date)}
                </div>
              )}
              {match.venue && (
                <div className="flex items-center gap-1">
                  <MapPin className="h-4 w-4" />
                  {match.venue.name || match.venue}
                </div>
              )}
            </div>
          </div>
          {isLive && (
            <Badge color="red" className="ml-4">
              LIVE
            </Badge>
          )}
          {isCompleted && (
            <Badge color="green" className="ml-4">
              Completed
            </Badge>
          )}
        </div>
      </div>
    </Card>
  );
};

// Player Result Card
const PlayerResultCard = ({ player }) => {
  return (
    <Card
      hover
      onClick={() => player.team_id && (window.location.href = `/teams/${player.team_id}`)}
      className="h-full"
    >
      <div className="p-4">
        <div className="flex items-center gap-3">
          {player.photo && (
            <img
              src={player.photo}
              alt={player.name}
              className="h-12 w-12 object-cover rounded-full flex-shrink-0"
            />
          )}
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
              {player.name}
            </h3>
            {player.team && (
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {player.team.name || player.team}
              </p>
            )}
            {player.position && (
              <Badge color="blue" className="text-xs mt-1">
                {player.position}
              </Badge>
            )}
          </div>
        </div>
      </div>
    </Card>
  );
};

export default SearchPage;
