import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { venuesService } from '../../api/tournaments';
import { usePermissions } from '../../hooks/usePermissions';
import { ArrowLeft, Edit, MapPin, Users, Building2, Calendar } from 'lucide-react';

export default function VenueDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasPermission, isAdmin } = usePermissions();
  
  const canManageVenues = hasPermission('manage_venues') || isAdmin();
  const canEditVenues = canManageVenues;

  const { data: venue, isLoading, error } = useQuery({
    queryKey: ['venue', id],
    queryFn: () => venuesService.get(id),
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Loading venue...</div>
      </div>
    );
  }

  if (error || !venue) {
    return (
      <div className="space-y-6">
        <button
          onClick={() => navigate('/venues')}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ArrowLeft className="w-5 h-5 text-gray-600" />
        </button>
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-800">
            {error?.response?.data?.message || 'Venue not found'}
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button
            onClick={() => navigate('/venues')}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ArrowLeft className="w-5 h-5 text-gray-600" />
          </button>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{venue.name}</h1>
            <p className="text-gray-600 mt-1">Venue Details</p>
          </div>
        </div>
        {canEditVenues && (
          <button
            onClick={() => navigate(`/venues/${id}/edit`)}
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2"
          >
            <Edit className="w-4 h-4" />
            <span>Edit Venue</span>
          </button>
        )}
      </div>

      {/* Venue Information Card */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="p-6">
          <div className="space-y-6">
            {/* Name */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center space-x-2">
                  <Building2 className="w-4 h-4" />
                  <span>Venue Name</span>
                </div>
              </label>
              <p className="text-gray-900 text-lg font-medium">{venue.name}</p>
            </div>

            {/* Location */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center space-x-2">
                  <MapPin className="w-4 h-4" />
                  <span>Location</span>
                </div>
              </label>
              <p className="text-gray-900">
                {venue.location || <span className="text-gray-400">Not specified</span>}
              </p>
            </div>

            {/* Capacity */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center space-x-2">
                  <Users className="w-4 h-4" />
                  <span>Capacity</span>
                </div>
              </label>
              <p className="text-gray-900">
                {venue.capacity ? (
                  <span>{venue.capacity.toLocaleString()} spectators</span>
                ) : (
                  <span className="text-gray-400">Not specified</span>
                )}
              </p>
            </div>

            {/* Metadata */}
            <div className="pt-6 border-t border-gray-200 space-y-3">
              {venue.created_at && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Created At
                  </label>
                  <p className="text-gray-900">
                    {new Date(venue.created_at).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </p>
                </div>
              )}
              {venue.updated_at && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Last Updated
                  </label>
                  <p className="text-gray-900">
                    {new Date(venue.updated_at).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
