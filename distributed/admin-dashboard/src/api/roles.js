import { authApi, extractData, handleApiError } from '../lib/api';

export const rolesService = {
  list: async (params = {}) => {
    try {
      const response = await authApi.get('/admin/roles', { params });
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
      const response = await authApi.get(`/admin/roles/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await authApi.post('/admin/roles', data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await authApi.put(`/admin/roles/${id}`, data);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await authApi.delete(`/admin/roles/${id}`);
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
