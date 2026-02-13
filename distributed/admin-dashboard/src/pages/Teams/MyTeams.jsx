import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { teamsService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import { Search, Edit, Eye, Users } from 'lucide-react';

export default function MyTeams() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const navigate = useNavigate();
  const toast = useToast();
  const { isCoach } = usePermissions();

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Fetch coach's teams - the API should filter by coach automatically
  const { data, isLoading, error } = useQuery({
    queryKey: ['teams', 'my-teams', debouncedSearchTerm],
    queryFn: async () => {
      // When coach is logged in, the API should return only their teams
      // We'll use a special endpoint or query parameter
      const result = await teamsService.list({ per_page: 100 });
      return result;
    },
    retry: false,
  });

  // Extract teams - handle different response structures
  // The API returns: { success: true, data: [...], pagination: {...} }
  // extractData() returns: response.data?.data || response.data
  // teamsService.list() returns: { data: [...], pagination: {...} } or just [...]
  let teams = [];
  
  if (!data) {
    teams = [];
  } else if (Array.isArray(data)) {
    // If data is directly an array (extractData returned the array directly)
    teams = data;
  } else if (data && typeof data === 'object') {
    // Handle { data: [...], pagination: {...} } structure (from teamsService when pagination exists)
    if (Array.isArray(data.data)) {
      teams = data.data;
    }
    // Handle nested structure { data: { data: [...], pagination: {...} } }
    else if (data.data && typeof data.data === 'object') {
      if (Array.isArray(data.data.data)) {
        teams = data.data.data;
      } else if (Array.isArray(data.data)) {
        teams = data.data;
      }
    }
  }
  
  // Debug log to help troubleshoot (only in development)
  if (import.meta.env.DEV) {
    console.log('MyTeams - Data extraction:', { 
      hasData: !!data,
      dataType: typeof data,
      isArray: Array.isArray(data),
      dataKeys: data && typeof data === 'object' ? Object.keys(data) : null,
      teamsCount: teams.length,
      firstTeam: teams.length > 0 ? teams[0] : null
    });
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Failed to load your teams'}
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">My Teams</h1>
          <p className="text-gray-600 mt-1">Manage your teams and players</p>
        </div>
      </div>

      {/* Search */}
      <div className="card mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search teams..."
            className="input pl-10"
          />
        </div>
      </div>

      {/* Teams Table */}
      <div className="card">
        {teams.length === 0 ? (
          <div className="text-center py-12">
            <Users className="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600 text-lg mb-2">No teams found</p>
            <p className="text-gray-500">You are not assigned to any teams yet.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Tournament</th>
                  <th>Players</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {teams
                  .filter((team) => {
                    if (!debouncedSearchTerm) return true;
                    const search = debouncedSearchTerm.toLowerCase();
                    return (
                      team.name?.toLowerCase().includes(search) ||
                      team.tournament?.name?.toLowerCase().includes(search)
                    );
                  })
                  .map((team) => (
                    <tr key={team.id}>
                      <td>{team.id}</td>
                      <td className="font-medium">
                        <div className="flex items-center space-x-2">
                          {team.logo && (
                            <img
                              src={team.logo}
                              alt={team.name}
                              className="w-8 h-8 rounded-full object-cover"
                              onError={(e) => {
                                e.target.style.display = 'none';
                              }}
                            />
                          )}
                          <span>{team.name}</span>
                        </div>
                      </td>
                      <td>
                        {team.tournament?.name || team.tournament_id || 'N/A'}
                      </td>
                      <td>{team.players?.length || team.players_count || 0}</td>
                      <td>
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => navigate(`/teams/${team.id}`)}
                            className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                            title="View"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => navigate(`/teams/${team.id}/edit`)}
                            className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                            title="Edit"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => navigate(`/teams/${team.id}/players`)}
                            className="p-2 text-green-600 hover:bg-green-50 rounded"
                            title="See Players"
                          >
                            <Users className="w-4 h-4" />
                          </button>
                        </div>
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
