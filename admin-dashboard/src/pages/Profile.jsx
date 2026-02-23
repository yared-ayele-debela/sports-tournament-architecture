import { useState, useEffect } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuth } from '../context/AuthContext';
import { usersService } from '../api/users';
import { useToast } from '../context/ToastContext';
import { User, Save, Lock, Mail, UserCircle } from 'lucide-react';

export default function Profile() {
  const { user, fetchUser, loading: authLoading } = useAuth();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [errors, setErrors] = useState({});
  const [isEditing, setIsEditing] = useState(false);

  // Get the actual user object (handle nested structure from /auth/me)
  // The /auth/me endpoint returns { user: {...}, roles: [...], permissions: [...] }
  // But login might return just the user object directly
  // Also handle case where user might have id, name, email directly (from login)
  let userData = null;
  if (user) {
    if (user.user && typeof user.user === 'object' && user.user.id) {
      // Nested structure from /auth/me: { user: {...}, roles: [...] }
      userData = user.user;
    } else if (user.id || user.name || user.email) {
      // Direct user object (from login or stored)
      userData = user;
    }
  }

  // Populate form when user data loads
  useEffect(() => {
    if (userData) {
      setFormData({
        name: userData.name || '',
        email: userData.email || '',
        password: '',
        password_confirmation: '',
      });
    }
  }, [userData]);

  const mutation = useMutation({
    mutationFn: (data) => {
      return usersService.update(userData.id, data);
    },
    onSuccess: async (data) => {
      // Refresh user data in AuthContext
      await fetchUser();
      queryClient.invalidateQueries(['user', userData.id]);
      queryClient.invalidateQueries(['users']);
      toast.success('Profile updated successfully');
      setIsEditing(false);
      // Clear password fields
      setFormData((prev) => ({
        ...prev,
        password: '',
        password_confirmation: '',
      }));
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(error?.response?.data?.message || 'Failed to update profile');
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Validate password confirmation
    if (formData.password && formData.password !== formData.password_confirmation) {
      setErrors({ password_confirmation: ['Passwords do not match'] });
      return;
    }

    // Prepare data (exclude password fields if empty)
    const submitData = { ...formData };
    if (!submitData.password) {
      delete submitData.password;
      delete submitData.password_confirmation;
    }

    mutation.mutate(submitData);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleCancel = () => {
    setIsEditing(false);
    setErrors({});
    // Reset form to original user data
    if (userData) {
      setFormData({
        name: userData.name || '',
        email: userData.email || '',
        password: '',
        password_confirmation: '',
      });
    }
  };

  // Show loading state while auth is initializing
  if (authLoading || !user) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Loading profile...</div>
      </div>
    );
  }

  // If user exists but userData is still null, it means the structure is unexpected
  if (!userData) {
    console.error('User data structure unexpected:', user);
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-red-500">Error: Unable to load profile data</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">My Profile</h1>
          <p className="text-gray-600 mt-1">Manage your account information and settings</p>
        </div>
        {!isEditing && (
          <button
            onClick={() => setIsEditing(true)}
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2"
          >
            <User className="w-4 h-4" />
            <span>Edit Profile</span>
          </button>
        )}
      </div>

      {/* Profile Card */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="p-6">
          {/* Profile Header */}
          <div className="flex items-center space-x-4 mb-6 pb-6 border-b border-gray-200">
            <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center">
              <UserCircle className="w-10 h-10 text-primary-600" />
            </div>
            <div>
              <h2 className="text-xl font-semibold text-gray-900">{userData.name}</h2>
              <p className="text-gray-600">{userData.email}</p>
              {(() => {
                // Get roles from either user.roles (nested structure) or userData.roles (direct)
                const roles = user?.roles || userData?.roles || [];
                if (roles && roles.length > 0) {
                  return (
                    <div className="flex flex-wrap gap-2 mt-2">
                      {roles.map((role, index) => (
                        <span
                          key={index}
                          className="px-2 py-1 bg-primary-100 text-primary-800 rounded text-xs font-medium"
                        >
                          {role.name || role}
                        </span>
                      ))}
                    </div>
                  );
                }
                return null;
              })()}
            </div>
          </div>

          {/* Profile Form */}
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name Field */}
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center space-x-2">
                  <User className="w-4 h-4" />
                  <span>Full Name</span>
                </div>
              </label>
              {isEditing ? (
                <>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                      errors.name ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="Enter your full name"
                  />
                  {errors.name && (
                    <p className="mt-1 text-sm text-red-600">{errors.name[0]}</p>
                  )}
                </>
              ) : (
                <p className="text-gray-900">{userData.name}</p>
              )}
            </div>

            {/* Email Field */}
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center space-x-2">
                  <Mail className="w-4 h-4" />
                  <span>Email Address</span>
                </div>
              </label>
              {isEditing ? (
                <>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                      errors.email ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="Enter your email"
                  />
                  {errors.email && (
                    <p className="mt-1 text-sm text-red-600">{errors.email[0]}</p>
                  )}
                </>
              ) : (
                <p className="text-gray-900">{userData.email}</p>
              )}
            </div>

            {/* Password Fields (only show when editing) */}
            {isEditing && (
              <>
                <div>
                  <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                    <div className="flex items-center space-x-2">
                      <Lock className="w-4 h-4" />
                      <span>New Password</span>
                      <span className="text-xs text-gray-500 font-normal">(leave blank to keep current password)</span>
                    </div>
                  </label>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                      errors.password ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="Enter new password (min 8 characters)"
                  />
                  {errors.password && (
                    <p className="mt-1 text-sm text-red-600">{errors.password[0]}</p>
                  )}
                </div>

                <div>
                  <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-2">
                    <div className="flex items-center space-x-2">
                      <Lock className="w-4 h-4" />
                      <span>Confirm New Password</span>
                    </div>
                  </label>
                  <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    value={formData.password_confirmation}
                    onChange={handleChange}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                      errors.password_confirmation ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="Confirm new password"
                  />
                  {errors.password_confirmation && (
                    <p className="mt-1 text-sm text-red-600">{errors.password_confirmation[0]}</p>
                  )}
                </div>
              </>
            )}

            {/* Account Info (read-only) */}
            {!isEditing && (
              <div className="pt-6 border-t border-gray-200 space-y-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Account Created
                  </label>
                  <p className="text-gray-900">
                    {userData.created_at
                      ? new Date(userData.created_at).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                        })
                      : 'N/A'}
                  </p>
                </div>
                {userData.email_verified_at && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Email Verified
                    </label>
                    <p className="text-gray-900">
                      {new Date(userData.email_verified_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Action Buttons */}
            {isEditing && (
              <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                <button
                  type="button"
                  onClick={handleCancel}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  disabled={mutation.isLoading}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  disabled={mutation.isLoading}
                >
                  <Save className="w-4 h-4" />
                  <span>{mutation.isLoading ? 'Saving...' : 'Save Changes'}</span>
                </button>
              </div>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}
