import { tournamentApi } from './axios';
import { teamApi } from './axios';
import { matchApi } from './axios';
import { executeParallelPartial } from './parallelRequests';

export const searchService = {
  // Global meta search (aggregates all services)
  // Optimized: Makes parallel requests to all services instead of sequential
  // This reduces total response time from sum of all requests to max of all requests
  // GET /api/public/search?q={query}
  searchAll: async (query, params = {}) => {
    // Make parallel requests to all search endpoints
    const requests = [
      tournamentApi.get('/search/tournaments', {
        params: { q: query, ...params },
      }).then(res => ({ type: 'tournaments', data: res.data })),
      teamApi.get('/search/teams', {
        params: { q: query, ...params },
      }).then(res => ({ type: 'teams', data: res.data })),
      matchApi.get('/search/matches', {
        params: { q: query, ...params },
      }).then(res => ({ type: 'matches', data: res.data })),
    ];

    // Execute all requests in parallel
    const { successful, errors } = await executeParallelPartial(requests, {
      timeout: 30000,
    });

    // Aggregate results from all services
    let tournaments = [];
    let teams = [];
    let matches = [];

    successful.forEach((item) => {
      // item.data contains { type, data } from our .then() transformation
      const result = item.data;
      const responseData = result?.data || result;
      
      if (result?.type === 'tournaments') {
        tournaments = Array.isArray(responseData) 
          ? responseData 
          : (responseData?.tournaments || responseData?.data || []);
      } else if (result?.type === 'teams') {
        teams = Array.isArray(responseData) 
          ? responseData 
          : (responseData?.teams || responseData?.data || []);
      } else if (result?.type === 'matches') {
        matches = Array.isArray(responseData) 
          ? responseData 
          : (responseData?.matches || responseData?.data || []);
      }
    });

    // Log errors but don't fail the entire request
    if (errors.length > 0) {
      console.warn('Some search services failed:', errors);
    }

    return {
      success: true,
      data: {
        tournaments,
        teams,
        matches,
      },
    };
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
