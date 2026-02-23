import axios from 'axios';

// Get base URLs from environment variables
// In Docker, these will be proxied by Vite to the service names
const AUTH_SERVICE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || '/api/auth';
const TOURNAMENT_SERVICE_URL = import.meta.env.VITE_TOURNAMENT_SERVICE_URL || '/api/tournaments';
const TEAM_SERVICE_URL = import.meta.env.VITE_TEAM_SERVICE_URL || '/api/teams';
const MATCH_SERVICE_URL = import.meta.env.VITE_MATCH_SERVICE_URL || '/api/matches';
const RESULTS_SERVICE_URL = import.meta.env.VITE_RESULTS_SERVICE_URL || '/api/results';

// Create axios instances for each service
const createApiInstance = (baseURL) => {
  const instance = axios.create({
    baseURL,
    headers: {
      'Accept': 'application/json',
    },
  });

  // Request interceptor to add auth token and set Content-Type conditionally
  instance.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('access_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      
      // Handle Content-Type for file uploads
      // FormData needs multipart/form-data which axios sets automatically
      // We must delete the Content-Type header for FormData so axios can set it with boundary
      if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
      } else if (config.data && typeof config.data === 'object') {
        // Only set Content-Type to application/json for JSON data
        config.headers['Content-Type'] = 'application/json';
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
