<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        class="relative size-8 shrink-0 border border-border text-muted-foreground hover:border-brand hover:text-foreground pointer-coarse:size-11"
        data-tour="notifications-indicator"
      >
        <BellIcon class="h-4 w-4" :class="{ 'animate-bell-swing': hasNewNotification }" />
        <Transition name="count" mode="out-in">
          <span :key="`count-${unreadNotificationsCount}`" class="sr-only" aria-live="polite">{{ unreadNotificationsCount }}</span>
        </Transition>
        <span
          v-if="unreadNotificationsCount > 0"
          class="absolute -right-1 -top-1 flex min-h-4 min-w-4 items-center justify-center bg-brand-fill px-0.5 text-[10px] font-bold text-brand-foreground"
          aria-live="polite"
        >
          {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
        </span>
        <span class="sr-only">{{ $t('Notifications') }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-96 max-w-[calc(100vw-2rem)] border-border p-0 shadow-none" align="end">
      <!-- Header -->
      <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
        <h4 class="font-semibold text-foreground">
          {{ $t('Notifications') }}
        </h4>
        <Button
          v-if="unreadNotificationsCount > 0"
          variant="ghost"
          size="sm"
          class="gap-1.5 text-xs text-muted-foreground hover:text-foreground pointer-coarse:h-11"
          @click="markAllAsRead"
        >
          <CheckCheckIcon class="h-3.5 w-3.5" />
          {{ $t('Mark all as read') }}
        </Button>
      </div>

      <!-- Notifications List -->
      <ScrollArea class="h-[340px]">
        <div class="divide-y divide-border">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="group relative"
            :class="{ 'bg-secondary/40': !notification.read_at }"
          >
            <button
              class="flex w-full items-start gap-3 p-4 text-left transition-colors hover:bg-secondary"
              @click="navigateToNotification(notification)"
            >
              <!-- Icon -->
              <div
                :class="[
                  'mt-0.5 flex size-9 shrink-0 items-center justify-center border border-border text-muted-foreground',
                ]"
              >
                <component :is="getNotificationIconComponent(notification)" class="size-4" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0 space-y-1">
                <!-- Title with subject avatar -->
                <div class="flex items-center gap-2">
                  <img
                    v-if="notification.data.subject?.image"
                    :src="notification.data.subject.image"
                    :alt="notification.data.subject.name"
                    class="size-4 object-cover"
                  >
                  <p
                    class="text-sm truncate"
                    :class="notification.read_at
                      ? 'font-medium text-muted-foreground'
                      : 'font-semibold text-foreground'"
                  >
                    {{ getNotificationTitleText(notification) }}
                  </p>
                </div>

                <!-- Body -->
                <p
                  class="line-clamp-2 text-xs text-muted-foreground"
                  v-html="getNotificationMessageText(notification)"
                />

                <!-- Timestamp -->
                <p class="text-xs text-muted-foreground">
                  {{ getFormattedTime(notification) }}
                </p>
              </div>

              <!-- Mark as read button (shows on hover or when unread) -->
              <div class="shrink-0 flex items-center">
                <Transition name="fade">
                  <button
                    v-if="!notification.read_at"
                    class="flex size-9 items-center justify-center border border-border text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground pointer-coarse:size-11"
                    :title="$t('Mark as read')"
                    @click.stop="markAsRead(notification.id)"
                  >
                    <CheckIcon class="size-4" />
                  </button>
                </Transition>
              </div>
            </button>

            <!-- The ask. A sibling of the row button: a button cannot nest inside a button. -->
            <div
              v-if="getPrimaryAction(notification)"
              class="-mt-2 pb-3 pl-16 pr-4"
            >
              <Button
                variant="outline"
                size="xs"
                class="max-sm:h-11"
                @click="openAction(notification, getPrimaryAction(notification)!.url)"
              >
                {{ getPrimaryAction(notification)!.label }}
              </Button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="notifications.length === 0"
          class="flex flex-col items-center justify-center h-full p-8 text-center"
        >
          <div class="mb-3 flex size-12 items-center justify-center border border-border bg-secondary">
            <BellIcon class="size-6 text-muted-foreground" />
          </div>
          <h3 class="text-sm font-medium text-foreground">
            {{ $t('No notifications') }}
          </h3>
          <p class="mt-1 max-w-[200px] text-xs text-muted-foreground">
            {{ $t("You're all caught up! New notifications will appear here.") }}
          </p>
        </div>
      </ScrollArea>

      <!-- Footer -->
      <div class="space-y-1.5 border-t border-border p-2">
        <!-- Push notification toggle (compact) -->
        <div
          v-if="pushSupported"
          class="flex items-center justify-between px-2 py-1.5 hover:bg-secondary"
        >
          <div class="flex items-center gap-2">
            <SmartphoneIcon class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            <span class="text-xs text-muted-foreground">{{ $t('notifications.channels.push') }}</span>
          </div>
          <button
            v-if="!hasPushSubscription && canSubscribeToPush"
            class="text-xs font-medium text-foreground hover:underline disabled:opacity-50"
            :disabled="isSubscribingToPush"
            @click="handleSubscribeToPush"
          >
            <LoaderCircleIcon v-if="isSubscribingToPush" class="h-3 w-3 animate-spin" />
            <span v-else>{{ $t('notifications.channels.push_enable') }}</span>
          </button>
          <button
            v-else-if="hasPushSubscription"
            class="text-xs font-medium text-foreground hover:underline disabled:opacity-50"
            :disabled="isUnsubscribingFromPush"
            @click="handleUnsubscribeFromPush"
          >
            <LoaderCircleIcon v-if="isUnsubscribingFromPush" class="h-3 w-3 animate-spin" />
            <span v-else>{{ $t('notifications.channels.push_disable') }}</span>
          </button>
          <span
            v-else-if="pushPermission === 'denied'"
            class="text-xs text-muted-foreground"
          >
            {{ $t('notifications.channels.push_blocked') }}
          </span>
        </div>

        <!-- View all link -->
        <Link
          :href="route('notifications.index')"
          class="flex w-full items-center justify-center gap-1.5 py-2 text-xs font-bold uppercase tracking-wide text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground pointer-coarse:min-h-11"
          @click="isOpen = false"
        >
          {{ $t('notifications.view_all') }}
          <ArrowRightIcon class="h-3 w-3" />
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowRightIcon,
  BellIcon,
  CheckCheckIcon,
  CheckIcon,
  LoaderCircleIcon,
  SmartphoneIcon,
} from 'lucide-vue-next';

import { usePWA } from '@/Composables/usePWA';
import { useRealtimeNotifications } from '@/Composables/useRealtimeNotifications';
import { useUnreadNotificationCount } from '@/Composables/useUnreadNotificationCount';
import {
  getNotificationIcon as getNotificationIconFn,
  getNotificationTitle as getNotificationTitleFn,
  getNotificationMessage as getNotificationMessageFn,
  getNotificationUrl,
  getNotificationPrimaryAction as getPrimaryAction,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';

// Popover open state for transitions
const isOpen = ref(false);

// Get unread notifications from auth.user
const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// PWA push notification state
const {
  pushSupported,
  pushPermission,
  canSubscribeToPush,
  hasPushSubscription,
  isSubscribingToPush,
  isUnsubscribingFromPush,
  subscribeToPush,
  unsubscribeFromPush,
} = usePWA();

// Real-time notifications via Reverb
const {
  hasNewNotification,
} = useRealtimeNotifications();

const handleSubscribeToPush = async () => {
  await subscribeToPush();
};

const handleUnsubscribeFromPush = async () => {
  await unsubscribeFromPush();
};

// Cast the notification data from the backend to our interface
const notifications = computed(() => {
  return (authUser.value?.unreadNotifications || []) as Notification[];
});

const unreadNotificationsCount = useUnreadNotificationCount();

// Wrapper functions for formatting utilities
const getNotificationIconComponent = (notification: Notification) => {
  return getNotificationIconFn(notification);
};

const getNotificationTitleText = (notification: Notification) => {
  return getNotificationTitleFn(notification);
};

const getNotificationMessageText = (notification: Notification) => {
  return getNotificationMessageFn(notification);
};

const getFormattedTime = (notification: Notification) => {
  return formatNotificationTime(notification);
};

// Navigate to notification target URL
const navigateToNotification = (notification: Notification) => {
  const url = getNotificationUrl(notification);
  if (url) {
    markAsRead(notification.id);
    isOpen.value = false;
    router.visit(url);
  }
};

// Open an action's own URL instead of the notification's
const openAction = (notification: Notification, url: string) => {
  markAsRead(notification.id);
  isOpen.value = false;
  router.visit(url);
};

// Mark notification as read
const markAsRead = async (id: string) => {
  await router.post(route('notifications.markAsRead', id), {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Mark all notifications as read
const markAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<style scoped>
@keyframes bell-swing {
  0% { transform: rotate(0deg); }
  15% { transform: rotate(12deg); }
  30% { transform: rotate(-10deg); }
  45% { transform: rotate(8deg); }
  60% { transform: rotate(-6deg); }
  75% { transform: rotate(4deg); }
  100% { transform: rotate(0deg); }
}

.animate-bell-swing {
  animation: bell-swing 0.8s ease;
  transform-origin: top center;
}

/* Count badge transition */
.count-enter-from,
.count-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.count-enter-active,
.count-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.count-enter-to,
.count-leave-from {
  opacity: 1;
  transform: translateY(0);
}

/* Fade transition for mark-as-read button */
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
</style>
