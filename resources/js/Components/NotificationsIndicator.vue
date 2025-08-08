<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="outline" size="icon" class="rounded-full relative">
        <BellIcon class="h-4 w-4" />
        <span v-if="unreadNotificationsCount > 0" class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
          {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
        </span>
        <span class="sr-only">{{ $t('Notifications') }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80 p-0">
      <div class="p-4 border-b">
        <div class="flex items-center justify-between">
          <h4 class="font-medium">{{ $t('Notifications') }}</h4>
          <Button v-if="unreadNotificationsCount > 0" 
            variant="ghost" 
            size="sm" 
            class="h-7 text-xs"
            @click="markAllAsRead"
          >
            {{ $t('Mark all as read') }}
          </Button>
        </div>
      </div>
      <ScrollArea class="h-[300px]">
        <div v-if="notifications.length > 0" class="divide-y">
          <div 
            v-for="notification in notifications" 
            :key="notification.id" 
            class="flex items-start gap-2 p-4 cursor-pointer"
            :class="{ 'bg-muted/30': !notification.read_at }"
            @click="navigateToNotification(notification)"
          >
            <div :class="[
              'mt-0.5 rounded-full p-1',
              getNotificationIconClass(getNotificationType(notification))
            ]">
              <component :is="getNotificationIcon(getNotificationType(notification))" class="h-3 w-3" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium" :class="{ 'font-bold': !notification.read_at }">
                {{ getNotificationTitle(notification) }}
              </p>
              <div class="text-xs text-muted-foreground">
                {{  }}
                <div v-html="getNotificationMessage(notification)" />
              </div>
              <p class="mt-1 text-[11px] text-muted-foreground">
                {{ formatRelativeTime(new Date(notification.created_at)) }}
              </p>
            </div>
            <Button 
              v-if="!notification.read_at" 
              variant="ghost" 
              size="icon" 
              class="h-6 w-6"
              @click.stop="markAsRead(notification.id)"
            >
              <CircleIcon class="h-3 w-3" />
              <span class="sr-only">{{ $t('Mark as read') }}</span>
            </Button>
          </div>
        </div>
        <div v-else class="flex h-full items-center justify-center p-8 text-center">
          <div>
            <BellIcon class="mx-auto h-6 w-6 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-medium">{{ $t('No notifications') }}</h3>
            <p class="mt-1 text-xs text-muted-foreground">
              {{ $t('You have no unread notifications') }}
            </p>
          </div>
        </div>
      </ScrollArea>
      <div class="border-t p-2">
        <Link :href="route('notifications.index')" class="block w-full rounded-sm p-2 text-center text-xs hover:bg-muted">
          {{ $t('View All Notifications') }}
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"
import { 
  BellIcon, 
  CircleIcon, 
  MessageSquareIcon, 
  CalendarIcon, 
  UserPlusIcon, 
  AlertCircleIcon,
  CheckIcon
} from 'lucide-vue-next'

import { 
  Popover, 
  PopoverContent, 
  PopoverTrigger 
} from '@/Components/ui/popover'
import { Button } from '@/Components/ui/button'
import { ScrollArea } from '@/Components/ui/scroll-area'

interface BaseNotification {
  id: string;
  type: string;
  created_at: string;
  read_at: string | null;
  data: Record<string, any>;
}

// Get unread notifications from auth.user
const page = usePage()
const authUser = computed(() => page.props.auth?.user)

// Cast the notification data from the backend to our interface
const notifications = computed(() => {
  return (authUser.value?.unreadNotifications || []) as BaseNotification[]
})

const unreadNotificationsCount = computed(() => {
  return notifications.value.filter(notification => !notification.read_at).length
})

// Extract notification type (class name without namespace)
const getNotificationType = (notification: BaseNotification): string => {
  const typeParts = notification.type.split('\\')
  return typeParts[typeParts.length - 1] || 'Unknown'
}

// Get notification title based on type and data
const getNotificationTitle = (notification: BaseNotification): string => {
  const type = getNotificationType(notification)
  const data = notification.data
  
  switch (type) {
    case 'ModelCommented':
      return $t('New Comment')
    case 'MemberRegistered':
      return $t('New Member Registration')
    case 'UserAttachedToModel':
      return $t('Assignment Notification')
    default:
      return data.subject?.name || $t('Notification')
  }
}

// Get notification message based on type and data
const getNotificationMessage = (notification: BaseNotification): string => {
  const data = notification.data
  
  if (data.text) {
    return data.text
  }
  
  if (data.object?.name) {
    return `${data.subject?.name || ''} ${$t('on')} ${data.object.name}`
  }
  
  return data.message || $t('You have a new notification')
}

// Icon mapping based on notification type
const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'ModelCommented':
      return MessageSquareIcon
    case 'MemberRegistered':
      return UserPlusIcon
    case 'UserAttachedToModel':
      return UserPlusIcon
    default:
      return BellIcon
  }
}

// Icon styling based on notification type
const getNotificationIconClass = (type: string) => {
  switch (type) {
    case 'ModelCommented':
      return 'bg-blue-500/20 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400'
    case 'MemberRegistered':
      return 'bg-green-500/20 text-green-600 dark:bg-green-500/10 dark:text-green-400'
    case 'UserAttachedToModel':
      return 'bg-purple-500/20 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400'
    default:
      return 'bg-gray-500/20 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'
  }
}

// Navigate to notification target URL
const navigateToNotification = (notification: BaseNotification) => {
  const url = notification.data.object?.url
  if (url) {
    markAsRead(notification.id)
    router.visit(url)
  }
}

// Format relative time (e.g., "2 hours ago")
const formatRelativeTime = (date: Date) => {
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffSec = Math.round(diffMs / 1000)
  const diffMin = Math.round(diffSec / 60)
  const diffHour = Math.round(diffMin / 60)
  const diffDay = Math.round(diffHour / 24)

  if (diffSec < 60) {
    return $t('Just now')
  } else if (diffMin < 60) {
    return diffMin + ' ' + $t('minutes ago')
  } else if (diffHour < 24) {
    return diffHour + ' ' + $t('hours ago')
  } else if (diffDay === 1) {
    return $t('Yesterday')
  } else {
    return diffDay + ' ' + $t('days ago')
  }
}

// Mark notification as read
const markAsRead = async (id: string) => {
  await router.post(route('notifications.markAsRead', id), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Update the notification in the list
      const notification = notifications.value.find(n => n.id === id)
      if (notification) {
        notification.read_at = new Date().toISOString()
      }
    }
  })
}

// Mark all notifications as read
const markAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Mark all as read locally
      notifications.value.forEach(notification => {
        notification.read_at = new Date().toISOString()
      })
    }
  })
}
</script>