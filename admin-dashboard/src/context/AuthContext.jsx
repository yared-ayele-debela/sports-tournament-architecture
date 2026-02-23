import { createContext, useContext, useState, useEffect } from 'react';
import { authApi, extractData } from '../lib/api';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [roles, setRoles] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Check if user is already logged in
    const initializeAuth = async () => {
      const token = localStorage.getItem('access_token');
      const storedUser = localStorage.getItem('user');
      
      if (token && storedUser) {
        try {
          // Set user from storage immediately for better UX
          const storedUserData = JSON.parse(storedUser);
          setUser(storedUserData.user || storedUserData);
          setRoles(storedUserData.roles || []);
          setPermissions(storedUserData.permissions || []);
          // Verify token is still valid
          await fetchUser();
        } catch (error) {
          console.error('Failed to verify token:', error);
          // Only clear if it's a 401 (unauthorized), not network errors
          if (error.response?.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            setUser(null);
            setRoles([]);
            setPermissions([]);
          }
          // For other errors, keep the user logged in (might be temporary network issue)
        }
      }
      setLoading(false);
    };

    initializeAuth();
  }, []);

  const fetchUser = async () => {
    try {
      const response = await authApi.get('/auth/me');
      const userData = extractData(response);
      
      // Extract user, roles, and permissions from response
      const userInfo = userData.user || userData;
      const userRoles = userData.roles || [];
      const userPermissions = userData.permissions || [];
      
      setUser(userInfo);
      setRoles(userRoles);
      setPermissions(userPermissions);
      
      // Store complete user data including roles and permissions
      const completeUserData = {
        user: userInfo,
        roles: userRoles,
        permissions: userPermissions
      };
      localStorage.setItem('user', JSON.stringify(completeUserData));
      
      return completeUserData;
    } catch (error) {
      // Don't call logout here - let the caller handle it
      // This allows us to handle errors more gracefully
      throw error;
    }
  };

  const login = async (email, password) => {
    try {
      const baseURL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8001/api';
      console.log('Making login request to:', baseURL + '/auth/login');
      const response = await authApi.post('/auth/login', { email, password });
      console.log('Login response:', response);
      const data = extractData(response);
      console.log('Extracted data:', data);
      
      // Handle both 'token' and 'access_token' for compatibility
      const token = data.token || data.access_token;
      
      if (token) {
        localStorage.setItem('access_token', token);
        // Fetch complete user data with roles and permissions
        const userData = await fetchUser();
        return { success: true, user: userData };
      }
      throw new Error('Invalid response from server - no token received');
    } catch (error) {
      // Log error for debugging
      console.error('Login error:', error);
      console.error('Error response:', error.response?.data);
      console.error('Error status:', error.response?.status);
      console.error('Error config:', error.config);
      
      // Handle network errors
      if (!error.response) {
        return {
          success: false,
          message: 'Network error: Unable to connect to the server. Please check if the auth service is running.',
          errors: {},
        };
      }
      
      // Extract error message from various possible response formats
      let errorMessage = 'Login failed';
      if (error.response?.data) {
        const responseData = error.response.data;
        errorMessage = responseData.message || 
                       responseData.error || 
                       (responseData.success === false ? responseData.message : 'Invalid credentials');
      } else if (error.message) {
        errorMessage = error.message;
      }
      
      return {
        success: false,
        message: errorMessage,
        errors: error.response?.data?.errors || {},
      };
    }
  };

  const logout = async () => {
    try {
      await authApi.post('/auth/logout');
    } catch (error) {
      // Continue with logout even if API call fails
    } finally {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      setUser(null);
      setRoles([]);
      setPermissions([]);
    }
  };

  const isAuthenticated = () => {
    return !!user && !!localStorage.getItem('access_token');
  };

  const value = {
    user,
    roles,
    permissions,
    loading,
    login,
    logout,
    fetchUser,
    isAuthenticated,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
