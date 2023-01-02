<template>
  <div
    class="flex max-w-sm items-center gap-2 rounded-sm p-2 text-zinc-700 transition hover:bg-zinc-200/80 dark:text-zinc-50 dark:hover:bg-zinc-800/80"
  >
    <NIcon :component="People24Regular" />
    <div class="w-full">
      <p class="text-xs text-zinc-700 dark:text-zinc-300">
        <component
          :is="getNotificationComponent(notification.type)"
          :data="notification.data"
        />
      </p>
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ getRelativeTime(notification.created_at) }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <Link
        :href="
          route(
            notification.data.route['routeName'],
            notification.data.route['model']
          )
        "
      >
        <NButton v-if="notification.data.route" size="tiny" tertiary circle>
          <template #icon><NIcon :component="ArrowForward24Filled" /></template>
        </NButton>
      </Link>
      <Link
        method="post"
        :href="route('notifications.markAsRead', notification.id)"
      >
        <NButton
          size="tiny"
          tertiary
          circle
          @click="$emit('markAsRead', notification.id)"
        >
          <template #icon><NIcon :component="Checkmark24Filled" /></template>
        </NButton>
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowForward24Filled,
  Checkmark24Filled,
  People24Regular,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { defineAsyncComponent } from "vue";
import route from "ziggy-js";

import getRelativeTime from "@/Composables/getRelativeTime";

defineProps<{
  notification: Record<string, any>;
}>();

defineEmits<{ (event: "markAsRead", id: number): void }>();

const MemberRegistered = defineAsyncComponent(
  () =>
    import("@/Components/Notifications/NotificationTypes/MemberRegistered.vue")
);

const CommentNotification = defineAsyncComponent(
  () =>
    import(
      "@/Components/Notifications/NotificationTypes/CommentNotification.vue"
    )
);

const getNotificationComponent = (type: string) => {
  console.log(type);

  switch (type) {
    case "App\\Notifications\\MemberRegistered":
      return MemberRegistered;
    case "App\\Notifications\\CommentNotification":
      return CommentNotification;
    default:
      return MemberRegistered;
  }
};
</script>
