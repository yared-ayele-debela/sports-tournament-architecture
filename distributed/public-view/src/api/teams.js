import { teamApi } from './axios';
import { tournamentApi } from './axios';

export const teamService = {
  // Get team by ID
  getById: async (id) => {
    const response = await teamApi.get(`/teams/${id}`);
    return response.data;
  },

  // Get team players
  getPlayers: async (teamId, params = {}) => {
    const response = await teamApi.get(`/teams/${teamId}/players`, { params });
    return response.data;
  },

  // Get team matches
  getMatches: async (teamId, params = {}) => {
    const response = await teamApi.get(`/teams/${teamId}/matches`, { params });
    return response.data;
  },

  // Get teams from a tournament (use team-service, not tournament-service)
  getTournamentTeams: async (tournamentId, params = {}) => {
    const response = await teamApi.get(`/tournaments/${tournamentId}/teams`, { params });
    return response.data;
  },
};
