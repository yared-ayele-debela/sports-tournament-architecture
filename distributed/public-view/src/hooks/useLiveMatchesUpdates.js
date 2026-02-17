import { useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import echo from '../api/echo';

/**
 * Custom hook to update live matches in real-time via WebSocket
 * Updates React Query cache when match minutes, scores, or status change
 * 
 * @param {Array} matchIds - Array of match IDs to listen to
 * @param {object} options - Configuration options
 * @param {string|number} options.tournamentId - Tournament ID for query key
 * @returns {object} - Connection state
 */
export const useLiveMatchesUpdates = (matchIds = [], options = {}) => {
  const queryClient = useQueryClient();
  const { enabled = true, tournamentId } = options;
  const listenersRef = useRef(new Set());

  useEffect(() => {
    if (!enabled || !matchIds || matchIds.length === 0 || !echo) {
      return;
    }

    // Subscribe to each match's channel
    matchIds.forEach((matchId) => {
      if (!matchId || listenersRef.current.has(matchId)) {
        return;
      }

      const channelName = `match.${matchId}`;
      const channel = echo.channel(channelName);

      // Listen for minute updates
      channel.listen('.match.minute.updated', (data) => {
        const { match_id, current_minute, status } = data;
        
        // Update match in tournament matches query
        const queryKey = tournamentId ? ['tournamentMatches', tournamentId] : ['tournamentMatches'];
        queryClient.setQueryData(queryKey, (oldData) => {
          if (!oldData) return oldData;
          
          const matches = oldData?.data?.matches || oldData?.data?.data?.matches || oldData?.data || [];
          const updatedMatches = matches.map((match) => {
            if (match.id === match_id || match.match_id === match_id) {
              return {
                ...match,
                current_minute: current_minute,
                status: status || match.status,
              };
            }
            return match;
          });

          // Return in same format as original
          if (oldData?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, matches: updatedMatches } };
          }
          if (oldData?.data?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, data: { ...oldData.data.data, matches: updatedMatches } } };
          }
          return { ...oldData, data: updatedMatches };
        });

        // Also update individual match queries
        queryClient.setQueryData(['match', match_id], (oldData) => {
          if (!oldData) return oldData;
          const matchData = oldData?.data || oldData;
          return {
            ...oldData,
            data: {
              ...matchData,
              current_minute: current_minute,
              status: status || matchData.status,
            },
          };
        });
      });

      // Listen for score updates
      channel.listen('.match.score.updated', (data) => {
        const { match_id, home_score, away_score, current_minute, status } = data;
        
        // Update match in tournament matches query
        const queryKey = tournamentId ? ['tournamentMatches', tournamentId] : ['tournamentMatches'];
        queryClient.setQueryData(queryKey, (oldData) => {
          if (!oldData) return oldData;
          
          const matches = oldData?.data?.matches || oldData?.data?.data?.matches || oldData?.data || [];
          const updatedMatches = matches.map((match) => {
            if (match.id === match_id || match.match_id === match_id) {
              return {
                ...match,
                home_score: home_score !== undefined ? home_score : match.home_score,
                away_score: away_score !== undefined ? away_score : match.away_score,
                current_minute: current_minute !== undefined ? current_minute : match.current_minute,
                status: status || match.status,
              };
            }
            return match;
          });

          if (oldData?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, matches: updatedMatches } };
          }
          if (oldData?.data?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, data: { ...oldData.data.data, matches: updatedMatches } } };
          }
          return { ...oldData, data: updatedMatches };
        });

        // Also update individual match queries
        queryClient.setQueryData(['match', match_id], (oldData) => {
          if (!oldData) return oldData;
          const matchData = oldData?.data || oldData;
          return {
            ...oldData,
            data: {
              ...matchData,
              home_score: home_score !== undefined ? home_score : matchData.home_score,
              away_score: away_score !== undefined ? away_score : matchData.away_score,
              current_minute: current_minute !== undefined ? current_minute : matchData.current_minute,
              status: status || matchData.status,
            },
          };
        });
      });

      // Listen for status changes
      channel.listen('.match.status.changed', (data) => {
        const { match_id, status } = data;
        
        // Update match in tournament matches query
        const queryKey = tournamentId ? ['tournamentMatches', tournamentId] : ['tournamentMatches'];
        queryClient.setQueryData(queryKey, (oldData) => {
          if (!oldData) return oldData;
          
          const matches = oldData?.data?.matches || oldData?.data?.data?.matches || oldData?.data || [];
          const updatedMatches = matches.map((match) => {
            if (match.id === match_id || match.match_id === match_id) {
              return {
                ...match,
                status: status,
              };
            }
            return match;
          });

          if (oldData?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, matches: updatedMatches } };
          }
          if (oldData?.data?.data?.matches) {
            return { ...oldData, data: { ...oldData.data, data: { ...oldData.data.data, matches: updatedMatches } } };
          }
          return { ...oldData, data: updatedMatches };
        });

        // Also update individual match queries
        queryClient.setQueryData(['match', match_id], (oldData) => {
          if (!oldData) return oldData;
          const matchData = oldData?.data || oldData;
          return {
            ...oldData,
            data: {
              ...matchData,
              status: status,
            },
          };
        });
      });

      listenersRef.current.add(matchId);
    });

    // Cleanup: unsubscribe from all channels
    return () => {
      matchIds.forEach((matchId) => {
        if (matchId && echo) {
          const channelName = `match.${matchId}`;
          echo.leave(channelName);
          listenersRef.current.delete(matchId);
        }
      });
    };
  }, [matchIds, enabled, tournamentId, queryClient]);

  return {
    isConnected: listenersRef.current.size > 0,
  };
};
