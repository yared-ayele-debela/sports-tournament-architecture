import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { tournamentService } from '../api/tournaments';
import { matchService } from '../api/matches';
import TournamentCard from '../components/tournament/TournamentCard';
import MatchCard from '../components/match/MatchCard';
import Loading from '../components/common/Loading';
import ErrorMessage from '../components/common/ErrorMessage';

const Home = () => {
  const { data: featuredData, isLoading: featuredLoading, error: featuredError } = useQuery({
    queryKey: ['featuredTournaments'],
    queryFn: () => tournamentService.getFeatured(),
    staleTime: 60 * 1000, // 1 minute - reasonable cache time for featured content
    refetchOnMount: true, // Always refetch when component mounts to get latest data
    refetchOnWindowFocus: true, // Refetch when window regains focus (user returns to tab)
    // No refetchInterval - only refetch on user actions to reduce server load
  });

  // Fetch live matches
  const { data: liveMatchesData, isLoading: liveMatchesLoading } = useQuery({
    queryKey: ['liveMatches', 'home'],
    queryFn: () => matchService.getLive(),
    refetchInterval: 10000, // Refresh every 10 seconds
    staleTime: 5000,
  });

  // Fetch upcoming matches
  const { data: upcomingMatchesData, isLoading: upcomingMatchesLoading } = useQuery({
    queryKey: ['upcomingMatches'],
    queryFn: () => matchService.getUpcoming(),
  });

  const featuredTournaments = featuredData?.data?.slice(0, 6) || [];
  const liveMatches = liveMatchesData?.data?.matches || liveMatchesData?.matches || liveMatchesData || [];
  const displayLiveMatches = liveMatches.slice(0, 6);
  const upcomingMatches = (upcomingMatchesData || []).slice(0, 6);

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-4xl md:text-5xl font-bold mb-4">
              Welcome to Sports Tournament
            </h1>
            <p className="text-xl mb-6 text-primary-100">
              Your source for sports tournament information, matches, and teams.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
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
              View All
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </div>

          {featuredLoading ? (
            <Loading />
          ) : featuredError ? (
            <ErrorMessage
              message="Failed to load featured tournaments."
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
              No featured tournaments available.
            </div>
          )}
        </div>
      </section>

      {/* Live Matches Section */}
      <section className="py-12 bg-gradient-to-br from-red-50 to-orange-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-8">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 bg-red-600 rounded-full animate-pulse"></div>
              <h2 className="text-3xl font-bold text-gray-900">Live Matches</h2>
            </div>
            <Link
              to="/matches/live"
              className="text-red-600 hover:text-red-700 font-semibold flex items-center"
            >
              View All Live
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </div>

          {liveMatchesLoading ? (
            <Loading />
          ) : displayLiveMatches.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {displayLiveMatches.map((match) => (
                <MatchCard key={match.id} match={match} />
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-500">
              No live matches at the moment. Check back later!
            </div>
          )}
        </div>
      </section>

      {/* Upcoming Matches Section */}
      <section className="py-12 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-3xl font-bold text-gray-900">Upcoming Matches</h2>
            <Link
              to="/matches"
              className="text-primary-600 hover:text-primary-700 font-medium flex items-center"
            >
              View All
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </div>

          {upcomingMatchesLoading ? (
            <Loading />
          ) : upcomingMatches.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {upcomingMatches.map((match) => (
                <MatchCard key={match.id} match={match} />
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-500">
              No upcoming matches scheduled.
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default Home;
