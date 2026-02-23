import { authApi, extractData, handleApiError } from '../lib/api';

export const usersService = {
  list: async (params = {}) => {
    try {
      const response = await authApi.get('/admin/users', { params });
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
      const response = await authApi.get(`/admin/users/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await authApi.post('/admin/users', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await authApi.put(`/admin/users/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await authApi.delete(`/admin/users/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
