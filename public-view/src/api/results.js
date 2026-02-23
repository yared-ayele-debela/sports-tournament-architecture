import { resultsApi } from './axios';

export const resultsService = {
  // Get tournament standings
  getStandings: async (tournamentId) => {
    const response = await resultsApi.get(`/tournaments/${tournamentId}/standings`);
    return response.data;
  },

  // Get tournament statistics
  getStatistics: async (tournamentId) => {
    const response = await resultsApi.get(`/tournaments/${tournamentId}/statistics`);
    return response.data;
  },

  // Get top scorers
  getTopScorers: async (tournamentId, params = {}) => {
    const response = await resultsApi.get(`/tournaments/${tournamentId}/top-scorers`, { params });
    return response.data;
  },
};
