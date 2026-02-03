import { matchApi, extractData, handleApiError } from '../lib/api';

export const matchesService = {
  list: async (params = {}) => {
    try {
      const response = await matchApi.get('/matches', { params });
      // For paginated responses, we need to preserve both data and pagination
      const extracted = extractData(response);
      // If the response has pagination at root level, preserve it
      if (response.data && response.data.pagination) {
        return {
          data: extracted,
          pagination: response.data.pagination,
        };
      }
      // Otherwise return as is
      return extracted;
    } catch (error) {
      throw handleApiError(error);
    }
  },

  get: async (id) => {
    try {
      const response = await matchApi.get(`/matches/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await matchApi.post('/matches', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await matchApi.put(`/matches/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await matchApi.delete(`/matches/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  updateStatus: async (id, status) => {
    try {
      const response = await matchApi.patch(`/matches/${id}/status`, { status });
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  generateSchedule: async (tournamentId) => {
    try {
      const response = await matchApi.post(`/tournaments/${tournamentId}/generate-schedule`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getEvents: async (matchId) => {
    try {
      const response = await matchApi.get(`/matches/${matchId}/events`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  createEvent: async (matchId, data) => {
    try {
      const response = await matchApi.post(`/matches/${matchId}/events`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
