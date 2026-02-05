import { tournamentApi } from './axios';
import { resultsApi } from './axios';
import { matchApi } from './axios';
import { teamApi } from './axios';

export const tournamentService = {
  // Get featured tournaments
  getFeatured: async () => {
    const response = await tournamentApi.get('/tournaments/featured');
    // console.log(response.data);
    return response.data;
  },

  // Get upcoming tournaments
  getUpcoming: async () => {
    const response = await tournamentApi.get('/tournaments/upcoming');
    return response.data;
  },

  // Get all tournaments with filters
  getAll: async (params = {}) => {
    const response = await tournamentApi.get('/tournaments', { params });
    // Handle both response structures: { data: { data: [...], pagination: {...} } } or { data: [...] }
    return response.data;
  },

  // Get tournament by ID
  getById: async (id) => {
    const response = await tournamentApi.get(`/tournaments/${id}`);
    return response.data;
  },

  // Get tournament standings (from Results Service)
  getStandings: async (tournamentId) => {
    const response = await resultsApi.get(`/tournaments/${tournamentId}/standings`);
    return response.data;
  },

  // Get tournament matches (from Match Service)
  getMatches: async (tournamentId, params = {}) => {
    const response = await matchApi.get(`/tournaments/${tournamentId}/matches`, { params });
    return response.data;
  },

  // Get tournament teams (from Team Service)
  getTeams: async (tournamentId, params = {}) => {
    const response = await teamApi.get(`/tournaments/${tournamentId}/teams`, { params });
    return response.data;
  },

  // Get tournament statistics (from Results Service)
  getStatistics: async (tournamentId) => {
    const response = await resultsApi.get(`/tournaments/${tournamentId}/statistics`);
    return response.data;
  },

  // Get all sports
  getSports: async () => {
    const response = await tournamentApi.get('/sports');
    return response.data;
  },
};
