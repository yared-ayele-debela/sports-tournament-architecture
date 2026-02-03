import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { usersService } from '../../api/users';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export default function UserDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);

  const { data: user, isLoading, error } = useQuery({
    queryKey: ['user', id],
    queryFn: () => usersService.get(id),
  });

  const deleteMutation = useMutation({
    mutationFn: () => usersService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['users']);
      navigate('/users');
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
          <p className="text-red-600">Error loading user: {error.message}</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="p-8">
        <div className="card">
          <p className="text-gray-600">User not found</p>
        </div>
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
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-bold text-gray-900">User Details</h1>
          <div className="flex space-x-3">
            <button
              onClick={() => navigate(`/users/${id}/edit`)}
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
              <p className="font-medium text-gray-900">{user.id}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Name</p>
              <p className="font-medium text-gray-900">{user.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Email</p>
              <p className="font-medium text-gray-900">{user.email}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Email Verified</p>
              <p className="font-medium text-gray-900">
                {user.email_verified_at ? (
                  <span className="text-green-600">Yes</span>
                ) : (
                  <span className="text-red-600">No</span>
                )}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Created At</p>
              <p className="font-medium text-gray-900">
                {new Date(user.created_at).toLocaleString()}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Updated At</p>
              <p className="font-medium text-gray-900">
                {new Date(user.updated_at).toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        {/* Roles */}
        <div className="card">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Roles</h2>
          {user.roles && user.roles.length > 0 ? (
            <div className="space-y-2">
              {user.roles.map((role, index) => (
                <div
                  key={index}
                  className="p-3 bg-primary-50 rounded-lg border border-primary-200"
                >
                  <p className="font-medium text-primary-900">{role.name || role}</p>
                  {role.description && (
                    <p className="text-sm text-primary-700 mt-1">{role.description}</p>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500">No roles assigned</p>
          )}
        </div>

        {/* Permissions */}
        {user.permissions && user.permissions.length > 0 && (
          <div className="card md:col-span-2">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Permissions</h2>
            <div className="flex flex-wrap gap-2">
              {user.permissions.map((permission, index) => (
                <span
                  key={index}
                  className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm"
                >
                  {permission.name || permission}
                </span>
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
        title="Delete User"
        message={`Are you sure you want to delete user "${user.name}"? This action cannot be undone.`}
        confirmText="Delete"
        cancelText="Cancel"
      />
    </div>
  );
}
