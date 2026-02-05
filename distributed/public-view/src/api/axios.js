import axios from 'axios';

// Create axios instances for each service
export const tournamentApi = axios.create({
  baseURL:'http://localhost:8002/api/public',
  timeout: 30000, // Increased timeout to 30 seconds
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const teamApi = axios.create({
  baseURL:'http://localhost:8003/api/public',
  timeout: 30000, // Increased timeout to 30 seconds
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const matchApi = axios.create({
  baseURL:'http://localhost:8004/api/public',
  timeout: 30000, // Increased timeout to 30 seconds
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const resultsApi = axios.create({
  baseURL:'http://localhost:8005/api/public',
  timeout: 30000, // Increased timeout to 30 seconds
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor for logging (optional)
[tournamentApi, teamApi, matchApi, resultsApi].forEach(api => {
  api.interceptors.request.use(
    (config) => {
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
