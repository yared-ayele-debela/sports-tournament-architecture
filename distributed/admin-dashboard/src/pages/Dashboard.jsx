import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { usersService } from '../api/users';
import { tournamentsService } from '../api/tournaments';
import { teamsService } from '../api/teams';
import { matchesService } from '../api/matches';
import { Users, Trophy, Users as TeamIcon, Calendar, TrendingUp, Clock } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function AdminDashboard() {
  const { user } = useAuth();

  // Fetch statistics
  const { data: usersData } = useQuery({
    queryKey: ['users', 'stats'],
    queryFn: () => usersService.list({ per_page: 1 }),
  });

  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'stats'],
    queryFn: () => tournamentsService.list({ per_page: 1 }),
  });

  const { data: matchesData } = useQuery({
    queryKey: ['matches', 'stats'],
    queryFn: () => matchesService.list({ per_page: 1 }),
  });

  // Extract totals from pagination
  const totalUsers = usersData?.pagination?.total || usersData?.total || 0;
  const totalTournaments = tournamentsData?.pagination?.total || tournamentsData?.total || 0;
  const totalMatches = matchesData?.pagination?.total || matchesData?.total || 0;

  // Get active tournaments (status: ongoing)
  const { data: activeTournamentsData } = useQuery({
    queryKey: ['tournaments', 'active'],
    queryFn: () => tournamentsService.list({ status: 'ongoing', per_page: 1 }),
  });

  const activeTournaments = activeTournamentsData?.pagination?.total || activeTournamentsData?.total || 0;

  // Get upcoming matches
  const { data: upcomingMatchesData } = useQuery({
    queryKey: ['matches', 'upcoming'],
    queryFn: () => matchesService.list({ status: 'scheduled', per_page: 1 }),
  });

  const upcomingMatches = upcomingMatchesData?.pagination?.total || upcomingMatchesData?.total || 0;

  const stats = [
    {
      title: 'Total Users',
      value: totalUsers,
      icon: Users,
      color: 'bg-blue-500',
      link: '/users',
    },
    {
      title: 'Total Tournaments',
      value: totalTournaments,
      icon: Trophy,
      color: 'bg-yellow-500',
      link: '/tournaments',
    },
    {
      title: 'Total Teams',
      value: 0, // Will be fetched when teams service is ready
      icon: TeamIcon,
      color: 'bg-green-500',
      link: '/teams',
    },
    {
      title: 'Total Matches',
      value: totalMatches,
      icon: Calendar,
      color: 'bg-purple-500',
      link: '/matches',
    },
    {
      title: 'Active Tournaments',
      value: activeTournaments,
      icon: TrendingUp,
      color: 'bg-green-600',
      link: '/tournaments?status=ongoing',
    },
    {
      title: 'Upcoming Matches',
      value: upcomingMatches,
      icon: Clock,
      color: 'bg-orange-500',
      link: '/matches?status=scheduled',
    },
  ];

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

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
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link
            to="/users/new"
            className="btn btn-primary flex items-center justify-center"
          >
            <Users className="w-5 h-5 mr-2" />
            Add User
          </Link>
          <Link
            to="/roles/new"
            className="btn btn-primary flex items-center justify-center"
          >
            <Trophy className="w-5 h-5 mr-2" />
            Create Role
          </Link>
          <Link
            to="/tournaments/new"
            className="btn btn-primary flex items-center justify-center"
          >
            <Trophy className="w-5 h-5 mr-2" />
            New Tournament
          </Link>
          <Link
            to="/matches/new"
            className="btn btn-primary flex items-center justify-center"
          >
            <Calendar className="w-5 h-5 mr-2" />
            Schedule Match
          </Link>
        </div>
      </div>

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
