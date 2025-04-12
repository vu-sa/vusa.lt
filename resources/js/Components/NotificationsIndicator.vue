<script setup lang="ts">
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { BellIcon } from 'lucide-vue-next'
import { 
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/Components/ui/popover'
import { Separator } from '@/Components/ui/separator'
import { trans as $t } from 'laravel-vue-i18n'

// References to unreadNotifications from auth user data
const notifications = ref(usePage().props.auth?.user?.unreadNotifications || [])

// Function to mark notification as read
const markAsRead = (id) => {
  router.post(route('notifications.markAsRead', { id }), {}, {
    preserveState: true,
    onSuccess: () => {
      // Update local state
      notifications.value = notifications.value.filter(notification => notification.id !== id)
    }
  })
}

// View all notifications
const viewAllNotifications = () => {
  router.visit(route('notifications.index'))
}
</script>

<template>
  <div class="relative">
    <Badge 
      v-if="notifications.length > 0" 
      class="absolute -right-1 -top-1 z-10"
      variant="destructive"
    >
      {{ notifications.length }}
    </Badge>
    
    <Popover>
      <PopoverTrigger>
        <Button variant="ghost" size="icon">
          <BellIcon class="size-5" />
          <span class="sr-only">{{ $t('Pranešimai') }}</span>
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-80">
        <div class="flex justify-between items-center mb-2">
          <h4 class="font-medium text-sm">{{ $t('Pranešimai') }}</h4>
          <Button @click="viewAllNotifications" variant="link" size="sm" class="p-0 h-auto text-xs">
            {{ $t('Žiūrėti visus') }}
          </Button>
        </div>
        <Separator class="mb-2" />
        
        <div class="max-h-[300px] overflow-y-auto">
          <div v-if="notifications.length === 0" class="py-2 px-1 text-xs text-muted-foreground">
            {{ $t('Naujų pranešimų nėra') }}
          </div>
          <div v-else class="space-y-2">
            <div v-for="notification in notifications" :key="notification.id" class="p-2 rounded-md hover:bg-muted text-sm">
              <div class="flex justify-between">
                <div v-html="notification.text"></div>
                <Button 
                  @click="markAsRead(notification.id)" 
                  variant="ghost" 
                  size="sm" 
                  class="h-5 w-5 p-0 opacity-50 hover:opacity-100"
                >
                  <span class="sr-only">{{ $t('Pažymėti kaip perskaitytą') }}</span>
                  <span class="text-xs">×</span>
                </Button>
              </div>
              <div v-if="notification.subject" class="text-xs text-muted-foreground mt-1">
                {{ notification.subject.title || notification.subject.name }}
              </div>
            </div>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>