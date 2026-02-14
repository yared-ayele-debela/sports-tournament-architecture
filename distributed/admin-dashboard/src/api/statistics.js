import { 
  authApi, 
  tournamentApi, 
  teamApi, 
  matchApi, 
  resultsApi, 
  extractData, 
  handleApiError 
} from '../lib/api';

export const statisticsService = {
 
  getStatistics: async () => {
    try {
      // Fetch statistics from all services in parallel
      const [
        authStats,
        tournamentStats,
        teamStats,
        matchStats,
        resultsStats
      ] = await Promise.allSettled([
        // Auth Service Statistics (users, roles, permissions)
        authApi.get('/statistics').then(res => extractData(res)).catch(() => ({})),
        
        // Tournament Service Statistics (tournaments, sports, venues)
        tournamentApi.get('/statistics').then(res => extractData(res)).catch(() => ({})),
        
        // Team Service Statistics (teams, players)
        teamApi.get('/statistics').then(res => extractData(res)).catch(() => ({})),
        
        // Match Service Statistics (matches)
        matchApi.get('/statistics').then(res => extractData(res)).catch(() => ({})),
        
        // Results Service Statistics (standings, match results)
        resultsApi.get('/statistics').then(res => extractData(res)).catch(() => ({}))
      ]);

      return {
        // Auth Service
        users: authStats.status === 'fulfilled' ? (authStats.value?.users || { total: 0 }) : { total: 0 },
        roles: authStats.status === 'fulfilled' ? (authStats.value?.roles || { total: 0 }) : { total: 0 },
        permissions: authStats.status === 'fulfilled' ? (authStats.value?.permissions || { total: 0 }) : { total: 0 },
        
        // Tournament Service
        tournaments: tournamentStats.status === 'fulfilled' ? (tournamentStats.value?.tournaments || { total: 0, active: 0 }) : { total: 0, active: 0 },
        sports: tournamentStats.status === 'fulfilled' ? (tournamentStats.value?.sports || { total: 0 }) : { total: 0 },
        venues: tournamentStats.status === 'fulfilled' ? (tournamentStats.value?.venues || { total: 0 }) : { total: 0 },
        
        // Team Service
        teams: teamStats.status === 'fulfilled' ? (teamStats.value?.teams || { total: 0 }) : { total: 0 },
        players: teamStats.status === 'fulfilled' ? (teamStats.value?.players || { total: 0 }) : { total: 0 },
        
        // Match Service
        matches: matchStats.status === 'fulfilled' ? (matchStats.value?.matches || { total: 0 }) : { total: 0 },
        
        // Results Service
        results: resultsStats.status === 'fulfilled' ? (resultsStats.value || {}) : {},
      };
    } catch (error) {
      console.error('Error fetching statistics:', error);
      // Return default empty statistics on error
      return {
        users: { total: 0 },
        roles: { total: 0 },
        permissions: { total: 0 },
        tournaments: { total: 0, active: 0 },
        sports: { total: 0 },
        venues: { total: 0 },
        teams: { total: 0 },
        players: { total: 0 },
        matches: { total: 0 },
        results: {},
      };
    }
  },

  /**
   * Get statistics from a specific service
   */
  getAuthStatistics: async () => {
    try {
      const response = await authApi.get('/statistics');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getTournamentStatistics: async () => {
    try {
      const response = await tournamentApi.get('/statistics');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getTeamStatistics: async () => {
    try {
      const response = await teamApi.get('/statistics');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getMatchStatistics: async () => {
    try {
      const response = await matchApi.get('/statistics');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getResultsStatistics: async () => {
    try {
      const response = await resultsApi.get('/statistics');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  /**
   * Get chart data
   */
  getMatchesByStatus: async () => {
    try {
      const response = await matchApi.get('/statistics/matches-by-status');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getGoalsPerTournament: async () => {
    try {
      const response = await resultsApi.get('/statistics/goals-per-tournament');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getTopScoringTeams: async () => {
    try {
      const response = await resultsApi.get('/statistics/top-scoring-teams');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getCoachMatchesByStatus: async () => {
    try {
      const response = await matchApi.get('/statistics/coach/matches-by-status');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
