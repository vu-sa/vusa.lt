<template>
  <div class="flex items-center gap-2">
    <NIcon :component="Comment24Regular" />
    <div>
      <div v-html="notification.data.text"></div>
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ formatRelativeTime(new Date(notification.created_at)) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Comment24Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { formatRelativeTime } from "@/Utils/IntlTime";

defineProps<{
  notification: any;
}>();
</script>
