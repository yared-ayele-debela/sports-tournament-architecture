import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import { teamsService } from '../api/teams';
import { playersService } from '../api/teams';
import { matchesService } from '../api/matches';
import { tournamentsService } from '../api/tournaments';
import { statisticsService } from '../api/statistics';
import { Users, Trophy, Calendar, Clock, UserCircle, Award } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function CoachDashboard() {
  const { user } = useAuth();
  const { hasPermission, isAdmin } = usePermissions();

  // Fetch coach's team (if assigned)
  // Note: This would need to be implemented based on how teams are linked to coaches
  // For now, we'll show general player and match statistics

  // Fetch player statistics
  const { data: playersData } = useQuery({
    queryKey: ['players', 'stats'],
    queryFn: () => playersService.list({ per_page: 1 }),
    enabled: hasPermission('manage_players') || isAdmin(),
  });

  // Fetch coach's teams to get count
  const { data: teamsData } = useQuery({
    queryKey: ['teams', 'my-teams', 'count'],
    queryFn: () => teamsService.list({ per_page: 1 }),
  });

  // Fetch coach match statistics by status
  const { data: coachMatchStats, isLoading: loadingCoachStats } = useQuery({
    queryKey: ['coach-match-stats'],
    queryFn: () => statisticsService.getCoachMatchesByStatus(),
    refetchInterval: 300000, // Refetch every 5 minutes
  });

  // Fetch upcoming matches (coach's teams only)
  const { data: upcomingMatchesData } = useQuery({
    queryKey: ['matches', 'upcoming', 'coach'],
    queryFn: () => matchesService.list({ status: 'scheduled', per_page: 5 }),
  });

  // Fetch recent completed matches (coach's teams only)
  const { data: recentMatchesData } = useQuery({
    queryKey: ['matches', 'recent', 'coach'],
    queryFn: () => matchesService.list({ status: 'completed', per_page: 5 }),
  });

  // Fetch active tournaments
  const { data: activeTournamentsData } = useQuery({
    queryKey: ['tournaments', 'active'],
    queryFn: () => tournamentsService.list({ status: 'ongoing', per_page: 5 }),
  });

  // Extract data
  const totalPlayers = playersData?.pagination?.total || playersData?.total || 0;
  const myTeamsCount = teamsData?.pagination?.total || (Array.isArray(teamsData?.data) ? teamsData.data.length : 0) || 0;
  
  // Extract matches - handle different response structures
  let upcomingMatches = [];
  if (Array.isArray(upcomingMatchesData)) {
    upcomingMatches = upcomingMatchesData;
  } else if (upcomingMatchesData?.data && Array.isArray(upcomingMatchesData.data)) {
    upcomingMatches = upcomingMatchesData.data;
  } else if (upcomingMatchesData?.data?.data && Array.isArray(upcomingMatchesData.data.data)) {
    upcomingMatches = upcomingMatchesData.data.data;
  }

  let recentMatches = [];
  if (Array.isArray(recentMatchesData)) {
    recentMatches = recentMatchesData;
  } else if (recentMatchesData?.data && Array.isArray(recentMatchesData.data)) {
    recentMatches = recentMatchesData.data;
  } else if (recentMatchesData?.data?.data && Array.isArray(recentMatchesData.data.data)) {
    recentMatches = recentMatchesData.data.data;
  }

  const activeTournaments = activeTournamentsData?.data || activeTournamentsData || [];

  // Get match counts from statistics
  const scheduledMatches = coachMatchStats?.scheduled || 0;
  const inProgressMatches = coachMatchStats?.in_progress || 0;
  const completedMatches = coachMatchStats?.completed || 0;
  const cancelledMatches = coachMatchStats?.cancelled || 0;
  const totalMatches = coachMatchStats?.total || 0;

  const stats = [
    {
      title: 'My Teams',
      value: myTeamsCount,
      icon: Users,
      color: 'bg-purple-500',
      link: '/teams/my-teams',
    },
    {
      title: 'My Players',
      value: totalPlayers,
      icon: UserCircle,
      color: 'bg-blue-500',
      link: '/teams/my-teams',
    },
    {
      title: 'Scheduled Matches',
      value: scheduledMatches,
      icon: Calendar,
      color: 'bg-yellow-500',
      link: '/matches/my-matches?status=scheduled',
    },
    {
      title: 'Ongoing Matches',
      value: inProgressMatches,
      icon: Clock,
      color: 'bg-blue-600',
      link: '/matches/my-matches?status=in_progress',
    },
    {
      title: 'Completed Matches',
      value: completedMatches,
      icon: Trophy,
      color: 'bg-green-500',
      link: '/matches/my-matches?status=completed',
    },
    {
      title: 'Cancelled Matches',
      value: cancelledMatches,
      icon: Calendar,
      color: 'bg-red-500',
      link: '/matches/my-matches?status=cancelled',
    },
  ];

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Coach Dashboard</h1>
        <p className="text-gray-600 mt-1">Manage your team and players</p>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        {stats.map((stat, index) => {
          const Icon = stat.icon;
          return (
            <Link
              key={index}
              to={stat.link}
              className="card hover:shadow-lg transition-shadow cursor-pointer"
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{stat.title}</p>
                  <p className="text-3xl font-bold text-gray-900">{stat.value}</p>
                </div>
                <div className={`${stat.color} p-3 rounded-lg`}>
                  <Icon className="w-8 h-8 text-white" />
                </div>
              </div>
            </Link>
          );
        })}
      </div>


      {/* Quick Actions */}
      <div className="card mb-8">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Link
            to="/teams/my-teams"
            className="btn btn-primary flex items-center justify-center"
          >
            <Users className="w-5 h-5 mr-2" />
            My Teams
          </Link>
          <Link
            to="/matches/my-matches"
            className="btn btn-primary flex items-center justify-center"
          >
            <Calendar className="w-5 h-5 mr-2" />
            My Matches
          </Link>
          <Link
            to="/standings"
            className="btn btn-primary flex items-center justify-center"
          >
            <Award className="w-5 h-5 mr-2" />
            View Standings
          </Link>
        </div>
      </div>

      {/* Upcoming Matches Table */}
      <div className="card mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Upcoming Matches</h2>
          {upcomingMatches.length > 5 && (
            <Link
              to="/matches/my-matches?status=scheduled"
              className="text-primary-600 hover:text-primary-700 text-sm font-medium"
            >
              View all →
            </Link>
          )}
        </div>
        {upcomingMatches.length === 0 ? (
          <div className="text-center py-8 text-gray-500">No upcoming matches</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Tournament</th>
                  <th>Home Team</th>
                  <th>Away Team</th>
                  <th>Date & Time</th>
                  <th>Round</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {upcomingMatches.slice(0, 5).map((match) => (
                  <tr key={match.id} className="hover:bg-gray-50">
                    <td>{match.id}</td>
                    <td>{match.tournament?.name || 'N/A'}</td>
                    <td className="font-medium">
                      {match.home_team?.name || 'TBD'}
                    </td>
                    <td className="font-medium">
                      {match.away_team?.name || 'TBD'}
                    </td>
                    <td>
                      {match.match_date
                        ? new Date(match.match_date).toLocaleString()
                        : 'N/A'}
                    </td>
                    <td>{match.round_number || 'N/A'}</td>
                    <td>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-medium ${
                          match.status === 'in_progress'
                            ? 'bg-green-100 text-green-800'
                            : match.status === 'cancelled'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-blue-100 text-blue-800'
                        }`}
                      >
                        {match.status || 'scheduled'}
                      </span>
                    </td>
                    <td>
                      <Link
                        to={`/matches/my-matches/${match.id}`}
                        className="text-primary-600 hover:text-primary-700 text-sm font-medium"
                      >
                        View
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Recent Matches Table */}
      <div className="card mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Recent Matches</h2>
          {recentMatches.length > 5 && (
            <Link
              to="/matches/my-matches?status=completed"
              className="text-primary-600 hover:text-primary-700 text-sm font-medium"
            >
              View all →
            </Link>
          )}
        </div>
        {recentMatches.length === 0 ? (
          <div className="text-center py-8 text-gray-500">No recent matches</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Tournament</th>
                  <th>Home Team</th>
                  <th>Away Team</th>
                  <th>Score</th>
                  <th>Date & Time</th>
                  <th>Round</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {recentMatches.slice(0, 5).map((match) => (
                  <tr key={match.id} className="hover:bg-gray-50">
                    <td>{match.id}</td>
                    <td>{match.tournament?.name || 'N/A'}</td>
                    <td className="font-medium">
                      {match.home_team?.name || 'TBD'}
                    </td>
                    <td className="font-medium">
                      {match.away_team?.name || 'TBD'}
                    </td>
                    <td>
                      {match.home_score !== null && match.away_score !== null ? (
                        <span className="font-bold text-primary-600">
                          {match.home_score} - {match.away_score}
                        </span>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td>
                      {match.match_date
                        ? new Date(match.match_date).toLocaleString()
                        : 'N/A'}
                    </td>
                    <td>{match.round_number || 'N/A'}</td>
                    <td>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-medium ${
                          match.status === 'completed'
                            ? 'bg-gray-100 text-gray-800'
                            : match.status === 'cancelled'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-blue-100 text-blue-800'
                        }`}
                      >
                        {match.status || 'scheduled'}
                      </span>
                    </td>
                    <td>
                      <Link
                        to={`/matches/my-matches/${match.id}`}
                        className="text-primary-600 hover:text-primary-700 text-sm font-medium"
                      >
                        View
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      
    </div>
  );
}
