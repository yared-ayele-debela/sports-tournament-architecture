import { tournamentApi } from './axios';
import { teamApi } from './axios';
import { matchApi } from './axios';

export const searchService = {
  // Global meta search (aggregates all services)
  // GET /api/public/search?q={query}
  searchAll: async (query, params = {}) => {
    const response = await tournamentApi.get('/search', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  // Search tournaments only
  // GET /api/public/search/tournaments?q={query}
  searchTournaments: async (query, params = {}) => {
    const response = await tournamentApi.get('/search/tournaments', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  // Search teams only
  // GET /api/public/search/teams?q={query}
  searchTeams: async (query, params = {}) => {
    const response = await teamApi.get('/search/teams', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  // Search matches only
  // GET /api/public/search/matches?q={query}
  searchMatches: async (query, params = {}) => {
    const response = await matchApi.get('/search/matches', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },
};
