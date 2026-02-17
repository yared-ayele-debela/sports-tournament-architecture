import { useEffect, useRef, useState, useCallback } from 'react';
import echo from '../api/echo';

/**
 * Custom hook for WebSocket connection to match events
 * @param {string|number} matchId - Match ID to listen to
 * @param {object} options - Configuration options
 * @returns {object} - WebSocket state and handlers
 */
export const useMatchWebSocket = (matchId, options = {}) => {
  const {
    onEventRecorded,
    onScoreUpdated,
    onMinuteUpdated,
    onStatusChanged,
    enabled = true,
  } = options;

  const [isConnected, setIsConnected] = useState(false);
  const [lastUpdate, setLastUpdate] = useState(null);
  const listenersRef = useRef([]);
  const handlersRef = useRef({ onEventRecorded, onScoreUpdated, onMinuteUpdated, onStatusChanged });

  // Update handlers ref when they change
  useEffect(() => {
    handlersRef.current = { onEventRecorded, onScoreUpdated, onMinuteUpdated, onStatusChanged };
  }, [onEventRecorded, onScoreUpdated, onMinuteUpdated, onStatusChanged]);

  useEffect(() => {
    if (!enabled || !matchId || !echo) {
      return;
    }

    const channelName = `match.${matchId}`;
    
    // Check if already subscribed to avoid duplicate subscriptions
    if (listenersRef.current.includes(channelName)) {
      return;
    }
    
    const channel = echo.channel(channelName);

    // Listen for match event recorded
    if (handlersRef.current.onEventRecorded) {
      channel.listen('.match.event.recorded', (data) => {
        handlersRef.current.onEventRecorded(data);
        setLastUpdate(new Date());
      });
      listenersRef.current.push('match.event.recorded');
    }

    // Listen for score updates
    if (handlersRef.current.onScoreUpdated) {
      channel.listen('.match.score.updated', (data) => {
        handlersRef.current.onScoreUpdated(data);
        setLastUpdate(new Date());
      });
      listenersRef.current.push('match.score.updated');
    }

    // Listen for minute updates
    if (handlersRef.current.onMinuteUpdated) {
      channel.listen('.match.minute.updated', (data) => {
        handlersRef.current.onMinuteUpdated(data);
        setLastUpdate(new Date());
      });
      listenersRef.current.push('match.minute.updated');
    }

    // Listen for status changes
    if (handlersRef.current.onStatusChanged) {
      channel.listen('.match.status.changed', (data) => {
        handlersRef.current.onStatusChanged(data);
        setLastUpdate(new Date());
      });
      listenersRef.current.push('match.status.changed');
    }
    
    // Mark channel as subscribed
    listenersRef.current.push(channelName);

    // Connection status
    echo.connector.pusher.connection.bind('connected', () => {
      setIsConnected(true);
    });

    echo.connector.pusher.connection.bind('disconnected', () => {
      setIsConnected(false);
    });

    echo.connector.pusher.connection.bind('error', (error) => {
      console.error('WebSocket connection error:', error);
      setIsConnected(false);
    });

    return () => {
      // Cleanup: leave channel and remove listeners
      if (echo) {
        echo.leave(channelName);
      }
      listenersRef.current = listenersRef.current.filter(item => item !== channelName && !item.startsWith('match.'));
    };
  }, [matchId, enabled]); // Removed callback dependencies to prevent re-subscriptions

  return {
    isConnected,
    lastUpdate,
  };
};

/**
 * Custom hook for listening to all live matches
 * @param {function} onMatchUpdate - Callback when any live match updates
 * @returns {object} - WebSocket state
 */
export const useLiveMatchesWebSocket = (onMatchUpdate) => {
  const [isConnected, setIsConnected] = useState(false);
  const listenersRef = useRef([]);

  useEffect(() => {
    if (!echo || !onMatchUpdate) {
      return;
    }

    // Listen to the general live matches channel
    const channel = echo.channel('match.live');

    // Listen for all match events on live channel
    channel.listen('.match.event.recorded', (data) => {
      onMatchUpdate({ type: 'event', data });
    });

    channel.listen('.match.score.updated', (data) => {
      onMatchUpdate({ type: 'score', data });
    });

    channel.listen('.match.minute.updated', (data) => {
      onMatchUpdate({ type: 'minute', data });
    });

    channel.listen('.match.status.changed', (data) => {
      onMatchUpdate({ type: 'status', data });
    });

    // Connection status
    echo.connector.pusher.connection.bind('connected', () => {
      setIsConnected(true);
    });

    echo.connector.pusher.connection.bind('disconnected', () => {
      setIsConnected(false);
    });

    return () => {
      if (echo) {
        echo.leave('match.live');
      }
      listenersRef.current = [];
    };
  }, [onMatchUpdate]);

  return { isConnected };
};
