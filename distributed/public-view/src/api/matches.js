import { matchApi } from './axios';

export const matchService = {
  // Get all matches with filters
  // Note: Public API doesn't have a general /matches endpoint
  // This will use tournament matches if tournament_id is provided, otherwise combine live/today/upcoming
  getAll: async (params = {}) => {
    // If tournament_id is provided, use MATCH-SERVICE public tournament matches endpoint
    // Handle both string and number, and filter out empty strings
    const tournamentId = params.tournament_id;
    if (tournamentId && tournamentId !== '' && tournamentId !== '0') {
      try {
        // Match Service public route:
        // GET http://localhost:8004/api/public/tournaments/{tournamentId}/matches
        const response = await matchApi.get(`/tournaments/${tournamentId}/matches`, { 
          params: {
            status: params.status,
            date: params.date || params.start_date,
            limit: params.limit || params.per_page,
            page: params.page,
          }
        });
        return response.data;
      } catch (error) {
        // Handle 404 specifically - tournament not found
        if (error.response?.status === 404) {
          const errorData = error.response?.data;
          if (errorData?.error_code === 'TOURNAMENT_NOT_FOUND') {
            // Tournament doesn't exist - return empty matches instead of throwing
            console.warn(`Tournament ${tournamentId} not found`);
            return {
              success: true,
              data: {
                matches: [],
                pagination: null,
              },
            };
          }
        }
        
        console.error('Error fetching tournament matches:', {
          tournamentId,
          error: error.message,
          status: error.response?.status,
          errorCode: error.response?.data?.error_code,
          url: error.config?.url,
          baseURL: error.config?.baseURL,
        });
        throw error;
      }
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
