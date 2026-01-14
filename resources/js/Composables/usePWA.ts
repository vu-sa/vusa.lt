import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * PWA Composable
 * 
 * NOTE: PWA features (service worker, install prompt, push notifications) only work
 * in PRODUCTION BUILD mode, not in dev mode. This is because:
 * - Laravel Sail serves the app at https://www.vusa.test
 * - Vite serves assets at http://localhost:5173
 * - Service workers require same-origin, causing redirect errors in dev
 * 
 * To test PWA features:
 * 1. Run `npm run build`
 * 2. Access the site normally (Laravel will serve built assets)
 * 3. The install button, update prompts, and push notifications will work
 */

// Dynamic import for registerSW - only available in production builds
let registerSW: ((options?: any) => (reloadPage?: boolean) => Promise<void>) | undefined;

const pwaModulePromise = import('virtual:pwa-register')
  .then((pwaModule) => {
    registerSW = pwaModule.registerSW;
    return true;
  })
  .catch(() => {
    // PWA module not available in dev mode - this is expected
    if (import.meta.env.DEV) {
      console.log('[PWA] Service worker not available in dev mode. Run `npm run build` to test PWA features.');
    }
    return false;
  });

interface BeforeInstallPromptEvent extends Event {
  readonly platforms: string[];
  readonly userChoice: Promise<{
    outcome: 'accepted' | 'dismissed';
    platform: string;
  }>;
  prompt(): Promise<void>;
}

interface PushSubscriptionJSON {
  endpoint: string;
  keys: {
    p256dh: string;
    auth: string;
  };
}

// Global state for PWA
const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const needRefresh = ref(false);
const offlineReady = ref(false);
const isInstalled = ref(false);
const pushPermission = ref<NotificationPermission>('default');
const isSubscribingToPush = ref(false);
const currentBrowserSubscribed = ref<boolean | null>(null); // null = checking, true/false = known state
const isPWAMode = ref(false); // Reactive PWA mode state

// Service worker update function (set after registration)
let updateSW: ((reloadPage?: boolean) => Promise<void>) | undefined;
let swRegistration: ServiceWorkerRegistration | undefined;

/**
 * Check if running in standalone/installed PWA mode
 */
function checkPWAMode(): boolean {
  if (typeof window === 'undefined') return false;
  
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    window.matchMedia('(display-mode: minimal-ui)').matches ||
    (window.navigator as any).standalone === true // iOS Safari
  );
}

/**
 * Set/update PWA cookie for server-side detection (extended sessions)
 * Called once on init and whenever display-mode changes
 */
function updatePWACookie(): void {
  if (typeof document === 'undefined') return;
  
  const mode = checkPWAMode();
  isPWAMode.value = mode;
  
  const cookieValue = mode ? '1' : '0';
  const maxAge = 60 * 60 * 24 * 365; // 1 year
  
  // Only set if different from current value to avoid unnecessary cookie writes
  const currentValue = document.cookie.match(/(?:^|; )pwa_mode=([^;]*)/)?.[1];
  if (currentValue !== cookieValue) {
    document.cookie = `pwa_mode=${cookieValue}; path=/; max-age=${maxAge}; SameSite=Lax`;
  }
}

/**
 * Composable for PWA functionality including:
 * - Detecting if running as installed PWA
 * - Managing install prompt
 * - Handling service worker updates
 * - Offline status
 */
export function usePWA() {
  // Reactive PWA mode - updated by initPWA and display-mode change listener
  const isPWA = computed(() => isPWAMode.value);

  // Check if PWA can be installed (not already installed and prompt available)
  const canInstall = computed(() => {
    return deferredPrompt.value !== null && !isInstalled.value;
  });

  // Trigger the install prompt
  const promptInstall = async (): Promise<boolean> => {
    if (!deferredPrompt.value) return false;

    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === 'accepted') {
      isInstalled.value = true;
      deferredPrompt.value = null;
      return true;
    }
    
    return false;
  };

  // Refresh the app with new service worker
  const updateApp = async () => {
    if (updateSW) {
      await updateSW(true);
    }
  };

  // Dismiss the update prompt
  const dismissUpdate = () => {
    needRefresh.value = false;
  };

  // Check if push notifications are supported
  const pushSupported = computed(() => {
    return 'PushManager' in window && 'serviceWorker' in navigator;
  });

  // Check if user can subscribe to push (permission not denied)
  const canSubscribeToPush = computed(() => {
    return pushSupported.value && pushPermission.value !== 'denied';
  });

  // Check if THIS browser has an active push subscription
  // This checks the browser's actual subscription state, not just the server record
  const hasPushSubscription = computed(() => {
    // If we've checked the browser, use that state
    if (currentBrowserSubscribed.value !== null) {
      return currentBrowserSubscribed.value;
    }
    // Fall back to server state while checking
    const page = usePage();
    return (page.props.pwa as any)?.hasPushSubscription ?? false;
  });

  // Check current browser's subscription status
  const checkCurrentBrowserSubscription = async (): Promise<void> => {
    if (!pushSupported.value || !swRegistration) {
      currentBrowserSubscribed.value = false;
      return;
    }

    try {
      const subscription = await swRegistration.pushManager.getSubscription();
      currentBrowserSubscribed.value = subscription !== null;
    } catch (error) {
      console.error('[PWA] Error checking subscription:', error);
      currentBrowserSubscribed.value = false;
    }
  };

  // Get CSRF token from Inertia page props
  const getCsrfToken = (): string => {
    const page = usePage();
    return (page.props.csrf_token as string) || '';
  };

  // Get VAPID public key from server
  const getVapidKey = (): string | null => {
    const page = usePage();
    return (page.props.pwa as any)?.vapidPublicKey ?? null;
  };

  // Convert URL-safe base64 to Uint8Array for VAPID key
  const urlBase64ToUint8Array = (base64String: string): Uint8Array => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  };

  // Subscribe to push notifications
  const subscribeToPush = async (): Promise<boolean> => {
    if (!pushSupported.value || !swRegistration) {
      console.error('[PWA] Push not supported or SW not registered');
      return false;
    }

    const vapidKey = getVapidKey();
    if (!vapidKey) {
      console.error('[PWA] VAPID public key not available');
      return false;
    }

    isSubscribingToPush.value = true;

    try {
      // Request notification permission
      const permission = await Notification.requestPermission();
      pushPermission.value = permission;

      if (permission !== 'granted') {
        console.log('[PWA] Notification permission denied');
        return false;
      }

      // Subscribe to push
      const subscription = await swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
      });

      const subscriptionJson = subscription.toJSON() as PushSubscriptionJSON;

      // Send subscription to server with content encoding
      // PushEncryptionKeyName tells the server which encryption format to use
      const response = await fetch(route('push-subscription.store'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          endpoint: subscriptionJson.endpoint,
          keys: subscriptionJson.keys,
          contentEncoding: (PushManager as any).supportedContentEncodings?.[0] || 'aes128gcm',
        }),
      });

      if (!response.ok) {
        throw new Error(`Server returned ${response.status}`);
      }

      // Update local state to reflect subscription
      currentBrowserSubscribed.value = true;

      // Reload page props to update hasPushSubscription
      router.reload({ only: ['pwa'] });

      console.log('[PWA] Push subscription successful');
      return true;
    } catch (error) {
      console.error('[PWA] Push subscription failed:', error);
      return false;
    } finally {
      isSubscribingToPush.value = false;
    }
  };

  // Unsubscribe from push notifications
  const unsubscribeFromPush = async (): Promise<boolean> => {
    if (!swRegistration) return false;

    try {
      const subscription = await swRegistration.pushManager.getSubscription();
      
      if (subscription) {
        // Unsubscribe locally
        await subscription.unsubscribe();

        // Remove from server
        await fetch(route('push-subscription.destroy'), {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
          },
          body: JSON.stringify({
            endpoint: subscription.endpoint,
          }),
        });

        // Update local state
        currentBrowserSubscribed.value = false;

        // Reload page props
        router.reload({ only: ['pwa'] });

        console.log('[PWA] Push unsubscription successful');
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('[PWA] Push unsubscription failed:', error);
      return false;
    }
  };

  return {
    isPWA,
    canInstall,
    isInstalled,
    needRefresh,
    offlineReady,
    promptInstall,
    updateApp,
    dismissUpdate,
    // Push notification exports
    pushSupported,
    pushPermission,
    canSubscribeToPush,
    hasPushSubscription,
    isSubscribingToPush,
    subscribeToPush,
    unsubscribeFromPush,
  };
}

/**
 * Initialize PWA - call this once in app.ts
 * Sets up service worker registration and install prompt listeners
 */
export async function initPWA() {
  if (typeof window === 'undefined') return;

  // Initialize PWA mode and cookie (consolidated)
  updatePWACookie();
  
  // Listen for display-mode changes (e.g., user installs PWA mid-session)
  const standaloneQuery = window.matchMedia('(display-mode: standalone)');
  const minimalUIQuery = window.matchMedia('(display-mode: minimal-ui)');
  
  const handleDisplayModeChange = () => {
    updatePWACookie();
    if (isPWAMode.value) {
      isInstalled.value = true;
    }
  };
  
  // Modern browsers support addEventListener on MediaQueryList
  standaloneQuery.addEventListener?.('change', handleDisplayModeChange);
  minimalUIQuery.addEventListener?.('change', handleDisplayModeChange);

  // Set up install prompt listeners (works even without service worker)
  const handleBeforeInstallPrompt = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e as BeforeInstallPromptEvent;
  };

  const handleAppInstalled = () => {
    isInstalled.value = true;
    deferredPrompt.value = null;
  };

  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  window.addEventListener('appinstalled', handleAppInstalled);

  // Wait for PWA module to load before checking registerSW availability
  await pwaModulePromise;

  // Skip service worker registration if registerSW is not available
  if (!registerSW) {
    console.log('[PWA] Skipping service worker registration (not available)');
    
    // Still set up push permission state
    if ('Notification' in window) {
      pushPermission.value = Notification.permission;
    }
    
    // Check if already installed via display-mode
    if (isPWAMode.value) {
      isInstalled.value = true;
    }
    
    return;
  }

  // Register service worker with update handling
  updateSW = registerSW({
    immediate: true,
    onNeedRefresh() {
      needRefresh.value = true;
    },
    onOfflineReady() {
      offlineReady.value = true;
    },
    onRegisteredSW(swUrl, registration) {
      console.log('[PWA] Service worker registered:', swUrl);
      swRegistration = registration;
      
      // Update push permission state
      if ('Notification' in window) {
        pushPermission.value = Notification.permission;
      }
      
      // Check if this browser has an active push subscription
      if (registration) {
        registration.pushManager.getSubscription().then((subscription) => {
          currentBrowserSubscribed.value = subscription !== null;
          console.log('[PWA] Browser push subscription:', subscription !== null);
        }).catch(() => {
          currentBrowserSubscribed.value = false;
        });
      }
      
      // Check for updates periodically (every hour)
      if (registration) {
        setInterval(() => {
          registration.update();
        }, 60 * 60 * 1000);
      }
    },
    onRegisterError(error: Error) {
      console.error('[PWA] Service worker registration error:', error);
    },
  });

  // Check if already installed via display-mode (already done via updatePWACookie above)
  if (isPWAMode.value) {
    isInstalled.value = true;
  }
}
