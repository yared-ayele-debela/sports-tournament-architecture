import { teamApi, extractData, handleApiError } from '../lib/api';

export const teamsService = {
  list: async (params = {}) => {
    try {
      // The API can work with or without tournament_id
      // If tournament_id is provided, use the tournament-specific endpoint
      // Otherwise, use the general teams endpoint (useful for coaches to see their teams)
      const { tournament_id, ...otherParams } = params;
      
      if (tournament_id) {
        // Use tournament-specific endpoint
        const response = await teamApi.get(`/tournaments/${tournament_id}/teams`, { params: otherParams });
        const extracted = extractData(response);
        if (response.data && response.data.pagination) {
          return {
            data: extracted,
            pagination: response.data.pagination,
          };
        }
        return extracted;
      } else {
        // Use general teams endpoint (for coaches, this will filter by coach automatically)
        const response = await teamApi.get('/teams', { params: otherParams });
        const extracted = extractData(response);
        if (response.data && response.data.pagination) {
          return {
            data: extracted,
            pagination: response.data.pagination,
          };
        }
        return extracted;
      }
    } catch (error) {
      throw handleApiError(error);
    }
  },

  listByTournament: async (tournamentId, params = {}) => {
    try {
      const response = await teamApi.get(`/tournaments/${tournamentId}/teams`, { params });
      // Handle pagination for tournament teams
      const extracted = extractData(response);
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  get: async (id) => {
    try {
      const response = await teamApi.get(`/teams/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await teamApi.post('/teams', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await teamApi.put(`/teams/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await teamApi.delete(`/teams/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getPlayers: async (id, params = {}) => {
    try {
      const response = await teamApi.get(`/teams/${id}/players`, { params });
      // Handle pagination for players
      const extracted = extractData(response);
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getSquad: async (id, params = {}) => {
    try {
      // Squad endpoint is only available as public route
      const response = await teamApi.get(`/public/teams/${id}/squad`, { params });
      // Handle pagination for squad
      const extracted = extractData(response);
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getMatches: async (id, params = {}) => {
    try {
      // Matches endpoint is only available as public route
      const response = await teamApi.get(`/public/teams/${id}/matches`, { params });
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getStatistics: async (id) => {
    try {
      // Statistics endpoint is only available as public route
      const response = await teamApi.get(`/public/teams/${id}/statistics`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getOverview: async (id) => {
    try {
      // Overview endpoint includes statistics and is available as public route
      const response = await teamApi.get(`/public/teams/${id}/overview`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};

export const playersService = {
  list: async (params = {}) => {
    try {
      const response = await teamApi.get('/players', { params });
      // Handle pagination for players list
      const extracted = extractData(response);
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  get: async (id) => {
    try {
      const response = await teamApi.get(`/players/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await teamApi.post('/players', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await teamApi.put(`/players/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await teamApi.delete(`/players/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
