import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const MatchStatistics = ({ statistics, homeTeam, awayTeam }) => {
  // Default statistics if not provided
  const stats = statistics || {
    possession: { home: 50, away: 50 },
    shots: { home: 0, away: 0 },
    shots_on_target: { home: 0, away: 0 },
    corners: { home: 0, away: 0 },
    fouls: { home: 0, away: 0 },
    yellow_cards: { home: 0, away: 0 },
    red_cards: { home: 0, away: 0 },
    offsides: { home: 0, away: 0 },
    pass_accuracy: { home: 0, away: 0 },
  };

  // Prepare data for bar charts
  const comparisonData = [
    {
      name: 'Possession (%)',
      home: stats.possession?.home || 0,
      away: stats.possession?.away || 0,
    },
    {
      name: 'Shots',
      home: stats.shots?.home || 0,
      away: stats.shots?.away || 0,
    },
    {
      name: 'Shots on Target',
      home: stats.shots_on_target?.home || 0,
      away: stats.shots_on_target?.away || 0,
    },
    {
      name: 'Corners',
      home: stats.corners?.home || 0,
      away: stats.corners?.away || 0,
    },
    {
      name: 'Fouls',
      home: stats.fouls?.home || 0,
      away: stats.fouls?.away || 0,
    },
    {
      name: 'Yellow Cards',
      home: stats.yellow_cards?.home || 0,
      away: stats.yellow_cards?.away || 0,
    },
    {
      name: 'Red Cards',
      home: stats.red_cards?.home || 0,
      away: stats.red_cards?.away || 0,
    },
    {
      name: 'Offsides',
      home: stats.offsides?.home || 0,
      away: stats.offsides?.away || 0,
    },
    {
      name: 'Pass Accuracy (%)',
      home: stats.pass_accuracy?.home || 0,
      away: stats.pass_accuracy?.away || 0,
    },
  ];

  const homeTeamName = homeTeam?.name || 'Home Team';
  const awayTeamName = awayTeam?.name || 'Away Team';

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-xl font-bold text-gray-900 mb-6">Match Statistics</h3>

      {/* Statistics Table */}
      <div className="overflow-x-auto mb-6">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-200">
              <th className="text-left py-3 px-4 font-semibold text-gray-700">Statistic</th>
              <th className="text-center py-3 px-4 font-semibold text-gray-700">{homeTeamName}</th>
              <th className="text-center py-3 px-4 font-semibold text-gray-700">{awayTeamName}</th>
            </tr>
          </thead>
          <tbody>
            {comparisonData.map((stat, index) => (
              <tr key={index} className="border-b border-gray-100 hover:bg-gray-50">
                <td className="py-3 px-4 text-gray-700">{stat.name}</td>
                <td className="py-3 px-4 text-center font-medium text-gray-900">{stat.home}</td>
                <td className="py-3 px-4 text-center font-medium text-gray-900">{stat.away}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Possession Chart */}
      {stats.possession && (
        <div className="mb-6">
          <h4 className="text-lg font-semibold text-gray-900 mb-4">Possession</h4>
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium text-gray-700">{homeTeamName}</span>
                <span className="text-sm font-bold text-gray-900">{stats.possession.home}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-4">
                <div
                  className="bg-primary-600 h-4 rounded-full transition-all duration-500"
                  style={{ width: `${stats.possession.home}%` }}
                ></div>
              </div>
            </div>
            <div className="flex-1">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium text-gray-700">{awayTeamName}</span>
                <span className="text-sm font-bold text-gray-900">{stats.possession.away}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-4">
                <div
                  className="bg-blue-600 h-4 rounded-full transition-all duration-500"
                  style={{ width: `${stats.possession.away}%` }}
                ></div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Comparison Bar Chart */}
      <div>
        <h4 className="text-lg font-semibold text-gray-900 mb-4">Statistics Comparison</h4>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={comparisonData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="home" fill="#3b82f6" name={homeTeamName} />
            <Bar dataKey="away" fill="#10b981" name={awayTeamName} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

export default MatchStatistics;
