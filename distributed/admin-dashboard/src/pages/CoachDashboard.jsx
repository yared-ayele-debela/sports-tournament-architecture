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

  // Fetch upcoming matches
  const { data: upcomingMatchesData } = useQuery({
    queryKey: ['matches', 'upcoming'],
    queryFn: () => matchesService.list({ status: 'scheduled', per_page: 5 }),
  });

  // Fetch active tournaments
  const { data: activeTournamentsData } = useQuery({
    queryKey: ['tournaments', 'active'],
    queryFn: () => tournamentsService.list({ status: 'ongoing', per_page: 5 }),
  });

  const totalPlayers = playersData?.pagination?.total || playersData?.total || 0;
  const upcomingMatches = upcomingMatchesData?.data || upcomingMatchesData || [];
  const activeTournaments = activeTournamentsData?.data || activeTournamentsData || [];

  const stats = [
    {
      title: 'My Players',
      value: totalPlayers,
      icon: UserCircle,
      color: 'bg-blue-500',
      link: '/players',
      permission: 'manage_players',
    },
    {
      title: 'Active Tournaments',
      value: activeTournaments.length,
      icon: Trophy,
      color: 'bg-yellow-500',
      link: '/tournaments?status=ongoing',
    },
    {
      title: 'Upcoming Matches',
      value: upcomingMatches.length,
      icon: Calendar,
      color: 'bg-green-500',
      link: '/matches?status=scheduled',
    },
  ].filter((stat) => !stat.permission || hasPermission(stat.permission) || isAdmin());

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

      {/* Quick Actions */}
      <div className="card mb-8">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {hasPermission('manage_players') || isAdmin() ? (
            <Link
              to="/players/new"
              className="btn btn-primary flex items-center justify-center"
            >
              <UserCircle className="w-5 h-5 mr-2" />
              Add Player
            </Link>
          ) : null}
          <Link
            to="/matches?status=scheduled"
            className="btn btn-primary flex items-center justify-center"
          >
            <Calendar className="w-5 h-5 mr-2" />
            View Matches
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
                to={`/matches/${match.id}`}
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
                      {match.scheduled_at && (
                        <span className="flex items-center">
                          <Clock className="w-4 h-4 mr-1" />
                          {new Date(match.scheduled_at).toLocaleDateString()}
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
                to="/matches?status=scheduled"
                className="text-primary-600 hover:text-primary-700 text-sm font-medium"
              >
                View all upcoming matches →
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
