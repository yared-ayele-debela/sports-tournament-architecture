import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { resultsService } from '../../api/results';
import { tournamentsService } from '../../api/tournaments';
import { ArrowUp, ArrowDown, ArrowUpDown, Trophy } from 'lucide-react';

const SORTABLE_COLUMNS = {
  position: 'position',
  points: 'points',
  wins: 'won',
  draws: 'drawn',
  losses: 'lost',
  goals_for: 'goals_for',
  goals_against: 'goals_against',
  goal_difference: 'goal_difference',
};

export default function Standings() {
  const [tournamentFilter, setTournamentFilter] = useState('');
  const [sortColumn, setSortColumn] = useState('position');
  const [sortDirection, setSortDirection] = useState('asc');

  // Fetch tournaments
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Fetch standings
  const { data: standingsData, isLoading, error } = useQuery({
    queryKey: ['standings', tournamentFilter],
    queryFn: () => {
      if (!tournamentFilter) return { data: [] };
      return resultsService.getStandings(tournamentFilter, { per_page: 100 });
    },
    enabled: !!tournamentFilter,
  });

  const handleSort = (column) => {
    if (sortColumn === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortColumn(column);
      setSortDirection('asc');
    }
  };

  const getSortIcon = (column) => {
    if (sortColumn !== column) {
      return <ArrowUpDown className="w-4 h-4 text-gray-400" />;
    }
    return sortDirection === 'asc' ? (
      <ArrowUp className="w-4 h-4 text-primary-600" />
    ) : (
      <ArrowDown className="w-4 h-4 text-primary-600" />
    );
  };

  // Extract standings
  let standings = [];
  if (standingsData) {
    if (Array.isArray(standingsData)) {
      standings = standingsData;
    } else if (standingsData.data && Array.isArray(standingsData.data)) {
      standings = standingsData.data;
    } else if (standingsData.standings && Array.isArray(standingsData.standings)) {
      standings = standingsData.standings;
    }
  }

  // Sort standings
  const sortedStandings = [...standings].sort((a, b) => {
    const aValue = a[sortColumn] ?? a[SORTABLE_COLUMNS[sortColumn]] ?? 0;
    const bValue = b[sortColumn] ?? b[SORTABLE_COLUMNS[sortColumn]] ?? 0;
    
    if (sortColumn === 'position') {
      return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
    }
    
    const comparison = aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
    return sortDirection === 'asc' ? comparison : -comparison;
  });

  if (isLoading && !standingsData) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900 flex items-center">
          <Trophy className="w-8 h-8 mr-2 text-primary-600" />
          Tournament Standings
        </h1>
      </div>

      {/* Tournament Filter */}
      <div className="card mb-6">
        <label htmlFor="tournament" className="label">Select Tournament</label>
        <select
          id="tournament"
          value={tournamentFilter}
          onChange={(e) => setTournamentFilter(e.target.value)}
          className="input"
        >
          <option value="">Select a tournament</option>
          {tournaments.map((tournament) => (
            <option key={tournament.id} value={tournament.id}>
              {tournament.name}
            </option>
          ))}
        </select>
      </div>

      {/* Standings Table */}
      {error ? (
        <div className="card p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
          {error?.response?.data?.message || 'Failed to load standings'}
        </div>
      ) : !tournamentFilter ? (
        <div className="card p-8 text-center text-gray-500">
          Please select a tournament to view standings
        </div>
      ) : sortedStandings.length === 0 ? (
        <div className="card p-8 text-center text-gray-500">
          No standings available for this tournament
        </div>
      ) : (
        <div className="card">
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('position')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>Pos</span>
                      {getSortIcon('position')}
                    </div>
                  </th>
                  <th>Team</th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('points')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>Pts</span>
                      {getSortIcon('points')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('wins')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>W</span>
                      {getSortIcon('wins')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('draws')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>D</span>
                      {getSortIcon('draws')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('losses')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>L</span>
                      {getSortIcon('losses')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('goals_for')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>GF</span>
                      {getSortIcon('goals_for')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('goals_against')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>GA</span>
                      {getSortIcon('goals_against')}
                    </div>
                  </th>
                  <th
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => handleSort('goal_difference')}
                  >
                    <div className="flex items-center space-x-2">
                      <span>GD</span>
                      {getSortIcon('goal_difference')}
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                {sortedStandings.map((standing, index) => {
                  const team = standing.team || standing.team_details || {};
                  const position = standing.position ?? index + 1;
                  const points = standing.points ?? 0;
                  const wins = standing.won ?? standing.wins ?? 0;
                  const draws = standing.drawn ?? standing.draws ?? 0;
                  const losses = standing.lost ?? standing.losses ?? 0;
                  const goalsFor = standing.goals_for ?? 0;
                  const goalsAgainst = standing.goals_against ?? 0;
                  const goalDifference =
                    standing.goal_difference ?? goalsFor - goalsAgainst;

                  return (
                    <tr key={standing.id || standing.team_id || index}>
                      <td className="font-bold">
                        {position <= 3 ? (
                          <span className="flex items-center">
                            <Trophy
                              className={`w-4 h-4 mr-1 ${
                                position === 1
                                  ? 'text-yellow-500'
                                  : position === 2
                                  ? 'text-gray-400'
                                  : 'text-orange-500'
                              }`}
                            />
                            {position}
                          </span>
                        ) : (
                          position
                        )}
                      </td>
                      <td className="font-medium">
                        {team.name || `Team ${standing.team_id || 'N/A'}`}
                      </td>
                      <td className="font-bold text-primary-600">{points}</td>
                      <td>{wins}</td>
                      <td>{draws}</td>
                      <td>{losses}</td>
                      <td>{goalsFor}</td>
                      <td>{goalsAgainst}</td>
                      <td
                        className={`font-medium ${
                          goalDifference > 0
                            ? 'text-green-600'
                            : goalDifference < 0
                            ? 'text-red-600'
                            : 'text-gray-600'
                        }`}
                      >
                        {goalDifference > 0 ? '+' : ''}
                        {goalDifference}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
