import { useState, useMemo, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { Download, Printer, Trophy, Target, TrendingUp } from 'lucide-react';
import { tournamentService } from '../api/tournaments';
import { resultsService } from '../api/results';
import StandingsTable from '../components/standings/StandingsTable';
import Loading from '../components/common/Loading';
import ErrorMessage from '../components/common/ErrorMessage';
import Breadcrumbs from '../components/layout/Breadcrumbs';
import Button from '../components/common/Button';

const Standings = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [selectedTournamentId, setSelectedTournamentId] = useState(
    searchParams.get('tournament_id') || ''
  );
  const [activeGroup, setActiveGroup] = useState('all');

  // Fetch tournaments
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournamentsForStandings'],
    queryFn: () => tournamentService.getAll({ limit: 100, status: 'ongoing' }),
  });

  // Fetch standings (from Results Service)
  const { data: standingsData, isLoading, error } = useQuery({
    queryKey: ['standings', selectedTournamentId],
    queryFn: () => resultsService.getStandings(selectedTournamentId),
    enabled: !!selectedTournamentId,
  });

  const tournaments = tournamentsData?.data?.data || tournamentsData?.data || [];
  const standings = standingsData?.data?.standings || standingsData?.data || [];

  // Set default tournament to most active (first ongoing tournament)
  useEffect(() => {
    if (!selectedTournamentId && tournaments.length > 0) {
      const mostActive = tournaments.find(t => t.status === 'ongoing') || tournaments[0];
      if (mostActive) {
        setSelectedTournamentId(mostActive.id.toString());
        setSearchParams({ tournament_id: mostActive.id.toString() }, { replace: true });
      }
    }
  }, [tournaments, selectedTournamentId, setSearchParams]);

  // Update URL when tournament changes
  const handleTournamentChange = (tournamentId) => {
    setSelectedTournamentId(tournamentId);
    setSearchParams({ tournament_id: tournamentId }, { replace: true });
  };

  // Group standings by group (if applicable)
  const groupedStandings = useMemo(() => {
    if (!standings || standings.length === 0) return {};

    const groups = {};
    standings.forEach((standing) => {
      const groupName = standing.group_name || 'all';
      if (!groups[groupName]) {
        groups[groupName] = [];
      }
      groups[groupName].push(standing);
    });

    return groups;
  }, [standings]);

  const hasGroups = Object.keys(groupedStandings).length > 1 && !groupedStandings['all'];

  // Get current standings based on active group
  const currentStandings = useMemo(() => {
    if (hasGroups) {
      return groupedStandings[activeGroup] || [];
    }
    return standings;
  }, [hasGroups, groupedStandings, activeGroup, standings]);

  // Calculate statistics summary
  const statsSummary = useMemo(() => {
    const totalMatches = standings.reduce((sum, s) => sum + (s.played || 0), 0);
    const totalGoals = standings.reduce((sum, s) => sum + (s.goals_for || 0), 0);
    const avgGoals = totalMatches > 0 ? (totalGoals / totalMatches).toFixed(2) : 0;

    return {
      totalMatches,
      totalGoals,
      avgGoals,
    };
  }, [standings]);

  // Export to CSV
  const handleExportCSV = () => {
    if (currentStandings.length === 0) return;

    const headers = ['Position', 'Team', 'Played', 'Won', 'Drawn', 'Lost', 'GF', 'GA', 'GD', 'Points'];
    const rows = currentStandings.map(s => [
      s.position || '',
      s.team?.name || '',
      s.played || 0,
      s.won || 0,
      s.drawn || 0,
      s.lost || 0,
      s.goals_for || 0,
      s.goals_against || 0,
      s.goal_difference || 0,
      s.points || 0,
    ]);

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `standings-${selectedTournamentId}-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  // Print standings
  const handlePrint = () => {
    window.print();
  };

  const selectedTournament = tournaments.find(t => t.id.toString() === selectedTournamentId);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs items={[{ label: 'Standings', path: '/standings' }]} />

        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Standings</h1>
          <p className="text-gray-600">View tournament standings and statistics</p>
        </div>

        {/* Tournament Selector */}
        <div className="bg-white rounded-lg shadow-md p-4 mb-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Select Tournament
              </label>
              <select
                value={selectedTournamentId}
                onChange={(e) => handleTournamentChange(e.target.value)}
                className="w-full sm:w-auto border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="">Select a tournament...</option>
                {tournaments.map((tournament) => (
                  <option key={tournament.id} value={tournament.id}>
                    {tournament.name} ({tournament.status})
                  </option>
                ))}
              </select>
            </div>

            {/* Export Options */}
            {selectedTournamentId && currentStandings.length > 0 && (
              <div className="flex items-end gap-2">
                <Button
                  variant="outline"
                  onClick={handleExportCSV}
                  className="flex items-center gap-2"
                >
                  <Download className="h-4 w-4" />
                  Export CSV
                </Button>
                <Button
                  variant="outline"
                  onClick={handlePrint}
                  className="flex items-center gap-2"
                >
                  <Printer className="h-4 w-4" />
                  Print
                </Button>
              </div>
            )}
          </div>
        </div>

        {/* Statistics Summary */}
        {selectedTournamentId && statsSummary.totalMatches > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm text-gray-600">Total Matches</span>
                <Target className="h-5 w-5 text-primary-600" />
              </div>
              <p className="text-3xl font-bold text-gray-900">{statsSummary.totalMatches}</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm text-gray-600">Total Goals</span>
                <Trophy className="h-5 w-5 text-yellow-600" />
              </div>
              <p className="text-3xl font-bold text-gray-900">{statsSummary.totalGoals}</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm text-gray-600">Avg Goals/Match</span>
                <TrendingUp className="h-5 w-5 text-green-600" />
              </div>
              <p className="text-3xl font-bold text-primary-600">{statsSummary.avgGoals}</p>
            </div>
          </div>
        )}

        {/* Results */}
        {!selectedTournamentId ? (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Trophy className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600">Please select a tournament to view standings.</p>
          </div>
        ) : isLoading ? (
          <Loading />
        ) : error ? (
          <ErrorMessage
            message="Failed to load standings. Please try again later."
            onRetry={() => window.location.reload()}
          />
        ) : currentStandings.length > 0 ? (
          <>
            {/* Group Tabs (if applicable) */}
            {hasGroups && (
              <div className="bg-white rounded-lg shadow-md mb-6">
                <div className="border-b border-gray-200">
                  <nav className="flex overflow-x-auto">
                    {Object.keys(groupedStandings).map((groupName) => (
                      <button
                        key={groupName}
                        onClick={() => setActiveGroup(groupName)}
                        className={`px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                          activeGroup === groupName
                            ? 'border-primary-600 text-primary-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                      >
                        Group {groupName}
                      </button>
                    ))}
                  </nav>
                </div>
              </div>
            )}

            {/* Tournament Info */}
            {selectedTournament && (
              <div className="bg-white rounded-lg shadow-md p-4 mb-6">
                <h2 className="text-xl font-bold text-gray-900">{selectedTournament.name}</h2>
                {standingsData?.data?.last_updated && (
                  <p className="text-sm text-gray-600 mt-1">
                    Last updated: {new Date(standingsData.data.last_updated).toLocaleString()}
                  </p>
                )}
              </div>
            )}

            {/* Standings Table */}
            <StandingsTable
              standings={currentStandings}
              highlightTeamId={null}
            />
          </>
        ) : (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Trophy className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600">No standings data available for this tournament.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Standings;
