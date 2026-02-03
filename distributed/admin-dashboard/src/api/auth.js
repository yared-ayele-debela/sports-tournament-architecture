import { authApi, extractData, handleApiError } from '../lib/api';

export const authService = {
  login: async (email, password) => {
    try {
      const response = await authApi.post('/auth/login', { email, password });
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },

  logout: async () => {
    try {
      await authApi.post('/auth/logout');
      return { success: true };
    } catch (error) {
      throw handleApiError(error);
    }
  },

  getMe: async () => {
    try {
      const response = await authApi.get('/auth/me');
      return extractData(response);
    } catch (error) {
      throw handleApiError(error);
    }
  },
};
