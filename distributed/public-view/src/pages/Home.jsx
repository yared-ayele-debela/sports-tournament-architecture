import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { useMemo } from 'react';
import { Calendar, Trophy, Users, Activity, ArrowRight } from 'lucide-react';
import { tournamentService } from '../api/tournaments';
import { matchService } from '../api/matches';
import TournamentCard from '../components/tournament/TournamentCard';
import MatchCard from '../components/match/MatchCard';
import Loading from '../components/common/Loading';
import ErrorMessage from '../components/common/ErrorMessage';
import SearchBar from '../components/common/SearchBar';
import { getCurrentDateTime, formatDate } from '../utils/dateUtils';

const Home = () => {
  const navigate = useNavigate();

  // Fetch featured tournaments
  const { data: featuredData, isLoading: featuredLoading, error: featuredError } = useQuery({
    queryKey: ['featuredTournaments'],
    queryFn: () => tournamentService.getFeatured(),
  });

  // Fetch upcoming tournaments
  const { data: upcomingTournamentsData, isLoading: upcomingLoading } = useQuery({
    queryKey: ['upcomingTournaments'],
    queryFn: () => tournamentService.getUpcoming(),
  });

  // Fetch live matches
  const { data: liveMatchesData, isLoading: liveMatchesLoading, error: liveMatchesError } = useQuery({
    queryKey: ['liveMatches'],
    queryFn: () => matchService.getLive(),
    refetchInterval: 30000, // Refetch every 30 seconds for live matches
  });

  // Fetch today's matches
  const { data: todayMatchesData, isLoading: todayMatchesLoading } = useQuery({
    queryKey: ['todayMatches'],
    queryFn: () => matchService.getToday(),
  });

  // Fetch upcoming matches
  const { data: upcomingMatchesData, isLoading: upcomingMatchesLoading } = useQuery({
    queryKey: ['upcomingMatches'],
    queryFn: () => matchService.getUpcoming(),
  });

  // Calculate quick stats (simplified - would need additional endpoints)
  const quickStats = useMemo(() => {
    const featured = featuredData?.data || [];
    const upcoming = upcomingTournamentsData?.data || [];
    const allTournaments = [...featured, ...upcoming];
    
    return {
      totalTournaments: allTournaments.length,
      activeTournaments: featured.filter(t => t.status === 'ongoing').length,
      totalTeams: allTournaments.reduce((sum, t) => sum + (t.team_count || 0), 0),
      totalMatches: allTournaments.reduce((sum, t) => sum + (t.match_count || 0), 0),
    };
  }, [featuredData, upcomingTournamentsData]);

  const featuredTournament = featuredData?.data?.[0];
  const featuredTournaments = featuredData?.data?.slice(0, 6) || [];
  const liveMatches = liveMatchesData || [];
  const todayMatches = todayMatchesData || [];
  const upcomingMatches = (upcomingMatchesData || []).slice(0, 6);

  const handleSearch = (query) => {
    if (query.trim()) {
      navigate(`/search?q=${encodeURIComponent(query)}`);
    }
  };

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
              <h1 className="text-4xl md:text-5xl font-bold mb-4">
                Welcome to Sports Tournament
              </h1>
              <p className="text-xl mb-6 text-primary-100">
                Your comprehensive source for sports tournament information, matches, teams, and statistics.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Link
                  to="/tournaments"
                  className="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                >
                  Browse Tournaments
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Link>
                <Link
                  to="/matches"
                  className="inline-flex items-center justify-center px-6 py-3 border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-primary-600 transition-colors"
                >
                  View Matches
                </Link>
              </div>
            </div>
            <div className="text-center lg:text-right">
              <div className="inline-block bg-white bg-opacity-20 rounded-lg p-6 backdrop-blur-sm">
                <div className="text-sm text-primary-100 mb-2">Current Date & Time</div>
                <div className="text-2xl font-bold">{getCurrentDateTime()}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Search Bar Section */}
      <section className="bg-white py-8 shadow-sm">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <SearchBar
            onSearch={handleSearch}
            placeholder="Search tournaments, teams, players, matches..."
            className="w-full"
          />
        </div>
      </section>

      {/* Quick Stats Section */}
      <section className="bg-gray-50 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="bg-white rounded-lg p-6 text-center shadow-md">
              <Trophy className="h-8 w-8 text-primary-600 mx-auto mb-2" />
              <div className="text-3xl font-bold text-gray-900">{quickStats.totalTournaments}</div>
              <div className="text-sm text-gray-600">Total Tournaments</div>
            </div>
            <div className="bg-white rounded-lg p-6 text-center shadow-md">
              <Activity className="h-8 w-8 text-green-600 mx-auto mb-2" />
              <div className="text-3xl font-bold text-gray-900">{quickStats.activeTournaments}</div>
              <div className="text-sm text-gray-600">Active Tournaments</div>
            </div>
            <div className="bg-white rounded-lg p-6 text-center shadow-md">
              <Users className="h-8 w-8 text-blue-600 mx-auto mb-2" />
              <div className="text-3xl font-bold text-gray-900">{quickStats.totalTeams}</div>
              <div className="text-sm text-gray-600">Total Teams</div>
            </div>
            <div className="bg-white rounded-lg p-6 text-center shadow-md">
              <Calendar className="h-8 w-8 text-purple-600 mx-auto mb-2" />
              <div className="text-3xl font-bold text-gray-900">{quickStats.totalMatches}</div>
              <div className="text-sm text-gray-600">Total Matches</div>
            </div>
          </div>
        </div>
      </section>

      {/* Featured Tournaments Section */}
      <section className="py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-3xl font-bold text-gray-900">Featured Tournaments</h2>
            <Link
              to="/tournaments"
              className="text-primary-600 hover:text-primary-700 font-medium flex items-center"
            >
              View All Tournaments
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </div>

          {featuredLoading ? (
            <Loading />
          ) : featuredError ? (
            <ErrorMessage
              message="Failed to load featured tournaments. Please try again later."
              onRetry={() => window.location.reload()}
            />
          ) : featuredTournaments.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {featuredTournaments.map((tournament) => (
                <TournamentCard key={tournament.id} tournament={tournament} />
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-500">
              No featured tournaments available at the moment.
            </div>
          )}
        </div>
      </section>

      {/* Live Matches Section */}
      <section className="py-12 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-3xl font-bold text-gray-900">Live Matches</h2>
            {liveMatches.length > 0 && (
              <Link
                to="/matches?status=live"
                className="text-primary-600 hover:text-primary-700 font-medium flex items-center"
              >
                View All Live Matches
                <ArrowRight className="ml-2 h-5 w-5" />
              </Link>
            )}
          </div>

          {liveMatchesLoading ? (
            <Loading />
          ) : liveMatchesError ? (
            <ErrorMessage
              message="Failed to load live matches. Please try again later."
              onRetry={() => window.location.reload()}
            />
          ) : liveMatches.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {liveMatches.map((match) => (
                <MatchCard key={match.id} match={match} />
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-500">
              No live matches at the moment.
            </div>
          )}
        </div>
      </section>

      {/* Upcoming Matches Section */}
      <section className="py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-3xl font-bold text-gray-900">Upcoming Matches</h2>
            <Link
              to="/matches?status=scheduled"
              className="text-primary-600 hover:text-primary-700 font-medium flex items-center"
            >
              View All Matches
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </div>

          {(todayMatchesLoading || upcomingMatchesLoading) ? (
            <Loading />
          ) : (todayMatches.length > 0 || upcomingMatches.length > 0) ? (
            <div className="space-y-8">
              {todayMatches.length > 0 && (
                <div>
                  <h3 className="text-xl font-semibold text-gray-700 mb-4">Today's Matches</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {todayMatches.slice(0, 6).map((match) => (
                      <MatchCard key={match.id} match={match} />
                    ))}
                  </div>
                </div>
              )}
              {upcomingMatches.length > 0 && (
                <div>
                  <h3 className="text-xl font-semibold text-gray-700 mb-4">Upcoming Matches</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {upcomingMatches.map((match) => (
                      <MatchCard key={match.id} match={match} />
                    ))}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-500">
              No upcoming matches scheduled at the moment.
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default Home;
