import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { rolesService } from '../../api/roles';
import { ArrowLeft, Edit, Trash2, Users, Shield } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export default function RoleDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);

  const { data: role, isLoading, error } = useQuery({
    queryKey: ['role', id],
    queryFn: () => rolesService.get(id),
  });

  const deleteMutation = useMutation({
    mutationFn: () => rolesService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['roles']);
      navigate('/roles');
    },
  });

  const handleDelete = () => {
    deleteMutation.mutate();
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-8">
        <div className="card">
          <p className="text-red-600">Error loading role: {error.message}</p>
        </div>
      </div>
    );
  }

  if (!role) {
    return (
      <div className="p-8">
        <div className="card">
          <p className="text-gray-600">Role not found</p>
        </div>
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
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-bold text-gray-900">Role Details</h1>
          <div className="flex space-x-3">
            <button
              onClick={() => navigate(`/roles/${id}/edit`)}
              className="btn btn-primary flex items-center"
            >
              <Edit className="w-5 h-5 mr-2" />
              Edit
            </button>
            <button
              onClick={() => setShowDeleteDialog(true)}
              className="btn btn-danger flex items-center"
            >
              <Trash2 className="w-5 h-5 mr-2" />
              Delete
            </button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Basic Information */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
          <div className="space-y-4">
            <div>
              <p className="text-sm text-gray-500">ID</p>
              <p className="font-medium text-gray-900">{role.id}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Name</p>
              <p className="font-medium text-gray-900">{role.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Description</p>
              <p className="font-medium text-gray-900">{role.description || '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Created At</p>
              <p className="font-medium text-gray-900">
                {new Date(role.created_at).toLocaleString()}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Updated At</p>
              <p className="font-medium text-gray-900">
                {new Date(role.updated_at).toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        {/* Permissions */}
        <div className="card">
          <div className="flex items-center mb-4">
            <Shield className="w-5 h-5 text-gray-400 mr-2" />
            <h2 className="text-xl font-semibold text-gray-900">Permissions</h2>
            <span className="ml-auto px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
              {role.permissions?.length || 0}
            </span>
          </div>
          {role.permissions && role.permissions.length > 0 ? (
            <div className="space-y-2">
              {role.permissions.map((permission, index) => (
                <div
                  key={index}
                  className="p-3 bg-gray-50 rounded-lg border border-gray-200"
                >
                  <p className="font-medium text-gray-900">{permission.name || permission}</p>
                  {permission.description && (
                    <p className="text-sm text-gray-600 mt-1">{permission.description}</p>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500">No permissions assigned</p>
          )}
        </div>

        {/* Assigned Users */}
        {role.users && role.users.length > 0 && (
          <div className="card md:col-span-2">
            <div className="flex items-center mb-4">
              <Users className="w-5 h-5 text-gray-400 mr-2" />
              <h2 className="text-xl font-semibold text-gray-900">Assigned Users</h2>
              <span className="ml-auto px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
                {role.users.length}
              </span>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              {role.users.map((user, index) => (
                <div
                  key={index}
                  className="p-3 bg-gray-50 rounded-lg border border-gray-200"
                >
                  <p className="font-medium text-gray-900">{user.name || user}</p>
                  <p className="text-sm text-gray-600">{user.email}</p>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={showDeleteDialog}
        onClose={() => setShowDeleteDialog(false)}
        onConfirm={handleDelete}
        title="Delete Role"
        message={`Are you sure you want to delete role "${role.name}"? This action cannot be undone.`}
        confirmText="Delete"
        cancelText="Cancel"
      />
    </div>
  );
}
