import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { teamService } from '../../api/teams';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line } from 'recharts';
import Loading from '../common/Loading';
import ErrorMessage from '../common/ErrorMessage';

const TeamStatistics = ({ teamId, team }) => {
  // Fetch team matches for statistics
  const { data: matchesData, isLoading, error } = useQuery({
    queryKey: ['teamMatchesForStats', teamId],
    queryFn: () => teamService.getMatches(teamId, { limit: 100, status: 'completed' }),
    enabled: !!teamId,
  });

  const matches = matchesData?.data?.data || matchesData?.data || matchesData?.matches || [];

  // Calculate statistics
  const stats = useMemo(() => {
    const wins = team.statistics?.wins || 0;
    const losses = team.statistics?.losses || 0;
    const draws = team.statistics?.draws || 0;
    const totalMatches = wins + losses + draws;

    let goalsScored = 0;
    let goalsConceded = 0;
    let cleanSheets = 0;
    const goalsOverTime = [];
    const topScorers = {};

    matches.forEach((match, index) => {
      const isHome = match.home_team_id === teamId || match.home_team?.id === teamId;
      const teamScore = isHome ? match.home_score : match.away_score;
      const opponentScore = isHome ? match.away_score : match.home_score;

      goalsScored += teamScore || 0;
      goalsConceded += opponentScore || 0;

      if (opponentScore === 0) {
        cleanSheets++;
      }

      goalsOverTime.push({
        match: index + 1,
        date: match.match_date,
        scored: teamScore || 0,
        conceded: opponentScore || 0,
      });
    });

    const avgGoalsScored = totalMatches > 0 ? (goalsScored / totalMatches).toFixed(2) : 0;
    const avgGoalsConceded = totalMatches > 0 ? (goalsConceded / totalMatches).toFixed(2) : 0;

    // Match results distribution
    const resultsData = [
      { name: 'Wins', value: wins, color: '#10b981' },
      { name: 'Draws', value: draws, color: '#6b7280' },
      { name: 'Losses', value: losses, color: '#ef4444' },
    ];

    return {
      goalsScored,
      goalsConceded,
      avgGoalsScored,
      avgGoalsConceded,
      cleanSheets,
      topScorers,
      resultsData,
      goalsOverTime,
    };
  }, [matches, team.statistics, teamId]);

  if (isLoading) {
    return <Loading />;
  }

  if (error) {
    return (
      <ErrorMessage
        message="Failed to load statistics. Please try again later."
        onRetry={() => window.location.reload()}
      />
    );
  }

  const COLORS = ['#10b981', '#6b7280', '#ef4444'];

  return (
    <div className="space-y-6">
      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow-md p-6">
          <h4 className="text-sm text-gray-600 mb-2">Goals Scored</h4>
          <p className="text-3xl font-bold text-gray-900">{stats.goalsScored}</p>
        </div>
        <div className="bg-white rounded-lg shadow-md p-6">
          <h4 className="text-sm text-gray-600 mb-2">Goals Conceded</h4>
          <p className="text-3xl font-bold text-gray-900">{stats.goalsConceded}</p>
        </div>
        <div className="bg-white rounded-lg shadow-md p-6">
          <h4 className="text-sm text-gray-600 mb-2">Avg Goals/Match</h4>
          <p className="text-3xl font-bold text-primary-600">{stats.avgGoalsScored}</p>
        </div>
        <div className="bg-white rounded-lg shadow-md p-6">
          <h4 className="text-sm text-gray-600 mb-2">Clean Sheets</h4>
          <p className="text-3xl font-bold text-green-600">{stats.cleanSheets}</p>
        </div>
      </div>

      {/* Match Results Distribution */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-xl font-bold text-gray-900 mb-4">Match Results Distribution</h3>
        <ResponsiveContainer width="100%" height={300}>
          <PieChart>
            <Pie
              data={stats.resultsData}
              cx="50%"
              cy="50%"
              labelLine={false}
              label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
              outerRadius={80}
              fill="#8884d8"
              dataKey="value"
            >
              {stats.resultsData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip />
          </PieChart>
        </ResponsiveContainer>
      </div>

      {/* Goals Over Time */}
      {stats.goalsOverTime.length > 0 && (
        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-xl font-bold text-gray-900 mb-4">Goals Scored/Conceded Over Time</h3>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={stats.goalsOverTime}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="match" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Line type="monotone" dataKey="scored" stroke="#10b981" name="Goals Scored" />
              <Line type="monotone" dataKey="conceded" stroke="#ef4444" name="Goals Conceded" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      )}

      {/* Top Scorers (if available) */}
      {Object.keys(stats.topScorers).length > 0 && (
        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-xl font-bold text-gray-900 mb-4">Top Scorers</h3>
          <div className="space-y-2">
            {Object.entries(stats.topScorers)
              .sort(([, a], [, b]) => b - a)
              .slice(0, 5)
              .map(([player, goals]) => (
                <div key={player} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <span className="font-medium text-gray-900">{player}</span>
                  <span className="text-primary-600 font-bold">{goals} goals</span>
                </div>
              ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default TeamStatistics;
