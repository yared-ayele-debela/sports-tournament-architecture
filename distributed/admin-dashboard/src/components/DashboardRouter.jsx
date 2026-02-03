import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import AdminDashboard from '../pages/Dashboard';
import CoachDashboard from '../pages/CoachDashboard';
import RefereeDashboard from '../pages/RefereeDashboard';

/**
 * DashboardRouter component that shows the appropriate dashboard based on user role
 * and redirects to role-specific routes if needed
 */
export default function DashboardRouter() {
  const { loading } = useAuth();
  const { isAdmin, isCoach, isReferee } = usePermissions();
  const navigate = useNavigate();

  useEffect(() => {
    // Wait for auth to load
    if (loading) return;

    // Redirect to role-specific dashboard routes
    if (isCoach()) {
      navigate('/dashboard/coach', { replace: true });
      return;
    } else if (isReferee()) {
      navigate('/dashboard/referee', { replace: true });
      return;
    } else if (isAdmin()) {
      // Admin can stay on /dashboard or redirect to /dashboard/admin
      // For now, we'll keep them on /dashboard
      return;
    }
  }, [loading, isAdmin, isCoach, isReferee, navigate]);

  // Show loading while determining role
  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Loading dashboard...</div>
      </div>
    );
  }

  // Determine which dashboard to show based on role
  // Priority: Admin > Coach > Referee
  // Note: This is a fallback in case redirect doesn't happen
  if (isAdmin()) {
    return <AdminDashboard />;
  } else if (isCoach()) {
    return <CoachDashboard />;
  } else if (isReferee()) {
    return <RefereeDashboard />;
  }

  // Fallback to admin dashboard if no specific role
  return <AdminDashboard />;
}
