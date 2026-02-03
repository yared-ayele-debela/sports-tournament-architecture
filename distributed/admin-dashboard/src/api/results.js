import { resultsApi, extractData, handleApiError } from '../lib/api';

export const resultsService = {
  getStandings: async (tournamentId, params = {}) => {
    try {
      const response = await resultsApi.get(`/tournaments/${tournamentId}/standings`, { params });
      // Handle pagination for standings
      const extracted = extractData(response);
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      // If standings is an array in data, return it
      if (Array.isArray(extracted)) {
        return { data: extracted, pagination: null };
      }
      // If standings is nested in data.standings
      if (extracted?.standings && Array.isArray(extracted.standings)) {
        return { data: extracted.standings, pagination: null };
      }
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  recalculateStandings: async (tournamentId) => {
    try {
      const response = await resultsApi.post(`/standings/recalculate/${tournamentId}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getResults: async (tournamentId, params = {}) => {
    try {
      const response = await resultsApi.get(`/tournaments/${tournamentId}/results`, { params });
      // Handle pagination for results
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

  getResult: async (id) => {
    try {
      const response = await resultsApi.get(`/results/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  finalizeMatch: async (matchId, data) => {
    try {
      const response = await resultsApi.post(`/matches/${matchId}/finalize`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getTeamStatistics: async (teamId) => {
    try {
      const response = await resultsApi.get(`/teams/${teamId}/statistics`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
