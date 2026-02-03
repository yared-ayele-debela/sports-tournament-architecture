import { tournamentApi, extractData, handleApiError } from '../lib/api';

export const tournamentsService = {
  list: async (params = {}) => {
    try {
      const response = await tournamentApi.get('/tournaments', { params });
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
      const response = await tournamentApi.get(`/tournaments/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await tournamentApi.post('/tournaments', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await tournamentApi.put(`/tournaments/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await tournamentApi.delete(`/tournaments/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  updateStatus: async (id, status) => {
    try {
      const response = await tournamentApi.patch(`/tournaments/${id}/status`, { status });
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getMatches: async (id, params = {}) => {
    try {
      const response = await tournamentApi.get(`/tournaments/${id}/matches`, { params });
      // Handle pagination for matches
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

  getTeams: async (id, params = {}) => {
    try {
      const response = await tournamentApi.get(`/tournaments/${id}/teams`, { params });
      // Handle pagination for teams
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

  getStandings: async (id) => {
    try {
      const response = await tournamentApi.get(`/tournaments/${id}/standings`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getStatistics: async (id) => {
    try {
      const response = await tournamentApi.get(`/tournaments/${id}/statistics`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};

export const sportsService = {
  list: async (params = {}) => {
    try {
      const response = await tournamentApi.get('/sports', { params });
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
      const response = await tournamentApi.get(`/sports/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await tournamentApi.post('/sports', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await tournamentApi.put(`/sports/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await tournamentApi.delete(`/sports/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};

export const venuesService = {
  list: async (params = {}) => {
    try {
      const response = await tournamentApi.get('/venues', { params });
      // Handle pagination for venues
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
      const response = await tournamentApi.get(`/venues/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await tournamentApi.post('/venues', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await tournamentApi.put(`/venues/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await tournamentApi.delete(`/venues/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
