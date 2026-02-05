import React, { useState, useMemo } from 'react';
import { ArrowUp, ArrowDown, ChevronDown, ChevronRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import Badge from '../common/Badge';

const StandingsTable = ({ standings = [], highlightTeamId = null, onTeamClick }) => {
  const [sortColumn, setSortColumn] = useState('position');
  const [sortDirection, setSortDirection] = useState('asc');
  const [expandedRows, setExpandedRows] = useState(new Set());

  const columns = [
    { key: 'position', label: 'Pos', sortable: true },
    { key: 'team', label: 'Team', sortable: false },
    { key: 'played', label: 'P', sortable: true },
    { key: 'won', label: 'W', sortable: true },
    { key: 'drawn', label: 'D', sortable: true },
    { key: 'lost', label: 'L', sortable: true },
    { key: 'goals_for', label: 'GF', sortable: true },
    { key: 'goals_against', label: 'GA', sortable: true },
    { key: 'goal_difference', label: 'GD', sortable: true },
    { key: 'points', label: 'Pts', sortable: true },
    { key: 'form', label: 'Form', sortable: false },
  ];

  const handleSort = (column) => {
    if (!column.sortable) return;
    
    if (sortColumn === column.key) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortColumn(column.key);
      setSortDirection('asc');
    }
  };

  const sortedStandings = useMemo(() => {
    if (!sortColumn) return standings;

    return [...standings].sort((a, b) => {
      let aValue, bValue;

      switch (sortColumn) {
        case 'position':
          aValue = a.position || 0;
          bValue = b.position || 0;
          break;
        case 'team':
          aValue = a.team?.name || '';
          bValue = b.team?.name || '';
          break;
        case 'played':
        case 'won':
        case 'drawn':
        case 'lost':
        case 'goals_for':
        case 'goals_against':
        case 'goal_difference':
        case 'points':
          aValue = a[sortColumn] || 0;
          bValue = b[sortColumn] || 0;
          break;
        default:
          return 0;
      }

      if (typeof aValue === 'string') {
        return sortDirection === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      }

      return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
    });
  }, [standings, sortColumn, sortDirection]);

  const toggleRow = (index) => {
    const newExpanded = new Set(expandedRows);
    if (newExpanded.has(index)) {
      newExpanded.delete(index);
    } else {
      newExpanded.add(index);
    }
    setExpandedRows(newExpanded);
  };

  const renderForm = (form) => {
    if (!form || form.length === 0) return <span className="text-gray-400">-</span>;
    
    return (
      <div className="flex gap-1">
        {form.slice(0, 5).map((result, idx) => (
          <span
            key={idx}
            className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white ${
              result === 'W' ? 'bg-green-600' :
              result === 'L' ? 'bg-red-600' :
              'bg-gray-600'
            }`}
            title={result === 'W' ? 'Win' : result === 'L' ? 'Loss' : 'Draw'}
          >
            {result}
          </span>
        ))}
      </div>
    );
  };

  if (standings.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow-md p-12 text-center">
        <p className="text-gray-600">No standings data available.</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50 border-b border-gray-200">
            <tr>
              {columns.map((column) => (
                <th
                  key={column.key}
                  className={`px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider ${
                    column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''
                  }`}
                  onClick={() => handleSort(column)}
                >
                  <div className="flex items-center gap-2">
                    {column.label}
                    {column.sortable && sortColumn === column.key && (
                      sortDirection === 'asc' ? (
                        <ArrowUp className="h-4 w-4" />
                      ) : (
                        <ArrowDown className="h-4 w-4" />
                      )
                    )}
                  </div>
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {sortedStandings.map((standing, index) => {
              const isHighlighted = highlightTeamId && (
                standing.team_id === highlightTeamId ||
                standing.team?.id === highlightTeamId
              );
              const isExpanded = expandedRows.has(index);

              return (
                <React.Fragment key={standing.id || index}>
                  <tr
                    className={`hover:bg-gray-50 transition-colors ${
                      isHighlighted ? 'bg-primary-50 border-l-4 border-primary-600' : ''
                    }`}
                  >
                    <td className="px-4 py-3 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-gray-900">{standing.position || index + 1}</span>
                        {onTeamClick && (
                          <button
                            onClick={() => toggleRow(index)}
                            className="text-gray-400 hover:text-gray-600"
                          >
                            {isExpanded ? (
                              <ChevronDown className="h-4 w-4" />
                            ) : (
                              <ChevronRight className="h-4 w-4" />
                            )}
                          </button>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        {standing.team?.logo && (
                          <img
                            src={standing.team.logo}
                            alt={standing.team.name}
                            className="h-8 w-8 object-contain"
                          />
                        )}
                        {onTeamClick ? (
                          <button
                            onClick={() => onTeamClick(standing.team_id || standing.team?.id)}
                            className="font-medium text-gray-900 hover:text-primary-600"
                          >
                            {standing.team?.name || 'Unknown Team'}
                          </button>
                        ) : (
                          <Link
                            to={`/teams/${standing.team_id || standing.team?.id}`}
                            className="font-medium text-gray-900 hover:text-primary-600"
                          >
                            {standing.team?.name || 'Unknown Team'}
                          </Link>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-center text-gray-900">{standing.played || 0}</td>
                    <td className="px-4 py-3 text-center text-green-600 font-medium">{standing.won || 0}</td>
                    <td className="px-4 py-3 text-center text-gray-600 font-medium">{standing.drawn || 0}</td>
                    <td className="px-4 py-3 text-center text-red-600 font-medium">{standing.lost || 0}</td>
                    <td className="px-4 py-3 text-center text-gray-900">{standing.goals_for || 0}</td>
                    <td className="px-4 py-3 text-center text-gray-900">{standing.goals_against || 0}</td>
                    <td className={`px-4 py-3 text-center font-medium ${
                      (standing.goal_difference || 0) > 0 ? 'text-green-600' :
                      (standing.goal_difference || 0) < 0 ? 'text-red-600' :
                      'text-gray-600'
                    }`}>
                      {standing.goal_difference > 0 ? '+' : ''}{standing.goal_difference || 0}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className="font-bold text-primary-600">{standing.points || 0}</span>
                    </td>
                    <td className="px-4 py-3">
                      {renderForm(standing.form)}
                    </td>
                  </tr>
                  {isExpanded && onTeamClick && (
                    <tr>
                      <td colSpan={columns.length} className="px-4 py-4 bg-gray-50">
                        <div className="text-sm text-gray-600">
                          <p><strong>Team ID:</strong> {standing.team_id || standing.team?.id}</p>
                          <p><strong>Win Rate:</strong> {standing.played > 0 
                            ? ((standing.won / standing.played) * 100).toFixed(1) 
                            : 0}%</p>
                        </div>
                      </td>
                    </tr>
                  )}
                </React.Fragment>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default StandingsTable;
