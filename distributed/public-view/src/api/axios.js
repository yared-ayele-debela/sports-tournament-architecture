import axios from 'axios';

// Shared axios configuration for performance optimization
// Note: In browsers, connection pooling and keep-alive are handled automatically
// by the browser's HTTP implementation (fetch/XMLHttpRequest)
const createOptimizedApi = (baseURL) => {
  return axios.create({
    baseURL,
    timeout: 30000,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      // Browser automatically handles Connection: keep-alive
      // Multiple requests to the same host reuse connections automatically
    },
    // Enable request/response compression (browser handles this automatically)
    decompress: true,
    // Max redirects
    maxRedirects: 5,
    // Browser's fetch/XMLHttpRequest automatically handles:
    // - Connection pooling (reuses TCP connections)
    // - Keep-alive (maintains connections for reuse)
    // - Request queuing and parallel execution
  });
};

// Create axios instances for each service with optimized configuration
export const tournamentApi = createOptimizedApi('http://localhost:8002/api/public');
export const teamApi = createOptimizedApi('http://localhost:8003/api/public');
export const matchApi = createOptimizedApi('http://localhost:8004/api/public');
export const resultsApi = createOptimizedApi('http://localhost:8005/api/public');

// Request interceptor for cancellation and logging
[tournamentApi, teamApi, matchApi, resultsApi].forEach(api => {
  api.interceptors.request.use(
    (config) => {
      // Add cancellation token support if not already present
      if (!config.signal && typeof AbortController !== 'undefined') {
        // React Query will provide its own signal, but we ensure compatibility
        config.signal = config.signal || new AbortController().signal;
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  // Response interceptor for error handling
  api.interceptors.response.use(
    (response) => {
      return response;
    },
    (error) => {
      // Enhanced error logging for debugging
      if (error.code === 'ECONNREFUSED' || error.message?.includes('Network Error')) {
        const serviceName = error.config?.baseURL?.includes('8002') ? 'Tournament' :
                           error.config?.baseURL?.includes('8003') ? 'Team' :
                           error.config?.baseURL?.includes('8004') ? 'Match' :
                           error.config?.baseURL?.includes('8005') ? 'Results' : 'Service';
        console.error(`❌ ${serviceName} Service is not running or not accessible.`);
        console.error(`   URL: ${error.config?.baseURL}`);
        console.error(`   Please ensure the service is running on the correct port.`);
        console.error(`   Run: ./check-services.sh to verify service status`);
      } else if (error.response?.status === 429) {
        console.error('Rate limit exceeded. Please try again later.');
      } else if (error.response?.status === 503) {
        console.error('Service temporarily unavailable. Please try again later.');
      } else if (error.response) {
        // Server responded with error status
        console.error(`API Error (${error.response.status}):`, error.response.data);
      } else if (error.request) {
        // Request was made but no response received
        console.error('No response from server. Service may be down.');
      } else {
        // Something else happened
        console.error('Request Error:', error.message);
      }
      return Promise.reject(error);
    }
  );
});
