<template>
  <div class="flex items-center gap-2">
    <IFluentComment24Regular />
    <div>
      <div v-html="notification.data.text" />
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ formatRelativeTime(new Date(notification.created_at)) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatRelativeTime } from '@/Utils/IntlTime';

defineProps<{
  notification: any;
}>();
</script>
