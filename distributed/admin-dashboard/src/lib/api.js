import axios from 'axios';

// Get base URLs from environment variables
const AUTH_SERVICE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8001/api';
const TOURNAMENT_SERVICE_URL = import.meta.env.VITE_TOURNAMENT_SERVICE_URL || 'http://localhost:8002/api';
const TEAM_SERVICE_URL = import.meta.env.VITE_TEAM_SERVICE_URL || 'http://localhost:8003/api';
const MATCH_SERVICE_URL = import.meta.env.VITE_MATCH_SERVICE_URL || 'http://localhost:8004/api';
const RESULTS_SERVICE_URL = import.meta.env.VITE_RESULTS_SERVICE_URL || 'http://localhost:8005/api';

// Create axios instances for each service
const createApiInstance = (baseURL) => {
  const instance = axios.create({
    baseURL,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  // Request interceptor to add auth token
  instance.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('access_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  // Response interceptor to handle errors
  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Token expired or invalid
        const token = localStorage.getItem('access_token');
        const currentPath = window.location.pathname;
        
        // Only clear and redirect if we actually had a token (means it expired/invalid)
        // If no token, let the component handle it (might be permission issue)
        if (token) {
          // Don't redirect if we're on login page or if it's the /auth/me endpoint
          // (that's handled by AuthContext during initialization)
          const isAuthMeEndpoint = error.config?.url?.includes('/auth/me');
          const isLoginPage = currentPath === '/login';
          
          if (!isLoginPage && !isAuthMeEndpoint) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            // Use a small delay to let React Query handle the error first
            setTimeout(() => {
              if (window.location.pathname !== '/login') {
                window.location.href = '/login';
              }
            }, 100);
          } else if (isAuthMeEndpoint) {
            // For /auth/me endpoint, just clear the token but don't redirect
            // Let AuthContext handle it
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
          }
        }
      }
      return Promise.reject(error);
    }
  );

  return instance;
};

// Export API instances
export const authApi = createApiInstance(AUTH_SERVICE_URL);
export const tournamentApi = createApiInstance(TOURNAMENT_SERVICE_URL);
export const teamApi = createApiInstance(TEAM_SERVICE_URL);
export const matchApi = createApiInstance(MATCH_SERVICE_URL);
export const resultsApi = createApiInstance(RESULTS_SERVICE_URL);

// Helper function to extract data from response
export const extractData = (response) => {
  return response.data?.data || response.data;
};

// Helper function to handle API errors
export const handleApiError = (error) => {
  if (error.response?.data) {
    return error.response.data;
  }
  return {
    success: false,
    message: error.message || 'An error occurred',
    errors: {},
  };
};
