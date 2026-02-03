import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../context/ToastContext';
import { LogIn } from 'lucide-react';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { login, isAuthenticated, roles } = useAuth();
  const { isAdmin, isCoach, isReferee } = usePermissions();
  const navigate = useNavigate();
  const toast = useToast();

  useEffect(() => {
    // Redirect if already authenticated based on role
    if (isAuthenticated() && roles && roles.length > 0) {
      const hasAdminRole = roles.some(r => (r.name || r) === 'Administrator');
      const hasCoachRole = roles.some(r => (r.name || r) === 'Coach');
      const hasRefereeRole = roles.some(r => (r.name || r) === 'Referee');
      
      if (hasAdminRole) {
        navigate('/dashboard', { replace: true });
      } else if (hasCoachRole) {
        navigate('/dashboard/coach', { replace: true });
      } else if (hasRefereeRole) {
        navigate('/dashboard/referee', { replace: true });
      } else {
        navigate('/dashboard', { replace: true });
      }
    } else if (isAuthenticated()) {
      // If authenticated but roles not loaded yet, go to main dashboard
      navigate('/dashboard', { replace: true });
    }
  }, [isAuthenticated, roles, navigate]);

  const handleSubmit = async (e) => {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    
    // Prevent multiple submissions
    if (loading) {
      return false;
    }
    
    setError('');
    setLoading(true);

    try {
      console.log('Attempting login with:', { email });
      const result = await login(email, password);
      console.log('Login result:', result);
      
      if (result && result.success) {
        toast.success('Login successful');
        // Small delay to ensure state is updated, then redirect based on role
        setTimeout(() => {
          // Get roles from the result or wait for them to be available
          const userRoles = result.user?.roles || roles || [];
          
          // Determine dashboard based on role
          // Priority: Admin > Coach > Referee
          const hasAdminRole = userRoles.some(r => (r.name || r) === 'Administrator');
          const hasCoachRole = userRoles.some(r => (r.name || r) === 'Coach');
          const hasRefereeRole = userRoles.some(r => (r.name || r) === 'Referee');
          
          if (hasAdminRole) {
            navigate('/dashboard', { replace: true });
          } else if (hasCoachRole) {
            navigate('/dashboard/coach', { replace: true });
          } else if (hasRefereeRole) {
            navigate('/dashboard/referee', { replace: true });
          } else {
            // Fallback to main dashboard
            navigate('/dashboard', { replace: true });
          }
        }, 200);
      } else {
        const errorMsg = result?.message || 'Login failed. Please check your credentials and try again.';
        setError(errorMsg);
        toast.error(errorMsg);
        console.error('Login failed:', errorMsg);
        setLoading(false);
      }
    } catch (err) {
      console.error('Login exception:', err);
      const errorMsg = err.message || 'An unexpected error occurred. Please try again.';
      setError(errorMsg);
      toast.error(errorMsg);
      setLoading(false);
    }
    
    return false;
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 px-4">
      <div className="max-w-md w-full">
        <div className="card">
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mb-4">
              <LogIn className="w-8 h-8 text-primary-600" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Admin Dashboard</h1>
            <p className="text-gray-600">Sign in to manage your tournament system</p>
          </div>

          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              {error}
            </div>
          )}

          <form 
            onSubmit={handleSubmit} 
            className="space-y-4" 
            noValidate
            onKeyDown={(e) => {
              // Prevent form submission on Enter key if loading
              if (e.key === 'Enter' && loading) {
                e.preventDefault();
                e.stopPropagation();
              }
            }}
          >
            <div>
              <label htmlFor="email" className="label">
                Email Address
              </label>
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="input"
                placeholder="admin@example.com"
                required
                autoComplete="email"
                disabled={loading}
              />
            </div>

            <div>
              <label htmlFor="password" className="label">
                Password
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="input"
                placeholder="Enter your password"
                required
                autoComplete="current-password"
                disabled={loading}
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="btn btn-primary w-full flex items-center justify-center"
              onClick={(e) => {
                // Additional safeguard
                if (loading) {
                  e.preventDefault();
                  e.stopPropagation();
                }
              }}
            >
              {loading ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                  Signing in...
                </>
              ) : (
                'Sign In'
              )}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
