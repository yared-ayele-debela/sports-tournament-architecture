import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Laravel Echo for Pusher
// Using Pusher service for WebSocket broadcasting

let echo = null;

try {
  // Initialize Pusher globally (required by Laravel Echo)
  window.Pusher = Pusher;
  
  // Get Pusher configuration from environment variables
  const appKey = import.meta.env.VITE_PUSHER_APP_KEY;
  const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu';
  const encrypted = import.meta.env.VITE_PUSHER_ENCRYPTED !== 'false';
  
  // Validate required configuration
  if (!appKey) {
    console.error('PUSHER_APP_KEY is not set. WebSocket will not work.');
    throw new Error('PUSHER_APP_KEY is required');
  }
  
  // Initialize Laravel Echo with Pusher configuration
  echo = new Echo({
    broadcaster: 'pusher',
    key: appKey,
    cluster: cluster,
    forceTLS: true,
    encrypted: encrypted,
    disableStats: false,
    enabledTransports: ['ws', 'wss'],
  });

  // Listen for connection events
  if (echo.connector && echo.connector.pusher) {
    echo.connector.pusher.connection.bind('error', (error) => {
      console.error('Pusher WebSocket error:', error);
    });
  }
} catch (error) {
  console.error('Failed to initialize Laravel Echo:', error);
  // Return a mock echo object to prevent crashes
  echo = {
    channel: () => ({
      listen: () => {
        console.warn('Echo not initialized - using mock channel');
      },
      stopListening: () => {},
    }),
    leave: () => {},
    disconnect: () => {},
    connector: null,
  };
}

export default echo;
