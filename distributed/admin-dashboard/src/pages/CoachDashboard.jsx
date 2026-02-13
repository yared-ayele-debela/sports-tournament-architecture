import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import { teamsService } from '../api/teams';
import { playersService } from '../api/teams';
import { matchesService } from '../api/matches';
import { tournamentsService } from '../api/tournaments';
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
      title: 'Upcoming Matches',
      value: upcomingMatches.length,
      icon: Calendar,
      color: 'bg-green-500',
      link: '/matches/my-matches?status=scheduled',
    },
  ];

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Coach Dashboard</h1>
        <p className="text-gray-600 mt-1">Manage your team and players</p>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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

      {/* My Teams Section */}
      <div className="card mb-8">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">My Teams</h2>
        <p className="text-gray-600 mb-4">View and manage your teams</p>
        <Link
          to="/teams/my-teams"
          className="btn btn-primary inline-flex items-center"
        >
          <Users className="w-5 h-5 mr-2" />
          View My Teams
        </Link>
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

      {/* Upcoming Matches */}
      {upcomingMatches.length > 0 && (
        <div className="card mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Upcoming Matches</h2>
          <div className="space-y-3">
            {upcomingMatches.slice(0, 5).map((match) => (
              <Link
                key={match.id}
                to={`/matches/my-matches/${match.id}`}
                className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-1">
                      <span className="font-medium text-gray-900">
                        {match.home_team?.name || 'TBD'} vs {match.away_team?.name || 'TBD'}
                      </span>
                    </div>
                    <div className="flex items-center space-x-4 text-sm text-gray-600">
                      {match.tournament && (
                        <span className="flex items-center">
                          <Trophy className="w-4 h-4 mr-1" />
                          {match.tournament.name}
                        </span>
                      )}
                      {match.match_date && (
                        <span className="flex items-center">
                          <Clock className="w-4 h-4 mr-1" />
                          {new Date(match.match_date).toLocaleDateString()}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>
          {upcomingMatches.length > 5 && (
            <div className="mt-4 text-center">
              <Link
                to="/matches/my-matches?status=scheduled"
                className="text-primary-600 hover:text-primary-700 text-sm font-medium"
              >
                View all upcoming matches →
              </Link>
            </div>
          )}
        </div>
      )}

      {/* Recent Matches */}
      {recentMatches.length > 0 && (
        <div className="card mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Recent Matches</h2>
          <div className="space-y-3">
            {recentMatches.slice(0, 5).map((match) => (
              <Link
                key={match.id}
                to={`/matches/my-matches/${match.id}`}
                className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-1">
                      <span className="font-medium text-gray-900">
                        {match.home_team?.name || 'TBD'} vs {match.away_team?.name || 'TBD'}
                      </span>
                      {(match.home_score !== null && match.away_score !== null) && (
                        <span className="text-sm font-semibold text-primary-600">
                          {match.home_score} - {match.away_score}
                        </span>
                      )}
                    </div>
                    <div className="flex items-center space-x-4 text-sm text-gray-600">
                      {match.tournament && (
                        <span className="flex items-center">
                          <Trophy className="w-4 h-4 mr-1" />
                          {match.tournament.name}
                        </span>
                      )}
                      {match.match_date && (
                        <span className="flex items-center">
                          <Clock className="w-4 h-4 mr-1" />
                          {new Date(match.match_date).toLocaleDateString()}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>
          {recentMatches.length > 5 && (
            <div className="mt-4 text-center">
              <Link
                to="/matches/my-matches?status=completed"
                className="text-primary-600 hover:text-primary-700 text-sm font-medium"
              >
                View all recent matches →
              </Link>
            </div>
          )}
        </div>
      )}

      {/* User Profile Card */}
      {user && (
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Your Profile</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-500">Name</p>
              <p className="font-medium text-gray-900">{user.name || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Email</p>
              <p className="font-medium text-gray-900">{user.email || 'N/A'}</p>
            </div>
            {user.roles && user.roles.length > 0 && (
              <div className="md:col-span-2">
                <p className="text-sm text-gray-500 mb-2">Roles</p>
                <div className="flex flex-wrap gap-2">
                  {user.roles.map((role, index) => (
                    <span
                      key={index}
                      className="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium"
                    >
                      {role.name || role}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
