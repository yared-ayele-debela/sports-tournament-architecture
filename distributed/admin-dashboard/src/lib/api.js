import axios from 'axios';

// Get base URLs from environment variables
const AUTH_SERVICE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://127.0.0.1:8001/api';
const TOURNAMENT_SERVICE_URL = import.meta.env.VITE_TOURNAMENT_SERVICE_URL || 'http://127.0.0.1:8002/api';
const TEAM_SERVICE_URL = import.meta.env.VITE_TEAM_SERVICE_URL || 'http://127.0.0.1:8003/api';
const MATCH_SERVICE_URL = import.meta.env.VITE_MATCH_SERVICE_URL || 'http://127.0.0.1:8004/api';
const RESULTS_SERVICE_URL = import.meta.env.VITE_RESULTS_SERVICE_URL || 'http://127.0.0.1:8005/api';

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
        const requestUrl = error.config?.url || '';
        
        // List of public endpoint patterns that don't require authentication
        // These are public tournament read-only endpoints that should not trigger redirect
        const publicEndpointPatterns = [
          '/auth/me',
          '/standings',
          '/statistics',
          '/matches',
          '/teams',
          '/overview',
        ];
        
        // Check if this is a public endpoint (read-only tournament data)
        // Match patterns like /tournaments/{id}/standings, /tournaments/{id}/statistics, etc.
        const isPublicEndpoint = publicEndpointPatterns.some(pattern => 
          requestUrl.includes(pattern) && !requestUrl.includes('/auth/login')
        );
        
        // Only clear and redirect if we actually had a token (means it expired/invalid)
        // If no token, let the component handle it (might be permission issue)
        if (token) {
          // Don't redirect for public endpoints, login page, or /auth/me endpoint
          const isAuthMeEndpoint = requestUrl.includes('/auth/me');
          const isLoginPage = currentPath === '/login';
          
          if (!isLoginPage && !isAuthMeEndpoint && !isPublicEndpoint) {
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
          // For public endpoints, don't redirect - let the component handle the error
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
