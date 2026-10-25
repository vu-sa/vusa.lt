import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import type { registerSW as RegisterSW } from 'virtual:pwa-register';

import { describeDevice } from '@/Utils/pushDevice';

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
let registerSW: typeof RegisterSW | undefined;

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

export interface PushSubscriptionDevice {
  id: number;
  endpoint: string;
  device_name: string | null;
  created_at: string | null;
  updated_at: string | null;
  isCurrentDevice?: boolean;
}

// Global state for PWA
const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const needRefresh = ref(false);
const offlineReady = ref(false);
const isInstalled = ref(false);
const pushPermission = ref<NotificationPermission>('default');
const isSubscribingToPush = ref(false);
const isUnsubscribingFromPush = ref(false);
const isRefreshingSubscriptionStatus = ref(false);
const currentBrowserSubscribed = ref<boolean | null>(null); // null = checking, true/false = known state
const currentBrowserEndpoint = ref<string | null>(null); // Store current browser's endpoint for matching
const isPWAMode = ref(false); // Reactive PWA mode state

// Service worker update function (set after registration)
let updateSW: ((reloadPage?: boolean) => Promise<void>) | undefined;
let swRegistration: ServiceWorkerRegistration | undefined;
const serviceWorkerReady = ref(false);

/**
 * Check if running in standalone/installed PWA mode
 */
function checkPWAMode(): boolean {
  if (typeof window === 'undefined') return false;

  return (
    window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: minimal-ui)').matches
    || (window.navigator as any).standalone === true // iOS Safari
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

function getCsrfToken(): string {
  return (usePage().props.csrf_token as string) || '';
}

function getVapidKey(): string | null {
  return (usePage().props.pwa as { vapidPublicKey?: string } | undefined)?.vapidPublicKey ?? null;
}

// Convert URL-safe base64 to Uint8Array for VAPID key
function urlBase64ToUint8Array(base64String: string): Uint8Array<ArrayBuffer> {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const buffer = new ArrayBuffer(rawData.length);
  const outputArray = new Uint8Array(buffer);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

async function subscribeRegistration(registration: ServiceWorkerRegistration, vapidKey: string): Promise<PushSubscriptionJSON> {
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidKey),
  });

  const subscriptionJson = subscription.toJSON() as PushSubscriptionJSON;

  // PushEncryptionKeyName tells the server which encryption format to use
  const response = await fetch(route('api.v1.admin.pushSubscriptions.store'), {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify({
      endpoint: subscriptionJson.endpoint,
      keys: subscriptionJson.keys,
      contentEncoding: (PushManager as unknown as { supportedContentEncodings?: string[] }).supportedContentEncodings?.[0] || 'aes128gcm',
      deviceName: describeDevice(navigator.userAgent),
    }),
  });

  if (!response.ok) {
    throw new Error(`Server returned ${response.status}`);
  }

  return subscriptionJson;
}

function destroySubscription(endpoint: string): Promise<Response> {
  return fetch(route('api.v1.admin.pushSubscriptions.destroy'), {
    method: 'DELETE',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify({ endpoint }),
  });
}

const RELOAD_FALLBACK_MS = 3000;

/**
 * workbox-window reloads on `controlling`, which a page the waiting worker will not control never
 * receives — so reload ourselves once it activates, with a timeout in case it never reports back.
 */
export async function activateWaitingWorker(reload = () => window.location.reload()): Promise<void> {
  const waiting = swRegistration?.waiting;

  if (!updateSW || !waiting) {
    reload();
    return;
  }

  waiting.addEventListener('statechange', () => {
    if (waiting.state === 'activated') {
      reload();
    }
  });
  window.setTimeout(reload, RELOAD_FALLBACK_MS);

  await updateSW();
}

/**
 * The worker used to be registered at /build/sw.js. Its push subscription moves to the
 * current worker before it is unregistered, so the device neither loses pushes nor gets them twice.
 */
export async function migrateLegacyRegistration(current: ServiceWorkerRegistration): Promise<void> {
  // pushManager.subscribe() needs an active worker; a first install migrates on the next load.
  if (!current.active) {
    return;
  }

  const registrations = await navigator.serviceWorker.getRegistrations();
  const legacy = registrations.find(registration => new URL(registration.scope).pathname === '/build/');

  if (!legacy) {
    return;
  }

  try {
    const subscription = await legacy.pushManager.getSubscription();
    const vapidKey = getVapidKey();

    if (subscription) {
      if (vapidKey && 'Notification' in window && Notification.permission === 'granted') {
        const subscriptionJson = await subscribeRegistration(current, vapidKey);
        currentBrowserSubscribed.value = true;
        currentBrowserEndpoint.value = subscriptionJson.endpoint;
      }

      await destroySubscription(subscription.endpoint);
      await subscription.unsubscribe();
    }

    await legacy.unregister();
  }
  catch (error) {
    console.warn('[PWA] Could not migrate the old service worker:', error);
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

  const updateApp = async () => {
    await activateWaitingWorker();
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
    // Fall back to checking if current browser's endpoint is in server list
    const page = usePage();
    const endpoints = (page.props.pwa as any)?.subscriptionEndpoints ?? [];
    if (currentBrowserEndpoint.value && endpoints.length > 0) {
      return endpoints.includes(currentBrowserEndpoint.value);
    }
    // Unknown: another device's subscription says nothing about this browser.
    return false;
  });

  // Check if ANY device has push subscription (for showing device management)
  const hasAnyPushSubscription = computed(() => {
    const page = usePage();
    return (page.props.pwa as any)?.hasPushSubscription ?? false;
  });

  // Check current browser's subscription status and store endpoint
  const checkCurrentBrowserSubscription = async (): Promise<void> => {
    // Try native service worker API first (works even without PWA module)
    if (pushSupported.value) {
      try {
        const registration = swRegistration || await navigator.serviceWorker.getRegistration();
        if (registration) {
          const subscription = await registration.pushManager.getSubscription();
          currentBrowserSubscribed.value = subscription !== null;
          currentBrowserEndpoint.value = subscription?.endpoint ?? null;
          return;
        }
      }
      catch (error) {
        console.warn('[PWA] Error checking subscription via SW:', error);
      }
    }

    // Fallback: check against server-side endpoints
    const page = usePage();
    const endpoints = (page.props.pwa as any)?.subscriptionEndpoints ?? [];
    if (currentBrowserEndpoint.value) {
      currentBrowserSubscribed.value = endpoints.includes(currentBrowserEndpoint.value);
    }
    else {
      currentBrowserSubscribed.value = false;
    }
  };

  // Refresh subscription status from server and browser
  const refreshSubscriptionStatus = async (): Promise<void> => {
    isRefreshingSubscriptionStatus.value = true;
    try {
      // Re-check browser subscription
      await checkCurrentBrowserSubscription();
      // Reload page props to get latest server state
      router.reload({ only: ['pwa'] });
    }
    finally {
      isRefreshingSubscriptionStatus.value = false;
    }
  };

  // Subscribe to push notifications
  const subscribeToPush = async (): Promise<boolean> => {
    // The registration may not be stored yet (early click, or a worker registered in an earlier visit).
    swRegistration ??= pushSupported.value ? await navigator.serviceWorker.getRegistration() : undefined;

    if (!pushSupported.value || !swRegistration) {
      serviceWorkerReady.value = false;
      toast.error($t('notifications.push_devices.worker_missing'));
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

      const subscriptionJson = await subscribeRegistration(swRegistration, vapidKey);

      // Update local state to reflect subscription
      currentBrowserSubscribed.value = true;
      currentBrowserEndpoint.value = subscriptionJson.endpoint;

      // Reload page props to update hasPushSubscription
      router.reload({ only: ['pwa'] });

      console.log('[PWA] Push subscription successful');
      return true;
    }
    catch (error) {
      console.error('[PWA] Push subscription failed:', error);
      toast.error($t('notifications.push_devices.subscribe_failed'));
      return false;
    }
    finally {
      isSubscribingToPush.value = false;
    }
  };

  // Unsubscribe from push notifications (current browser)
  const unsubscribeFromPush = async (): Promise<boolean> => {
    isUnsubscribingFromPush.value = true;

    try {
      // Try to get subscription from service worker
      const registration = swRegistration || await navigator.serviceWorker.getRegistration();

      if (registration) {
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
          // Unsubscribe locally first
          await subscription.unsubscribe();

          const response = await destroySubscription(subscription.endpoint);

          if (!response.ok) {
            console.warn('[PWA] Server returned error on unsubscribe:', response.status);
          }

          // Update local state
          currentBrowserSubscribed.value = false;
          currentBrowserEndpoint.value = null;

          // Reload page props
          router.reload({ only: ['pwa'] });

          console.log('[PWA] Push unsubscription successful');
          return true;
        }
      }

      // Fallback: If we have a stored endpoint but no SW, try server-only removal
      if (currentBrowserEndpoint.value) {
        const response = await destroySubscription(currentBrowserEndpoint.value);

        if (response.ok) {
          currentBrowserSubscribed.value = false;
          currentBrowserEndpoint.value = null;
          router.reload({ only: ['pwa'] });
          return true;
        }
      }

      // Nothing to remove in this browser: the "enabled" state was stale, so correct it.
      currentBrowserSubscribed.value = false;
      router.reload({ only: ['pwa'] });

      return true;
    }
    catch (error) {
      console.error('[PWA] Push unsubscription failed:', error);
      return false;
    }
    finally {
      isUnsubscribingFromPush.value = false;
    }
  };

  // Remove a subscription by ID (for device management)
  const removeSubscriptionById = async (id: number): Promise<boolean> => {
    try {
      const response = await fetch(route('api.v1.admin.pushSubscriptions.destroyById', { id }), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });

      if (!response.ok) {
        console.error('[PWA] Failed to remove subscription:', response.status);
        return false;
      }

      // Reload page props
      router.reload({ only: ['pwa'] });

      // Re-check current browser subscription in case we removed our own
      await checkCurrentBrowserSubscription();

      return true;
    }
    catch (error) {
      console.error('[PWA] Error removing subscription:', error);
      return false;
    }
  };

  // Fetch all push subscriptions for device management
  const fetchPushSubscriptions = async (): Promise<PushSubscriptionDevice[]> => {
    try {
      const response = await fetch(route('api.v1.admin.pushSubscriptions.index'), {
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });

      if (!response.ok) {
        console.error('[PWA] Failed to fetch subscriptions:', response.status);
        return [];
      }

      // ApiResponses shape: { success, data }.
      const payload = await response.json() as { data?: PushSubscriptionDevice[] };
      const subscriptions = payload.data ?? [];

      // Mark which subscription belongs to current browser
      if (currentBrowserEndpoint.value) {
        return subscriptions.map(sub => ({
          ...sub,
          isCurrentDevice: sub.endpoint === currentBrowserEndpoint.value,
        }));
      }

      return subscriptions;
    }
    catch (error) {
      console.error('[PWA] Error fetching subscriptions:', error);
      return [];
    }
  };

  /**
   * Set the app badge count (shows on PWA icon)
   * Uses the Badging API: https://developer.mozilla.org/en-US/docs/Web/API/Badging_API
   */
  const setAppBadge = async (count: number): Promise<boolean> => {
    if (!('setAppBadge' in navigator)) {
      return false;
    }

    try {
      if (count > 0) {
        await (navigator as any).setAppBadge(count);
      }
      else {
        await (navigator as any).clearAppBadge();
      }
      return true;
    }
    catch (error) {
      // Badging API may fail silently on some platforms
      console.warn('[PWA] Failed to set app badge:', error);
      return false;
    }
  };

  /**
   * Clear the app badge
   */
  const clearAppBadge = async (): Promise<boolean> => {
    if (!('clearAppBadge' in navigator)) {
      return false;
    }

    try {
      await (navigator as any).clearAppBadge();
      return true;
    }
    catch (error) {
      console.warn('[PWA] Failed to clear app badge:', error);
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
    hasAnyPushSubscription,
    isSubscribingToPush,
    isUnsubscribingFromPush,
    isRefreshingSubscriptionStatus,
    currentBrowserEndpoint,
    subscribeToPush,
    unsubscribeFromPush,
    removeSubscriptionById,
    fetchPushSubscriptions,
    serviceWorkerReady,
    checkCurrentBrowserSubscription,
    refreshSubscriptionStatus,
    // App badge
    setAppBadge,
    clearAppBadge,
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
    console.log('[PWA] Skipping VitePWA service worker registration (not available)');

    // Still set up push permission state
    if ('Notification' in window) {
      pushPermission.value = Notification.permission;
    }

    // Check if already installed via display-mode
    if (isPWAMode.value) {
      isInstalled.value = true;
    }

    // Try to check for existing service worker and push subscription
    // This handles cases where SW was registered previously but VitePWA module isn't available now
    if ('serviceWorker' in navigator) {
      try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration) {
          swRegistration = registration;
          serviceWorkerReady.value = true;
          const subscription = await registration.pushManager.getSubscription();
          currentBrowserSubscribed.value = subscription !== null;
          currentBrowserEndpoint.value = subscription?.endpoint ?? null;
          console.log('[PWA] Found existing SW with push subscription:', subscription !== null);
        }
      }
      catch (error) {
        console.warn('[PWA] Could not check existing service worker:', error);
      }
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
    onRegisteredSW(swUrl: string, registration: ServiceWorkerRegistration | undefined) {
      console.log('[PWA] Service worker registered:', swUrl);
      swRegistration = registration;
      serviceWorkerReady.value = registration !== undefined;

      // Update push permission state
      if ('Notification' in window) {
        pushPermission.value = Notification.permission;
      }

      if (!registration) {
        return;
      }

      registration.pushManager.getSubscription().then((subscription: PushSubscription | null) => {
        currentBrowserSubscribed.value = subscription !== null;
        currentBrowserEndpoint.value = subscription?.endpoint ?? null;
      }).catch(() => {
        currentBrowserSubscribed.value = false;
        currentBrowserEndpoint.value = null;
      }).finally(() => migrateLegacyRegistration(registration));

      // iOS suspends an installed app instead of keeping it open, so the hourly check alone rarely runs.
      // An offline check rejects; the next one retries.
      const checkForUpdate = () => registration.update().catch(() => undefined);
      setInterval(checkForUpdate, 60 * 60 * 1000);
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          checkForUpdate();
        }
      });
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
