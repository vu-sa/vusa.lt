/**
 * Laravel Echo with Reverb WebSocket Connection
 *
 * Echo enables real-time notifications and events via Laravel Reverb.
 * This module exports a function to initialize Echo on-demand for authenticated users.
 *
 * @see https://laravel.com/docs/broadcasting
 * @see https://laravel.com/docs/reverb
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Enable Pusher logging in development
if (import.meta.env.DEV) {
  Pusher.logToConsole = true;
}

// Pusher-js is used as the WebSocket client (Reverb is Pusher-compatible)
// We need to assign it to window for Echo to find it
(window as any).Pusher = Pusher;

let echoInstance: Echo<'reverb'> | null = null;

/**
 * Initialize and return the Echo instance.
 * Creates a singleton connection to the Reverb WebSocket server.
 */
export function initEcho(): Echo<'reverb'> {
  if (echoInstance) {
    return echoInstance;
  }

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
  });

  return echoInstance;
}

/**
 * Get the current Echo instance without initializing.
 * Returns null if Echo hasn't been initialized yet.
 */
export function getEcho(): Echo<'reverb'> | null {
  return echoInstance;
}

/**
 * Disconnect and cleanup the Echo instance.
 * Call this when the user logs out or the component unmounts.
 */
export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}
