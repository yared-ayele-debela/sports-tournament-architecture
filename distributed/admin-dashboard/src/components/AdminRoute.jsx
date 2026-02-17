import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import Unauthorized from './common/Unauthorized';

/**
 * AdminRoute component that restricts access to admin-only routes
 * Redirects non-admin users (referees, coaches) to an access denied page
 */
export const AdminRoute = ({ children }) => {
  const { loading } = useAuth();
  const { isAdmin } = usePermissions();

  // Show loading spinner while checking authentication
  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  // Check if user is an admin
  if (!isAdmin()) {
    return (
      <Unauthorized message="Access denied. Administrator privileges are required to access this page." />
    );
  }

  // User is an admin, allow access
  return children;
};
