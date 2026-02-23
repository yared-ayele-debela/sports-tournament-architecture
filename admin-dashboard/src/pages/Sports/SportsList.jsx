import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { sportsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function SportsList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [deleteSportId, setDeleteSportId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();
  
  // Check permissions
  const canManageSports = hasPermission('manage_sports') || isAdmin();
  const canCreateSports = canManageSports;
  const canEditSports = canManageSports;
  const canDeleteSports = canManageSports;

  // Block access if user doesn't have permission
  if (!canManageSports) {
    return <Unauthorized message="You do not have permission to manage sports." />;
  }

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
    queryKey: ['sports', page, debouncedSearchTerm],
    queryFn: async () => {
      const result = await sportsService.list(queryParams);
      return result;
    },
    retry: false,
    placeholderData: (previousData) => previousData,
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => sportsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['sports']);
      setDeleteSportId(null);
      toast.success('Sport deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete sport');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteSportId) {
      deleteMutation.mutate(deleteSportId);
    }
  };

  // Extract sports and pagination
  let sports = [];
  let pagination = {};

  if (Array.isArray(data)) {
    sports = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    sports = data.data || [];
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? sports.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? sports.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? sports.length,
        from: data.from ?? 1,
        to: data.to ?? sports.length,
      };
    }
  }

  // Remove the automatic page sync to prevent pagination loops
  // The page state should be controlled by user interactions only

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Sports</h1>
        {canCreateSports && (
          <button
            onClick={() => navigate('/sports/new')}
            className="btn btn-primary flex items-center"
          >
            <Plus className="w-5 h-5 mr-2" />
            Create Sport
          </button>
        )}
      </div>

      {/* Search */}
      <div className="card mb-6">
        <form onSubmit={handleSearch} className="flex gap-4">
          <div className="flex-1">
            <label htmlFor="search" className="label">Search</label>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                ref={searchInputRef}
                id="search"
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search sports..."
                className="input pl-10"
              />
            </div>
          </div>
        </form>
      </div>

      {/* Table */}
      <div className="card relative">
        {isFetching && data && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
              <span className="text-sm text-gray-600">Loading...</span>
            </div>
          </div>
        )}

        {error ? (
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {error?.response?.data?.message || 'Failed to load sports'}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    {/* <th>Team Based</th> */}
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {sports.length === 0 ? (
                    <tr>
                      <td colSpan="5" className="text-center py-8 text-gray-500">
                        No sports found
                      </td>
                    </tr>
                  ) : (
                    sports.map((sport) => (
                      <tr key={sport.id}>
                        <td>{sport.id}</td>
                        <td className="font-medium">{sport.name}</td>
                        <td className="max-w-md truncate">{sport.description || 'N/A'}</td>
                        <td>
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => navigate(`/sports/${sport.id}`)}
                              className="p-2 text-primary-600 hover:bg-primary-50 rounded"
                              title="View"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            {canEditSports && (
                              <button
                                onClick={() => navigate(`/sports/${sport.id}/edit`)}
                                className="p-2 text-blue-600 hover:bg-blue-50 rounded"
                                title="Edit"
                              >
                                <Edit className="w-4 h-4" />
                              </button>
                            )}
                            {canDeleteSports && (
                              <button
                                onClick={() => setDeleteSportId(sport.id)}
                                className="p-2 text-red-600 hover:bg-red-50 rounded"
                                title="Delete"
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
            {pagination.last_page > 1 && (
              <div className="mt-6 flex items-center justify-between">
                <div className="text-sm text-gray-600">
                  Showing {pagination.from} to {pagination.to} of {pagination.total} results
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => {
                      const newPage = Math.max(1, page - 1);
                      console.log('Previous button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
                    disabled={page === 1}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <div className="flex items-center space-x-1">
                    {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                      let pageNum;
                      if (pagination.last_page <= 5) {
                        pageNum = i + 1;
                      } else if (page <= 3) {
                        pageNum = i + 1;
                      } else if (page >= pagination.last_page - 2) {
                        pageNum = pagination.last_page - 4 + i;
                      } else {
                        pageNum = page - 2 + i;
                      }
                      return (
                        <button
                          key={pageNum}
                          onClick={() => {
                            console.log('Page button clicked, going to page:', pageNum);
                            setPage(pageNum);
                          }}
                          className={`px-3 py-1 rounded ${
                            page === pageNum
                              ? 'bg-primary-600 text-white'
                              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                          }`}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                  </div>
                  <button
                    onClick={() => {
                      const newPage = Math.min(pagination.last_page, page + 1);
                      console.log('Next button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
                    disabled={page >= pagination.last_page}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      <ConfirmDialog
        isOpen={!!deleteSportId}
        title="Delete Sport"
        message="Are you sure you want to delete this sport? This action cannot be undone."
        onConfirm={handleDelete}
        onCancel={() => setDeleteSportId(null)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
