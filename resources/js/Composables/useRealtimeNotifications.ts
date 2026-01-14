/**
 * Real-time Notifications Composable
 * 
 * Provides real-time notification updates via Laravel Reverb WebSockets.
 * Listens to the user's private broadcast channel for new notifications
 * and updates the UI without page refresh.
 * 
 * DEDUPLICATION STRATEGY:
 * - Broadcast channel: For in-app real-time updates (toast, badge animation)
 * - WebPush channel: For offline/background notifications (OS-level push)
 * 
 * When both are active, the frontend tracks received notification IDs
 * to prevent showing the same notification twice.
 * 
 * @see https://laravel.com/docs/broadcasting
 * @see https://laravel.com/docs/notifications#broadcast-notifications
 */

import { ref, computed, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { trans as $t } from 'laravel-vue-i18n';
import type Echo from 'laravel-echo';

// Types for broadcast notification events
interface BroadcastNotificationData {
  id: string;
  type: string;
  text?: string;
  object?: {
    name?: string;
    url?: string;
  };
  subject?: {
    name?: string;
  };
}

interface NotificationEvent {
  id: string;
  type: string;
  data: BroadcastNotificationData;
}

// Global state - shared across all component instances
const isConnected = ref(false);
const isConnecting = ref(false);
const connectionError = ref<string | null>(null);
const recentNotificationIds = new Set<string>();

// Animation trigger for the notification indicator
const hasNewNotification = ref(false);
let animationTimeout: ReturnType<typeof setTimeout> | null = null;

// Echo instance reference
let echo: Echo<'reverb'> | null = null;
let userChannel: any = null;

/**
 * Get the notification title for toast display
 */
function getNotificationTitle(type: string): string {
  const typeName = type.split('\\').pop() || 'Notification';
  
  switch (typeName) {
    case 'ModelCommented':
      return $t('New Comment');
    case 'MemberRegistered':
      return $t('New Member Registration');
    case 'UserAttachedToModel':
      return $t('Assignment Notification');
    case 'TaskCreatedNotification':
      return $t('New Task');
    case 'TaskReminderNotification':
      return $t('Task Reminder');
    default:
      return $t('New Notification');
  }
}

/**
 * Initialize real-time notifications for the current user
 */
export function useRealtimeNotifications() {
  const page = usePage();
  const authUser = computed(() => page.props.auth?.user);
  const userId = computed(() => authUser.value?.id);

  /**
   * Connect to the user's private notification channel
   */
  async function connect() {
    // Already connected or connecting
    if (isConnected.value || isConnecting.value) {
      return;
    }

    // No user ID - can't subscribe to private channel
    if (!userId.value) {
      return;
    }

    isConnecting.value = true;
    connectionError.value = null;

    try {
      // Dynamic import to avoid loading Echo for public pages
      const { initEcho } = await import('@/echo');
      echo = initEcho();

      // Subscribe to the user's private notification channel
      // Laravel broadcasts notifications to: App.Models.User.{id}
      const channelName = `App.Models.User.${userId.value}`;
      
      userChannel = echo.private(channelName)
        .notification((notification: NotificationEvent) => {
          handleNotification(notification);
        })
        .error((error: any) => {
          console.error('[Reverb] Channel subscription error:', error);
          connectionError.value = 'Failed to subscribe to notifications';
        });

      // Track connection state
      // Note: Echo doesn't have a direct "connected" callback for channels,
      // but if we get here without error, subscription was initiated
      isConnected.value = true;
      
    } catch (error) {
      console.error('[Reverb] Failed to initialize:', error);
      connectionError.value = 'Failed to connect to real-time server';
    } finally {
      isConnecting.value = false;
    }
  }

  /**
   * Handle incoming broadcast notification
   */
  function handleNotification(notification: NotificationEvent) {
    const notificationId = notification.id;

    // Deduplicate - skip if we've already processed this notification
    // (could come from WebPush + Broadcast simultaneously)
    if (recentNotificationIds.has(notificationId)) {
      return;
    }

    // Track this notification ID for deduplication (keep last 100)
    recentNotificationIds.add(notificationId);
    if (recentNotificationIds.size > 100) {
      const firstId = recentNotificationIds.values().next().value;
      if (firstId) {
        recentNotificationIds.delete(firstId);
      }
    }

    // Trigger animation on notification indicator
    triggerNewNotificationAnimation();

    // Show toast notification
    showNotificationToast(notification);

    // Soft-refresh Inertia shared props to update notification count
    // This updates the bell icon badge without full page reload
    router.reload({ only: ['auth'] });
  }

  /**
   * Trigger animation on the notification indicator
   */
  function triggerNewNotificationAnimation() {
    hasNewNotification.value = true;
    
    // Clear any existing timeout
    if (animationTimeout) {
      clearTimeout(animationTimeout);
    }
    
    // Reset animation state after animation completes
    animationTimeout = setTimeout(() => {
      hasNewNotification.value = false;
    }, 600); // Match CSS animation duration
  }

  /**
   * Show a toast notification for the incoming notification
   */
  function showNotificationToast(notification: NotificationEvent) {
    const data = notification.data || notification;
    const title = getNotificationTitle(notification.type);
    const message = data.text || data.object?.name || $t('You have a new notification');
    const url = data.object?.url;

    toast(title, {
      description: message,
      action: url ? {
        label: $t('View'),
        onClick: () => {
          router.visit(url);
        },
      } : undefined,
      duration: 5000,
    });
  }

  /**
   * Disconnect from the notification channel
   */
  function disconnect() {
    if (userChannel) {
      userChannel.unsubscribe?.();
      userChannel = null;
    }
    
    isConnected.value = false;
    connectionError.value = null;
  }

  // Auto-connect when user is available
  watch(userId, (newId, oldId) => {
    if (newId && !oldId) {
      // User just logged in
      connect();
    } else if (!newId && oldId) {
      // User just logged out
      disconnect();
    } else if (newId !== oldId && newId) {
      // User changed (e.g., impersonation)
      disconnect();
      connect();
    }
  }, { immediate: true });

  // Cleanup on unmount
  onUnmounted(() => {
    // Don't disconnect on component unmount - keep connection alive
    // Only disconnect on explicit logout or page unload
  });

  return {
    // State
    isConnected: computed(() => isConnected.value),
    isConnecting: computed(() => isConnecting.value),
    connectionError: computed(() => connectionError.value),
    hasNewNotification: computed(() => hasNewNotification.value),
    
    // Methods
    connect,
    disconnect,
  };
}

/**
 * Mark a notification ID as seen (for deduplication with WebPush)
 * Call this when a push notification is received in the service worker
 * and you want to prevent the same notification from showing via broadcast
 */
export function markNotificationAsSeen(notificationId: string): void {
  recentNotificationIds.add(notificationId);
  if (recentNotificationIds.size > 100) {
    const firstId = recentNotificationIds.values().next().value;
    if (firstId) {
      recentNotificationIds.delete(firstId);
    }
  }
}

/**
 * Disconnect Echo instance completely
 * Call this on logout
 */
export async function disconnectRealtimeNotifications(): Promise<void> {
  if (userChannel) {
    userChannel.unsubscribe?.();
    userChannel = null;
  }
  
  if (echo) {
    const { disconnectEcho } = await import('@/echo');
    disconnectEcho();
    echo = null;
  }
  
  isConnected.value = false;
  isConnecting.value = false;
  connectionError.value = null;
  recentNotificationIds.clear();
}
