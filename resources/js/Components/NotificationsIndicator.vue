<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        size="icon"
        class="relative rounded-full md:w-auto md:px-3 md:gap-2"
        data-tour="notifications-indicator"
      >
        <BellIcon class="h-4 w-4" :class="{ 'animate-bell-swing': hasNewNotification }" />
        <Transition name="count" mode="out-in">
          <span
            :key="`count-${unreadNotificationsCount}`"
            class="hidden text-sm md:inline"
            aria-live="polite"
          >
            {{ unreadNotificationsCount }}
          </span>
        </Transition>
        <span
          v-if="unreadNotificationsCount > 0"
          class="md:hidden absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground"
          aria-live="polite"
        >
          {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
        </span>
        <span class="sr-only">{{ $t('Notifications') }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-96 p-0" align="end">
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $t('Notifications') }}</h4>
        <Button 
          v-if="unreadNotificationsCount > 0" 
          variant="ghost" 
          size="sm" 
          class="h-7 text-xs gap-1.5 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
          @click="markAllAsRead"
        >
          <CheckCheckIcon class="h-3.5 w-3.5" />
          {{ $t('Mark all as read') }}
        </Button>
      </div>
      
      <!-- Notifications List -->
      <ScrollArea class="h-[340px]">
        <TransitionGroup 
          name="notification-list"
          tag="div" 
          class="divide-y divide-zinc-100 dark:divide-zinc-800"
        >
          <div 
            v-for="notification in notifications" 
            :key="notification.id"
            class="group relative"
          >
            <button
              class="flex items-start gap-3 w-full p-4 text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900/50"
              :class="{ 'bg-blue-50/50 dark:bg-blue-950/20': !notification.read_at }"
              @click="navigateToNotification(notification)"
            >
              <!-- Icon -->
              <div 
                :class="[
                  'flex items-center justify-center size-9 rounded-full shrink-0 mt-0.5',
                  getNotificationColorClasses(notification).combined
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
                    class="size-4 rounded-full object-cover"
                  />
                  <p 
                    class="text-sm truncate"
                    :class="notification.read_at 
                      ? 'font-medium text-zinc-700 dark:text-zinc-300' 
                      : 'font-semibold text-zinc-900 dark:text-zinc-100'"
                  >
                    {{ getNotificationTitleText(notification) }}
                  </p>
                </div>
                
                <!-- Body -->
                <p 
                  class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2"
                  v-html="getNotificationMessageText(notification)"
                />
                
                <!-- Timestamp -->
                <p class="text-[11px] text-zinc-500 dark:text-zinc-500">
                  {{ getFormattedTime(notification) }}
                </p>
              </div>

              <!-- Mark as read button (shows on hover or when unread) -->
              <div class="shrink-0 flex items-center">
                <Transition name="fade">
                  <button 
                    v-if="!notification.read_at"
                    class="flex items-center justify-center size-7 rounded-full transition-all opacity-0 group-hover:opacity-100 hover:bg-green-100 dark:hover:bg-green-900/30"
                    :class="{ 'opacity-100': !notification.read_at }"
                    :title="$t('Mark as read')"
                    @click.stop="markAsRead(notification.id)"
                  >
                    <CheckIcon class="size-4 text-green-600 dark:text-green-400" />
                  </button>
                </Transition>
              </div>
            </button>
          </div>
        </TransitionGroup>

        <!-- Empty State -->
        <div 
          v-if="notifications.length === 0" 
          class="flex flex-col items-center justify-center h-full p-8 text-center"
        >
          <div class="flex items-center justify-center size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 mb-3">
            <BellIcon class="size-6 text-zinc-400 dark:text-zinc-500" />
          </div>
          <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ $t('No notifications') }}
          </h3>
          <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 max-w-[200px]">
            {{ $t("You're all caught up! New notifications will appear here.") }}
          </p>
        </div>
      </ScrollArea>
      
      <!-- Footer -->
      <div class="border-t border-zinc-200 dark:border-zinc-800 p-2 space-y-1.5">
        <!-- Push notification toggle (compact) -->
        <div 
          v-if="pushSupported" 
          class="flex items-center justify-between px-2 py-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-900/50"
        >
          <div class="flex items-center gap-2">
            <SmartphoneIcon class="h-4 w-4 text-zinc-500" aria-hidden="true" />
            <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $t('Push pranešimai') }}</span>
          </div>
          <button 
            v-if="!hasPushSubscription && canSubscribeToPush"
            class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 disabled:opacity-50"
            :disabled="isSubscribingToPush"
            @click="handleSubscribeToPush"
          >
            <LoaderCircleIcon v-if="isSubscribingToPush" class="h-3 w-3 animate-spin" />
            <span v-else>{{ $t('Įjungti') }}</span>
          </button>
          <button 
            v-else-if="hasPushSubscription"
            class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50"
            :disabled="isUnsubscribingFromPush"
            @click="handleUnsubscribeFromPush"
          >
            <LoaderCircleIcon v-if="isUnsubscribingFromPush" class="h-3 w-3 animate-spin" />
            <span v-else>{{ $t('Išjungti') }}</span>
          </button>
          <span 
            v-else-if="pushPermission === 'denied'" 
            class="text-xs text-red-500"
          >
            {{ $t('Užblokuota') }}
          </span>
        </div>
        
        <!-- View all link -->
        <Link 
          :href="route('notifications.index')" 
          class="flex items-center justify-center gap-1.5 w-full py-2 text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
          @click="isOpen = false"
        >
          {{ $t('Rodyti visus pranešimus') }}
          <ArrowRightIcon class="h-3 w-3" />
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"
import {
  ArrowRightIcon,
  BellIcon,
  CheckCheckIcon,
  CheckIcon,
  LoaderCircleIcon,
  SmartphoneIcon,
} from 'lucide-vue-next'
import { usePWA } from '@/Composables/usePWA'
import { useRealtimeNotifications } from '@/Composables/useRealtimeNotifications'
import {
  getNotificationIcon as getNotificationIconFn,
  getNotificationColorClasses as getNotificationColorClassesFn,
  getNotificationTitle as getNotificationTitleFn,
  getNotificationMessage as getNotificationMessageFn,
  getNotificationUrl,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting'

import { 
  Popover, 
  PopoverContent, 
  PopoverTrigger 
} from '@/Components/ui/popover'
import { Button } from '@/Components/ui/button'
import { ScrollArea } from '@/Components/ui/scroll-area'

// Popover open state for transitions
const isOpen = ref(false)

// Get unread notifications from auth.user
const page = usePage()
const authUser = computed(() => page.props.auth?.user)

// PWA push notification state
const { 
  pushSupported, 
  pushPermission, 
  canSubscribeToPush, 
  hasPushSubscription, 
  isSubscribingToPush,
  isUnsubscribingFromPush,
  subscribeToPush, 
  unsubscribeFromPush 
} = usePWA()

// Real-time notifications via Reverb
const {
  hasNewNotification,
} = useRealtimeNotifications()

const handleSubscribeToPush = async () => {
  await subscribeToPush()
}

const handleUnsubscribeFromPush = async () => {
  await unsubscribeFromPush()
}

// Cast the notification data from the backend to our interface
const notifications = computed(() => {
  return (authUser.value?.unreadNotifications || []) as Notification[]
})

const unreadNotificationsCount = computed(() => {
  return notifications.value.filter(notification => !notification.read_at).length
})

// Wrapper functions for formatting utilities
const getNotificationIconComponent = (notification: Notification) => {
  return getNotificationIconFn(notification)
}

const getNotificationColorClasses = (notification: Notification) => {
  return getNotificationColorClassesFn(notification)
}

const getNotificationTitleText = (notification: Notification) => {
  return getNotificationTitleFn(notification)
}

const getNotificationMessageText = (notification: Notification) => {
  return getNotificationMessageFn(notification)
}

const getFormattedTime = (notification: Notification) => {
  return formatNotificationTime(notification)
}

// Navigate to notification target URL
const navigateToNotification = (notification: Notification) => {
  const url = getNotificationUrl(notification)
  if (url) {
    markAsRead(notification.id)
    isOpen.value = false
    router.visit(url)
  }
}

// Mark notification as read
const markAsRead = async (id: string) => {
  await router.post(route('notifications.markAsRead', id), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Mark all notifications as read
const markAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}
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

/* Notification list transitions */
.notification-list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.notification-list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.notification-list-enter-active {
  transition: all 0.3s ease-out;
}

.notification-list-leave-active {
  transition: all 0.2s ease-in;
  position: absolute;
  width: 100%;
}

.notification-list-move {
  transition: transform 0.3s ease;
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