import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { 
  Calendar, 
  Trophy, 
  Users, 
  MapPin, 
  Info, 
  BarChart3,
  Activity,
  Clock,
  CheckCircle2,
  XCircle
} from 'lucide-react';
import { tournamentService } from '../../api/tournaments';
import { resultsService } from '../../api/results';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Breadcrumbs from '../../components/layout/Breadcrumbs';
import Badge from '../../components/common/Badge';
import StandingsTable from '../../components/standings/StandingsTable';
import MatchCard from '../../components/match/MatchCard';
import TeamCard from '../../components/team/TeamCard';
import { formatDate, formatDateTime } from '../../utils/dateUtils';
import { STATUS_COLORS } from '../../utils/constants';

const tabs = [
  { id: 'overview', label: 'Overview', icon: Info },
  { id: 'standings', label: 'Standings', icon: Trophy },
  { id: 'matches', label: 'Matches', icon: Activity },
  { id: 'teams', label: 'Teams', icon: Users },
  { id: 'statistics', label: 'Statistics', icon: BarChart3 },
];

const TournamentDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');
  const [matchFilters, setMatchFilters] = useState({
    status: 'all',
    round: '',
    date: '',
  });
  const [teamViewMode, setTeamViewMode] = useState('grid');
  const [currentPage, setCurrentPage] = useState(1);
  const [teamsPage, setTeamsPage] = useState(1);

  // Fetch tournament details
  const { data: tournamentData, isLoading: isLoadingTournament, error: tournamentError } = useQuery({
    queryKey: ['tournament', id],
    queryFn: () => tournamentService.getById(id),
  });

  // Fetch standings (from Results Service)
  const { data: standingsData, isLoading: isLoadingStandings } = useQuery({
    queryKey: ['tournamentStandings', id],
    queryFn: () => resultsService.getStandings(id),
    enabled: activeTab === 'standings' && !!id,
  });

  // Fetch matches (from Match Service)
  const { data: matchesData, isLoading: isLoadingMatches } = useQuery({
    queryKey: ['tournamentMatches', id, matchFilters, currentPage],
    queryFn: () => tournamentService.getMatches(id, {
      ...matchFilters,
      page: currentPage,
      per_page: 20,
    }),
    enabled: activeTab === 'matches' && !!id,
  });

  // Fetch teams (from Team Service)
  const { data: teamsData, isLoading: isLoadingTeams } = useQuery({
    queryKey: ['tournamentTeams', id, teamsPage],
    queryFn: () => tournamentService.getTeams(id, {
      page: teamsPage,
      per_page: 20,
    }),
    enabled: activeTab === 'teams' && !!id,
  });

  // Fetch statistics (from Results Service)
  const { data: statisticsData, isLoading: isLoadingStatistics } = useQuery({
    queryKey: ['tournamentStatistics', id],
    queryFn: () => resultsService.getStatistics(id),
    enabled: activeTab === 'statistics' && !!id,
  });

  if (isLoadingTournament) {
    return <Loading />;
  }

  if (tournamentError || !tournamentData) {
    return <ErrorMessage message="Failed to load tournament details" />;
  }

  const tournament = tournamentData?.data || tournamentData;
  const standings = standingsData?.data?.standings || standingsData?.data || [];
  const matches = matchesData?.data?.matches || matchesData?.data?.data?.matches || [];
  const teams = teamsData?.data?.teams || teamsData?.data?.data?.teams || teamsData?.data?.data || [];
  const statistics = statisticsData?.data || statisticsData;

  const breadcrumbs = [
    { label: 'Home', path: '/' },
    { label: 'Tournaments', path: '/tournaments' },
    { label: tournament.name || 'Tournament Details' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={breadcrumbs} />

        {/* Tournament Header */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
          {tournament.banner && (
            <div className="h-48 md:h-64 bg-gradient-to-r from-blue-500 to-purple-600 relative">
              <img
                src={tournament.banner}
                alt={tournament.name}
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-black bg-opacity-30"></div>
            </div>
          )}
          
          <div className="p-6 md:p-8">
            <div className="flex flex-col md:flex-row md:items-start gap-6">
              {/* Logo */}
              {tournament.logo && (
                <img
                  src={tournament.logo}
                  alt={tournament.name}
                  className="h-24 w-24 md:h-32 md:w-32 object-contain rounded-lg bg-white p-2 shadow-md"
                />
              )}

              {/* Tournament Info */}
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-3 mb-4">
                  <h1 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                    {tournament.name}
                  </h1>
                  {tournament.sport && (
                    <Badge color="blue">{tournament.sport.name || tournament.sport}</Badge>
                  )}
                  {tournament.status && (
                    <Badge color={STATUS_COLORS[tournament.status] || 'gray'}>
                      {tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1)}
                    </Badge>
                  )}
                </div>

                {tournament.description && (
                  <p className="text-gray-600 dark:text-gray-300 mb-4">
                    {tournament.description}
                  </p>
                )}

                {/* Key Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                  {tournament.start_date && (
                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                      <Calendar className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Start Date</div>
                        <div className="text-sm">{formatDate(tournament.start_date)}</div>
                      </div>
                    </div>
                  )}
                  {tournament.end_date && (
                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                      <Calendar className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">End Date</div>
                        <div className="text-sm">{formatDate(tournament.end_date)}</div>
                      </div>
                    </div>
                  )}
                  {tournament.format && (
                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                      <Trophy className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Format</div>
                        <div className="text-sm capitalize">{tournament.format}</div>
                      </div>
                    </div>
                  )}
                  {tournament.venue && (
                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                      <MapPin className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Venue</div>
                        <div className="text-sm">
                          {tournament.venue.name || tournament.venue}
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
          <div className="border-b border-gray-200 dark:border-gray-700">
            <nav className="flex overflow-x-auto">
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`
                      flex items-center gap-2 px-6 py-4 font-medium text-sm whitespace-nowrap
                      border-b-2 transition-colors
                      ${
                        activeTab === tab.id
                          ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                      }
                    `}
                  >
                    <Icon className="h-5 w-5" />
                    {tab.label}
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Tab Content */}
          <div className="p-6">
            {activeTab === 'overview' && (
              <OverviewTab tournament={tournament} matches={matches} teams={teams} />
            )}

            {activeTab === 'standings' && (
              <StandingsTab 
                standings={standings} 
                isLoading={isLoadingStandings}
                tournamentId={id}
              />
            )}

            {activeTab === 'matches' && (
              <MatchesTab
                matches={matches}
                isLoading={isLoadingMatches}
                filters={matchFilters}
                onFilterChange={setMatchFilters}
                currentPage={currentPage}
                onPageChange={setCurrentPage}
                pagination={matchesData?.data?.pagination || matchesData?.pagination}
              />
            )}

            {activeTab === 'teams' && (
              <TeamsTab
                teams={teams}
                isLoading={isLoadingTeams}
                viewMode={teamViewMode}
                onViewModeChange={setTeamViewMode}
                currentPage={teamsPage}
                onPageChange={setTeamsPage}
                pagination={teamsData?.data?.pagination || teamsData?.pagination}
              />
            )}

            {activeTab === 'statistics' && (
              <StatisticsTab
                statistics={statistics}
                isLoading={isLoadingStatistics}
                tournament={tournament}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

// Overview Tab Component
const OverviewTab = ({ tournament, matches, teams }) => {
  const totalMatches = matches?.length || 0;
  const completedMatches = matches?.filter(m => m.status === 'completed')?.length || 0;
  const remainingMatches = totalMatches - completedMatches;
  const totalTeams = teams?.length || 0;

  return (
    <div className="space-y-6">
      {/* Tournament Summary */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
          Tournament Summary
        </h2>
        {tournament.description && (
          <p className="text-gray-600 dark:text-gray-300 leading-relaxed">
            {tournament.description}
          </p>
        )}
      </div>

      {/* Key Dates */}
      <div>
        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Key Dates
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {tournament.start_date && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <Calendar className="h-6 w-6 text-blue-500" />
              <div>
                <div className="text-sm text-gray-500 dark:text-gray-400">Start Date</div>
                <div className="font-semibold text-gray-900 dark:text-white">
                  {formatDate(tournament.start_date)}
                </div>
              </div>
            </div>
          )}
          {tournament.end_date && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <Calendar className="h-6 w-6 text-blue-500" />
              <div>
                <div className="text-sm text-gray-500 dark:text-gray-400">End Date</div>
                <div className="font-semibold text-gray-900 dark:text-white">
                  {formatDate(tournament.end_date)}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Format Information */}
      {tournament.format && (
        <div>
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Format
          </h3>
          <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p className="text-gray-700 dark:text-gray-300 capitalize">
              {tournament.format}
            </p>
          </div>
        </div>
      )}

      {/* Venue Information */}
      {tournament.venue && (
        <div>
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Venue
          </h3>
          <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div className="flex items-center gap-2">
              <MapPin className="h-5 w-5 text-gray-500" />
              <span className="text-gray-700 dark:text-gray-300">
                {tournament.venue.name || tournament.venue}
              </span>
            </div>
          </div>
        </div>
      )}

      {/* Quick Stats */}
      <div>
        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Quick Statistics
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
            <Users className="h-8 w-8 text-blue-500 mx-auto mb-2" />
            <div className="text-3xl font-bold text-gray-900 dark:text-white">
              {totalTeams}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Total Teams</div>
          </div>
          <div className="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
            <Activity className="h-8 w-8 text-green-500 mx-auto mb-2" />
            <div className="text-3xl font-bold text-gray-900 dark:text-white">
              {totalMatches}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Total Matches</div>
          </div>
          <div className="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
            <CheckCircle2 className="h-8 w-8 text-purple-500 mx-auto mb-2" />
            <div className="text-3xl font-bold text-gray-900 dark:text-white">
              {completedMatches}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Completed</div>
          </div>
          <div className="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-center">
            <Clock className="h-8 w-8 text-orange-500 mx-auto mb-2" />
            <div className="text-3xl font-bold text-gray-900 dark:text-white">
              {remainingMatches}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Remaining</div>
          </div>
        </div>
      </div>
    </div>
  );
};

// Standings Tab Component
const StandingsTab = ({ standings, isLoading, tournamentId }) => {
  if (isLoading) {
    return <Loading />;
  }

  if (!standings || standings.length === 0) {
    return (
      <div className="text-center py-12">
        <Trophy className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-500 dark:text-gray-400">
          No standings available for this tournament yet.
        </p>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
          Tournament Standings
        </h2>
        <div className="text-sm text-gray-500 dark:text-gray-400">
          Last Updated: {new Date().toLocaleString()}
        </div>
      </div>
      <StandingsTable standings={standings} />
    </div>
  );
};

// Matches Tab Component
const MatchesTab = ({ matches, isLoading, filters, onFilterChange, currentPage, onPageChange, pagination }) => {
  if (isLoading) {
    return <Loading />;
  }

  const filteredMatches = matches || [];

  return (
    <div>
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
          Tournament Matches
        </h2>

        {/* Filters */}
        <div className="flex flex-wrap gap-4 mb-6">
          <select
            value={filters.status}
            onChange={(e) => onFilterChange({ ...filters, status: e.target.value })}
            className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="all">All Status</option>
            <option value="scheduled">Scheduled</option>
            <option value="live">Live</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>

          <input
            type="text"
            placeholder="Filter by round/group"
            value={filters.round}
            onChange={(e) => onFilterChange({ ...filters, round: e.target.value })}
            className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />

          <input
            type="date"
            value={filters.date}
            onChange={(e) => onFilterChange({ ...filters, date: e.target.value })}
            className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </div>
      </div>

      {filteredMatches.length === 0 ? (
        <div className="text-center py-12">
          <Activity className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500 dark:text-gray-400">
            No matches found for the selected filters.
          </p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-4">
            {filteredMatches.map((match) => (
              <MatchCard key={match.id} match={match} />
            ))}
          </div>

          {pagination && pagination.last_page > 1 && (
            <div className="mt-6 flex justify-center">
              <div className="flex gap-2">
                <button
                  onClick={() => onPageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <span className="px-4 py-2">
                  Page {currentPage} of {pagination.last_page}
                </span>
                <button
                  onClick={() => onPageChange(currentPage + 1)}
                  disabled={currentPage >= pagination.last_page}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
};

// Teams Tab Component
const TeamsTab = ({ teams, isLoading, viewMode, onViewModeChange, currentPage, onPageChange, pagination }) => {
  if (isLoading) {
    return <Loading />;
  }

  const teamList = teams || [];

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
          Tournament Teams
        </h2>
        <div className="flex gap-2">
          <button
            onClick={() => onViewModeChange('grid')}
            className={`px-4 py-2 rounded-lg ${
              viewMode === 'grid'
                ? 'bg-blue-500 text-white'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
            }`}
          >
            Grid
          </button>
          <button
            onClick={() => onViewModeChange('list')}
            className={`px-4 py-2 rounded-lg ${
              viewMode === 'list'
                ? 'bg-blue-500 text-white'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
            }`}
          >
            List
          </button>
        </div>
      </div>

      {teamList.length === 0 ? (
        <div className="text-center py-12">
          <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500 dark:text-gray-400">
            No teams found for this tournament.
          </p>
        </div>
      ) : (
        <>
          <div
            className={
              viewMode === 'grid'
                ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
                : 'space-y-4'
            }
          >
            {teamList.map((team) => (
              <TeamCard key={team.id} team={team} viewMode={viewMode} />
            ))}
          </div>

          {pagination && pagination.last_page > 1 && (
            <div className="mt-6 flex justify-center">
              <div className="flex gap-2">
                <button
                  onClick={() => onPageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <span className="px-4 py-2">
                  Page {currentPage} of {pagination.last_page}
                </span>
                <button
                  onClick={() => onPageChange(currentPage + 1)}
                  disabled={currentPage >= pagination.last_page}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
};

// Statistics Tab Component
const StatisticsTab = ({ statistics, isLoading, tournament }) => {
  if (isLoading) {
    return <Loading />;
  }

  if (!statistics || Object.keys(statistics).length === 0) {
    return (
      <div className="text-center py-12">
        <BarChart3 className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-500 dark:text-gray-400">
          No statistics available for this tournament yet.
        </p>
      </div>
    );
  }

  const stats = statistics.statistics || statistics;

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
        Tournament Statistics
      </h2>

      {/* Key Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {stats.total_goals !== undefined && (
          <div className="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {stats.total_goals || 0}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Total Goals Scored</div>
          </div>
        )}

        {stats.average_goals_per_match !== undefined && (
          <div className="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {(stats.average_goals_per_match || 0).toFixed(2)}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Average Goals per Match</div>
          </div>
        )}

        {stats.most_goals_in_match !== undefined && (
          <div className="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {stats.most_goals_in_match || 0}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Most Goals in a Match</div>
          </div>
        )}

        {stats.clean_sheets !== undefined && (
          <div className="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {stats.clean_sheets || 0}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Clean Sheets</div>
          </div>
        )}

        {stats.yellow_cards !== undefined && (
          <div className="p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {stats.yellow_cards || 0}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Yellow Cards</div>
          </div>
        )}

        {stats.red_cards !== undefined && (
          <div className="p-6 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {stats.red_cards || 0}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400">Red Cards</div>
          </div>
        )}
      </div>

      {/* Top Scoring Teams */}
      {stats.top_scoring_teams && stats.top_scoring_teams.length > 0 && (
        <div>
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Top Scoring Teams
          </h3>
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Team
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Goals
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {stats.top_scoring_teams.map((team, index) => (
                  <tr key={index}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        {team.logo && (
                          <img
                            src={team.logo}
                            alt={team.name}
                            className="h-8 w-8 object-contain mr-3"
                          />
                        )}
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {team.name}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {team.goals || 0}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};

export default TournamentDetails;
