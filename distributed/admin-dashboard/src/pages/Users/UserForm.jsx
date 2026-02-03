import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { usersService } from '../../api/users';
import { rolesService } from '../../api/roles';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

export default function UserForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_ids: [],
  });
  const [errors, setErrors] = useState({});

  // Fetch user if editing
  const { data: userData, isLoading: loadingUser } = useQuery({
    queryKey: ['user', id],
    queryFn: () => usersService.get(id),
    enabled: isEdit,
  });

  // Fetch roles
  const { data: rolesData } = useQuery({
    queryKey: ['roles'],
    queryFn: () => rolesService.list({ per_page: 100 }),
  });

  const roles = rolesData?.data || [];

  // Populate form when user data loads
  useEffect(() => {
    if (userData && isEdit) {
      setFormData({
        name: userData.name || '',
        email: userData.email || '',
        password: '',
        password_confirmation: '',
        role_ids: userData.roles ? userData.roles.map((r) => r.id || r) : [],
      });
    }
  }, [userData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return usersService.update(id, data);
      }
      return usersService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['users']);
      toast.success(isEdit ? 'User updated successfully' : 'User created successfully');
      navigate('/users');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(error?.response?.data?.message || (isEdit ? 'Failed to update user' : 'Failed to create user'));
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Prepare data (exclude password fields if empty in edit mode)
    const submitData = { ...formData };
    if (isEdit && !submitData.password) {
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

  const handleRoleChange = (roleId) => {
    setFormData((prev) => {
      const roleIds = prev.role_ids || [];
      if (roleIds.includes(roleId)) {
        return { ...prev, role_ids: roleIds.filter((id) => id !== roleId) };
      }
      return { ...prev, role_ids: [...roleIds, roleId] };
    });
  };

  if (loadingUser) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/users')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Users
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit User' : 'Create New User'}
        </h1>
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Name */}
          <div>
            <label htmlFor="name" className="label">
              Name <span className="text-red-500">*</span>
            </label>
            <input
              id="name"
              name="name"
              type="text"
              value={formData.name}
              onChange={handleChange}
              className={`input ${errors.name ? 'border-red-500' : ''}`}
              required
            />
            {errors.name && (
              <p className="mt-1 text-sm text-red-600">{errors.name[0]}</p>
            )}
          </div>

          {/* Email */}
          <div>
            <label htmlFor="email" className="label">
              Email <span className="text-red-500">*</span>
            </label>
            <input
              id="email"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
              className={`input ${errors.email ? 'border-red-500' : ''}`}
              required
            />
            {errors.email && (
              <p className="mt-1 text-sm text-red-600">{errors.email[0]}</p>
            )}
          </div>

          {/* Password */}
          <div>
            <label htmlFor="password" className="label">
              Password {!isEdit && <span className="text-red-500">*</span>}
              {isEdit && <span className="text-gray-500 text-sm">(leave blank to keep current)</span>}
            </label>
            <input
              id="password"
              name="password"
              type="password"
              value={formData.password}
              onChange={handleChange}
              className={`input ${errors.password ? 'border-red-500' : ''}`}
              required={!isEdit}
            />
            {errors.password && (
              <p className="mt-1 text-sm text-red-600">{errors.password[0]}</p>
            )}
          </div>

          {/* Password Confirmation */}
          {formData.password && (
            <div>
              <label htmlFor="password_confirmation" className="label">
                Confirm Password <span className="text-red-500">*</span>
              </label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                value={formData.password_confirmation}
                onChange={handleChange}
                className={`input ${errors.password_confirmation ? 'border-red-500' : ''}`}
                required={!!formData.password}
              />
              {errors.password_confirmation && (
                <p className="mt-1 text-sm text-red-600">{errors.password_confirmation[0]}</p>
              )}
            </div>
          )}

          {/* Roles */}
          <div>
            <label className="label">Roles</label>
            <div className="space-y-2">
              {roles.length === 0 ? (
                <p className="text-sm text-gray-500">No roles available</p>
              ) : (
                roles.map((role) => (
                  <label key={role.id} className="flex items-center">
                    <input
                      type="checkbox"
                      checked={formData.role_ids.includes(role.id)}
                      onChange={() => handleRoleChange(role.id)}
                      className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span className="ml-2 text-sm text-gray-700">
                      {role.name}
                      {role.description && (
                        <span className="text-gray-500"> - {role.description}</span>
                      )}
                    </span>
                  </label>
                ))
              )}
            </div>
            {errors.role_ids && (
              <p className="mt-1 text-sm text-red-600">{errors.role_ids[0]}</p>
            )}
          </div>

          {/* Error Message */}
          {mutation.isError && mutation.error.message && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              {mutation.error.message}
            </div>
          )}

          {/* Submit Button */}
          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => navigate('/users')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={mutation.isLoading}
              className="btn btn-primary"
            >
              {mutation.isLoading ? 'Saving...' : isEdit ? 'Update User' : 'Create User'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
