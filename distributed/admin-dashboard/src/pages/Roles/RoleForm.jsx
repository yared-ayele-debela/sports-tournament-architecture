import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { rolesService } from '../../api/roles';
import { permissionsService } from '../../api/permissions';
import { ArrowLeft } from 'lucide-react';

export default function RoleForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    permission_ids: [],
  });
  const [errors, setErrors] = useState({});

  // Fetch role if editing
  const { data: roleData, isLoading: loadingRole } = useQuery({
    queryKey: ['role', id],
    queryFn: () => rolesService.get(id),
    enabled: isEdit,
  });

  // Fetch permissions
  const { data: permissionsData } = useQuery({
    queryKey: ['permissions'],
    queryFn: () => permissionsService.list({ per_page: 100 }),
  });

  // Handle both paginated and non-paginated responses
  const permissions = Array.isArray(permissionsData)
    ? permissionsData
    : permissionsData?.data || permissionsData || [];

  // Populate form when role data loads
  useEffect(() => {
    if (roleData && isEdit) {
      setFormData({
        name: roleData.name || '',
        description: roleData.description || '',
        permission_ids: roleData.permissions ? roleData.permissions.map((p) => p.id || p) : [],
      });
    }
  }, [roleData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return rolesService.update(id, data);
      }
      return rolesService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['roles']);
      navigate('/roles');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    mutation.mutate(formData);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handlePermissionChange = (permissionId) => {
    setFormData((prev) => {
      const permissionIds = prev.permission_ids || [];
      if (permissionIds.includes(permissionId)) {
        return { ...prev, permission_ids: permissionIds.filter((id) => id !== permissionId) };
      }
      return { ...prev, permission_ids: [...permissionIds, permissionId] };
    });
  };

  if (loadingRole) {
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
          onClick={() => navigate('/roles')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Roles
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Role' : 'Create New Role'}
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

          {/* Description */}
          <div>
            <label htmlFor="description" className="label">
              Description
            </label>
            <textarea
              id="description"
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows={3}
              className={`input ${errors.description ? 'border-red-500' : ''}`}
            />
            {errors.description && (
              <p className="mt-1 text-sm text-red-600">{errors.description[0]}</p>
            )}
          </div>

          {/* Permissions */}
          <div>
            <label className="label">Permissions</label>
            <div className="border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
              {permissions.length === 0 ? (
                <p className="text-sm text-gray-500">No permissions available</p>
              ) : (
                <div className="space-y-2">
                  {permissions.map((permission) => (
                    <label key={permission.id} className="flex items-start">
                      <input
                        type="checkbox"
                        checked={formData.permission_ids.includes(permission.id)}
                        onChange={() => handlePermissionChange(permission.id)}
                        className="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                      <div className="ml-2 flex-1">
                        <span className="text-sm font-medium text-gray-700">
                          {permission.name}
                        </span>
                        {permission.description && (
                          <p className="text-xs text-gray-500 mt-0.5">{permission.description}</p>
                        )}
                      </div>
                    </label>
                  ))}
                </div>
              )}
            </div>
            {errors.permission_ids && (
              <p className="mt-1 text-sm text-red-600">{errors.permission_ids[0]}</p>
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
              onClick={() => navigate('/roles')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={mutation.isLoading}
              className="btn btn-primary"
            >
              {mutation.isLoading ? 'Saving...' : isEdit ? 'Update Role' : 'Create Role'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
