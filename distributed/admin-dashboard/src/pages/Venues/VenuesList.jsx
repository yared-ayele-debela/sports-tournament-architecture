import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { venuesService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import { Search, Plus, Edit, Trash2, Eye, MapPin, Users } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function VenuesList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [deleteVenueId, setDeleteVenueId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();
  
  // Check permissions
  const canManageVenues = hasPermission('manage_venues') || isAdmin();
  const canCreateVenues = canManageVenues;
  const canEditVenues = canManageVenues;
  const canDeleteVenues = canManageVenues;

  // Debounce search term
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Build query params
  const queryParams = {
    per_page: 15,
    page,
    ...(debouncedSearchTerm && { search: debouncedSearchTerm }),
  };

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['venues', page, debouncedSearchTerm],
    queryFn: async () => {
      const result = await venuesService.list(queryParams);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => venuesService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['venues']);
      setDeleteVenueId(null);
      toast.success('Venue deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete venue');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteVenueId) {
      deleteMutation.mutate(deleteVenueId);
    }
  };

  // Extract venues and pagination
  let venues = [];
  let pagination = {};

  if (Array.isArray(data)) {
    venues = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    venues = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? venues.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? venues.length,
        has_more: data.pagination.has_more ?? false,
        has_previous: data.pagination.has_previous ?? false,
      };
    } else {
      pagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: venues.length,
        from: 1,
        to: venues.length,
      };
    }
  }

  // Sync page state with pagination
  useEffect(() => {
    if (pagination.current_page && pagination.current_page !== page) {
      setPage(pagination.current_page);
    }
  }, [pagination.current_page]);

  // Restore focus after re-render
  useEffect(() => {
    if (searchInputRef.current && document.activeElement === searchInputRef.current) {
      const cursorPosition = searchInputRef.current.selectionStart;
      setTimeout(() => {
        if (searchInputRef.current) {
          searchInputRef.current.focus();
          searchInputRef.current.setSelectionRange(cursorPosition, cursorPosition);
        }
      }, 0);
    }
  }, [data]);

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Loading venues...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Venues</h1>
          <p className="text-gray-600 mt-1">Manage tournament venues and locations</p>
        </div>
        {canCreateVenues && (
          <button
            onClick={() => navigate('/venues/new')}
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2"
          >
            <Plus className="w-5 h-5" />
            <span>Add Venue</span>
          </button>
        )}
      </div>

      {/* Search Bar */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <form onSubmit={handleSearch} className="flex items-center space-x-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              ref={searchInputRef}
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search venues by name or location..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            Search
          </button>
        </form>
      </div>

      {/* Error State */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-800">
            {error?.response?.data?.message || 'Failed to load venues. Please try again.'}
          </p>
        </div>
      )}

      {/* Venues Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isFetching && data && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
            <div className="text-gray-500">Refreshing...</div>
          </div>
        )}
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Location
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Capacity
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {venues.length === 0 ? (
                <tr>
                  <td colSpan="4" className="px-6 py-12 text-center text-gray-500">
                    {debouncedSearchTerm ? 'No venues found matching your search.' : 'No venues found.'}
                  </td>
                </tr>
              ) : (
                venues.map((venue) => (
                  <tr key={venue.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{venue.name}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center text-sm text-gray-600">
                        {venue.location ? (
                          <>
                            <MapPin className="w-4 h-4 mr-2 text-gray-400" />
                            <span>{venue.location}</span>
                          </>
                        ) : (
                          <span className="text-gray-400">Not specified</span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center text-sm text-gray-600">
                        {venue.capacity ? (
                          <>
                            <Users className="w-4 h-4 mr-2 text-gray-400" />
                            <span>{venue.capacity.toLocaleString()}</span>
                          </>
                        ) : (
                          <span className="text-gray-400">Not specified</span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end space-x-2">
                        <button
                          onClick={() => navigate(`/venues/${venue.id}`)}
                          className="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                          title="View details"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                        {canEditVenues && (
                          <button
                            onClick={() => navigate(`/venues/${venue.id}/edit`)}
                            className="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Edit venue"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                        )}
                        {canDeleteVenues && (
                          <button
                            onClick={() => setDeleteVenueId(venue.id)}
                            className="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title="Delete venue"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <div className="text-sm text-gray-700">
            {pagination.total > 0 ? (
              <>
                Showing {pagination.from} to {pagination.to} of {pagination.total} results
              </>
            ) : (
              'No results'
            )}
          </div>
          {pagination.last_page > 1 && (
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={!pagination.has_previous || isFetching}
                className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              <span className="text-sm text-gray-700">
                Page {pagination.current_page} of {pagination.last_page}
              </span>
              <button
                onClick={() => setPage((p) => Math.min(pagination.last_page, p + 1))}
                disabled={!pagination.has_more || isFetching}
                className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={!!deleteVenueId}
        onClose={() => setDeleteVenueId(null)}
        onConfirm={handleDelete}
        title="Delete Venue"
        message="Are you sure you want to delete this venue? This action cannot be undone."
        confirmText="Delete"
        cancelText="Cancel"
        isLoading={deleteMutation.isLoading}
        variant="danger"
      />
    </div>
  );
}
