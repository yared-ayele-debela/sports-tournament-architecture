import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { statisticsService } from '../api/statistics';
import { tournamentsService } from '../api/tournaments';
import { teamsService } from '../api/teams';
import { 
  Users, 
  Trophy, 
  Users as TeamIcon, 
  Calendar, 
  TrendingUp, 
  Clock,
  Activity,
  MapPin,
  Shield,
  Key,
  UserCircle
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function AdminDashboard() {
  const { user } = useAuth();

  // Fetch all statistics from the statistics endpoint
  const { data: statisticsData, isLoading } = useQuery({
    queryKey: ['statistics'],
    queryFn: () => statisticsService.getStatistics(),
    refetchInterval: 300000, // Refetch every 5 minutes
  });

  // Fetch chart data
  const { data: matchesByStatusData, isLoading: loadingMatchesByStatus } = useQuery({
    queryKey: ['matches-by-status'],
    queryFn: () => statisticsService.getMatchesByStatus(),
    refetchInterval: 300000,
  });

  const { data: goalsPerTournamentData, isLoading: loadingGoalsPerTournament } = useQuery({
    queryKey: ['goals-per-tournament'],
    queryFn: () => statisticsService.getGoalsPerTournament(),
    refetchInterval: 300000,
  });

  const { data: topScoringTeamsData, isLoading: loadingTopScoringTeams } = useQuery({
    queryKey: ['top-scoring-teams'],
    queryFn: () => statisticsService.getTopScoringTeams(),
    refetchInterval: 300000,
  });

  // Fetch tournaments and teams for chart labels
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments-for-charts'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
    enabled: !!goalsPerTournamentData,
  });

  // Fetch teams for top scoring teams chart
  const { data: teamsData } = useQuery({
    queryKey: ['teams-for-charts'],
    queryFn: () => teamsService.list({ per_page: 100 }),
    enabled: !!topScoringTeamsData,
  });

  const stats = statisticsData || {};

  const statsCards = [
    {
      title: 'Total Users',
      value: stats?.users?.total || 0,
      icon: Users,
      color: 'bg-blue-500',
      link: '/users',
    },
    {
      title: 'Total Tournaments',
      value: stats?.tournaments?.total || 0,
      icon: Trophy,
      color: 'bg-yellow-500',
      link: '/tournaments',
    },
    {
      title: 'Active Tournaments',
      value: stats?.tournaments?.active || 0,
      icon: Activity,
      color: 'bg-green-600',
      link: '/tournaments?status=ongoing',
    },
    {
      title: 'Total Sports',
      value: stats?.sports?.total || 0,
      icon: Trophy,
      color: 'bg-indigo-500',
      link: '/sports',
    },
    {
      title: 'Total Venues',
      value: stats?.venues?.total || 0,
      icon: MapPin,
      color: 'bg-pink-500',
      link: '/venues',
    },
    {
      title: 'Total Matches',
      value: stats?.matches?.total || 0,
      icon: Calendar,
      color: 'bg-purple-500',
      link: '/matches',
    },
    {
      title: 'Total Players',
      value: stats?.players?.total || 0,
      icon: UserCircle,
      color: 'bg-teal-500',
      link: '/players',
    },
    {
      title: 'Total Teams',
      value: stats?.teams?.total || 0,
      icon: TeamIcon,
      color: 'bg-green-500',
      link: '/teams',
    },
    {
      title: 'Total Roles',
      value: stats?.roles?.total || 0,
      icon: Shield,
      color: 'bg-red-500',
      link: '/roles',
    },
    {
      title: 'Total Permissions',
      value: stats?.permissions?.total || 0,
      icon: Key,
      color: 'bg-orange-500',
      link: '/permissions',
    },
  ];

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

      {/* Statistics Cards */}
      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          {[...Array(10)].map((_, index) => (
            <div key={index} className="card animate-pulse">
              <div className="flex items-center justify-between">
                <div className="flex-1">
                  <div className="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                  <div className="h-8 bg-gray-200 rounded w-16"></div>
                </div>
                <div className="w-14 h-14 bg-gray-200 rounded-lg"></div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
          {statsCards.map((stat, index) => {
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
                    <p className="text-3xl font-bold text-gray-900">{stat.value.toLocaleString()}</p>
                  </div>
                  <div className={`${stat.color} p-3 rounded-lg`}>
                    <Icon className="w-8 h-8 text-white" />
                  </div>
                </div>
              </Link>
            );
          })}
        </div>
      )}

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

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Matches by Status Chart */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">📊 Matches by Status</h2>
          {loadingMatchesByStatus ? (
            <div className="h-64 flex items-center justify-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            </div>
          ) : matchesByStatusData && matchesByStatusData.length > 0 ? (
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={matchesByStatusData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="status" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar dataKey="count" fill="#8b5cf6" name="Number of Matches" />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-64 flex items-center justify-center text-gray-500">
              No match data available
            </div>
          )}
        </div>

        {/* Goals Per Tournament Chart */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">⚽ Goals Per Tournament</h2>
          {loadingGoalsPerTournament ? (
            <div className="h-64 flex items-center justify-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            </div>
          ) : goalsPerTournamentData && goalsPerTournamentData.length > 0 ? (
            <ResponsiveContainer width="100%" height={400}>
              <BarChart data={goalsPerTournamentData.map(item => {
                const tournament = tournamentsData?.data?.find(t => t.id === item.tournament_id);
                return {
                  ...item,
                  name: tournament?.name || `Tournament ${item.tournament_id}`,
                };
              })}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar dataKey="total_goals" fill="#10b981" name="Total Goals" />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-64 flex items-center justify-center text-gray-500">
              No tournament goals data available
            </div>
          )}
        </div>
      </div>

      {/* Top Scoring Teams Chart */}
      <div className="card mb-8">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">🏆 Top 5 Scoring Teams</h2>
        {loadingTopScoringTeams ? (
          <div className="h-64 flex items-center justify-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
          </div>
        ) : topScoringTeamsData && topScoringTeamsData.length > 0 ? (
          <ResponsiveContainer width="100%" height={600}>
            <BarChart data={topScoringTeamsData.map(item => {
              const team = teamsData?.data?.find(t => t.id === item.team_id);
              return {
                ...item,
                name: team?.name || `Team ${item.team_id}`,
              };
            })}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
              <YAxis />
              <Tooltip />
              <Legend />
              <Bar dataKey="total_goals" fill="#f59e0b" name="Total Goals" />
            </BarChart>
          </ResponsiveContainer>
        ) : (
          <div className="h-64 flex items-center justify-center text-gray-500">
            No team scoring data available
          </div>
        )}
      </div>

      
    </div>
  );
}
