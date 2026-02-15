import { useQuery } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import { matchesService } from '../api/matches';
import { tournamentsService } from '../api/tournaments';
import { resultsService } from '../api/results';
import { statisticsService } from '../api/statistics';
import { Calendar, Clock, FileText, Award, CheckCircle } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function RefereeDashboard() {
  const { user } = useAuth();
  const { hasPermission, isAdmin } = usePermissions();

  // Check if user can record events or submit reports
  const canRecordEvents = hasPermission('record_events') || isAdmin();
  const canSubmitReports = hasPermission('submit_reports') || isAdmin();

  // Fetch referee match statistics by status
  const { data: refereeMatchStats, isLoading: loadingRefereeStats } = useQuery({
    queryKey: ['referee-match-stats'],
    queryFn: () => statisticsService.getRefereeMatchesByStatus(),
    refetchInterval: 300000, // Refetch every 5 minutes
  });

  // Fetch scheduled matches (for refereeing) - only assigned to this referee
  const { data: scheduledMatchesData } = useQuery({
    queryKey: ['matches', 'scheduled', 'referee'],
    queryFn: () => matchesService.list({ status: 'scheduled', per_page: 10 }),
    enabled: canRecordEvents || canSubmitReports,
  });

  // Fetch in-progress matches - only assigned to this referee
  const { data: inProgressMatchesData } = useQuery({
    queryKey: ['matches', 'in-progress', 'referee'],
    queryFn: () => matchesService.list({ status: 'in_progress', per_page: 10 }),
    enabled: canRecordEvents || canSubmitReports,
  });

  // Fetch active tournaments
  const { data: activeTournamentsData } = useQuery({
    queryKey: ['tournaments', 'active'],
    queryFn: () => tournamentsService.list({ status: 'ongoing', per_page: 5 }),
  });

  // Extract matches - handle different response structures
  let scheduledMatches = [];
  if (Array.isArray(scheduledMatchesData)) {
    scheduledMatches = scheduledMatchesData;
  } else if (scheduledMatchesData?.data && Array.isArray(scheduledMatchesData.data)) {
    scheduledMatches = scheduledMatchesData.data;
  } else if (scheduledMatchesData?.data?.data && Array.isArray(scheduledMatchesData.data.data)) {
    scheduledMatches = scheduledMatchesData.data.data;
  }

  let inProgressMatches = [];
  if (Array.isArray(inProgressMatchesData)) {
    inProgressMatches = inProgressMatchesData;
  } else if (inProgressMatchesData?.data && Array.isArray(inProgressMatchesData.data)) {
    inProgressMatches = inProgressMatchesData.data;
  } else if (inProgressMatchesData?.data?.data && Array.isArray(inProgressMatchesData.data.data)) {
    inProgressMatches = inProgressMatchesData.data.data;
  }

  const activeTournaments = activeTournamentsData?.data || activeTournamentsData || [];

  // Get match counts from statistics
  const scheduledCount = refereeMatchStats?.scheduled || 0;
  const inProgressCount = refereeMatchStats?.in_progress || 0;
  const completedCount = refereeMatchStats?.completed || 0;
  const cancelledCount = refereeMatchStats?.cancelled || 0;
  const totalMatches = refereeMatchStats?.total || 0;

  const stats = [
    {
      title: 'Scheduled Matches',
      value: scheduledCount,
      icon: Calendar,
      color: 'bg-blue-500',
      link: '/matches?status=scheduled',
    },
    {
      title: 'In Progress',
      value: inProgressCount,
      icon: Clock,
      color: 'bg-orange-500',
      link: '/matches?status=in_progress',
    },
    {
      title: 'Completed Matches',
      value: completedCount,
      icon: CheckCircle,
      color: 'bg-green-500',
      link: '/matches?status=completed',
    },
    {
      title: 'Cancelled Matches',
      value: cancelledCount,
      icon: Calendar,
      color: 'bg-red-500',
      link: '/matches?status=cancelled',
    },
  ];

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Referee Dashboard</h1>
        <p className="text-gray-600 mt-1">Manage matches and record events</p>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
          {canRecordEvents && (
            <Link
              to="/matches?status=scheduled"
              className="btn btn-primary flex items-center justify-center"
            >
              <CheckCircle className="w-5 h-5 mr-2" />
              Record Events
            </Link>
          )}
          {canSubmitReports && (
            <Link
              to="/results"
              className="btn btn-primary flex items-center justify-center"
            >
              <FileText className="w-5 h-5 mr-2" />
              Submit Reports
            </Link>
          )}
          <Link
            to="/standings"
            className="btn btn-primary flex items-center justify-center"
          >
            <Award className="w-5 h-5 mr-2" />
            View Standings
          </Link>
        </div>
      </div>

      {/* Scheduled Matches Table */}
      <div className="card mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Scheduled Matches</h2>
          {scheduledMatches.length > 10 && (
            <Link
              to="/matches?status=scheduled"
              className="text-primary-600 hover:text-primary-700 text-sm font-medium"
            >
              View all →
            </Link>
          )}
        </div>
        {scheduledMatches.length === 0 ? (
          <div className="text-center py-8 text-gray-500">No scheduled matches</div>
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
                  <th>Venue</th>
                  <th>Round</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {scheduledMatches.slice(0, 10).map((match) => (
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
                      {match.match_date || match.scheduled_at
                        ? new Date(match.match_date || match.scheduled_at).toLocaleString()
                        : 'N/A'}
                    </td>
                    <td>{match.venue?.name || 'N/A'}</td>
                    <td>{match.round_number || 'N/A'}</td>
                    <td>
                      <span className="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
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

      {/* Matches In Progress Table */}
      <div className="card mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Matches In Progress</h2>
          {inProgressMatches.length > 10 && (
            <Link
              to="/matches?status=in_progress"
              className="text-primary-600 hover:text-primary-700 text-sm font-medium"
            >
              View all →
            </Link>
          )}
        </div>
        {inProgressMatches.length === 0 ? (
          <div className="text-center py-8 text-gray-500">No matches in progress</div>
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
                {inProgressMatches.slice(0, 10).map((match) => (
                  <tr key={match.id} className="hover:bg-orange-50">
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
                        <span className="font-bold text-orange-600">
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
                      <span className="px-2 py-1 rounded-full text-xs font-medium bg-orange-500 text-white">
                        LIVE
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
