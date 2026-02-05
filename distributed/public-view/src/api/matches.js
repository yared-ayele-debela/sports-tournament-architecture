import { matchApi } from './axios';
import { tournamentApi } from './axios';

export const matchService = {
  // Get all matches with filters
  // Note: Public API doesn't have a general /matches endpoint
  // This will use tournament matches if tournament_id is provided, otherwise combine live/today/upcoming
  getAll: async (params = {}) => {
    // If tournament_id is provided, use tournament matches endpoint
    if (params.tournament_id) {
      const response = await tournamentApi.get(`/tournaments/${params.tournament_id}/matches`, { 
        params: {
          status: params.status,
          date: params.date || params.start_date,
          limit: params.limit || params.per_page,
          page: params.page,
        }
      });
      return response.data;
    }

    // Otherwise, combine available endpoints based on status
    if (params.status === 'live' || params.status === 'in_progress') {
      const response = await matchApi.get('/matches/live');
      return response.data;
    }

    if (params.status === 'scheduled' || params.status === 'upcoming') {
      const response = await matchApi.get('/matches/upcoming', { params: { limit: params.limit || params.per_page } });
      return response.data;
    }

    // Default: combine today and upcoming
    const [todayResponse, upcomingResponse] = await Promise.all([
      matchApi.get('/matches/today'),
      matchApi.get('/matches/upcoming', { params: { limit: params.limit || params.per_page } }),
    ]);

    const todayMatches = todayResponse.data?.data?.matches || todayResponse.data?.matches || [];
    const upcomingMatches = upcomingResponse.data?.data?.matches || upcomingResponse.data?.matches || [];
    
    return {
      success: true,
      data: {
        matches: [...todayMatches, ...upcomingMatches],
      },
    };
  },

  // Get live matches
  getLive: async () => {
    const response = await matchApi.get('/matches/live');
    return response.data?.data?.matches || response.data?.matches || [];
  },  

  // Get today's matches
  getToday: async () => {
    const response = await matchApi.get('/matches/today');
    return response.data?.data?.matches || response.data?.matches || [];
  },

  // Get upcoming matches
  getUpcoming: async () => {
    const response = await matchApi.get('/matches/upcoming');
    return response.data?.data?.matches || response.data?.matches || [];
  },

  // Get match by ID
  getById: async (id) => {
    const response = await matchApi.get(`/matches/${id}`);
    return response.data;
  },

  // Get match events
  getEvents: async (matchId) => {
    const response = await matchApi.get(`/matches/${matchId}/events`);
    return response.data;
  },
};
