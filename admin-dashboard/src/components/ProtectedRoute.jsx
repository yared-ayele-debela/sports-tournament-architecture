import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  // Show loading spinner while checking authentication
  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  // After loading completes, check authentication
  // If user has a token, allow access (API will handle invalid tokens with 401)
  const hasToken = !!localStorage.getItem('access_token');
  
  if (!hasToken && !isAuthenticated()) {
    return <Navigate to="/login" replace />;
  }

  // If token exists, allow access even if user object is null
  // (the API calls will handle 401 errors via interceptor)
  return children;
};
