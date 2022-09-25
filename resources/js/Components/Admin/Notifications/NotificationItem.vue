<template>
  <div
    class="flex max-w-sm items-center gap-2 rounded-sm p-2 text-zinc-700 transition hover:bg-zinc-200/80 dark:text-zinc-50 dark:hover:bg-zinc-800/80"
  >
    <NIcon :component="People24Regular" />
    <div>
      <p class="text-xs text-zinc-700 dark:text-zinc-300">
        <strong>{{ notification.data.registeredName }}</strong> užsiregistravo
        per VU SA registraciją.
      </p>
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ getRelativeTime(notification.created_at) }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <NButton
        tag="a"
        target="_blank"
        :href="route('registrationForms.show', 2)"
        size="tiny"
        tertiary
        circle
      >
        <template #icon><NIcon :component="ArrowForward24Filled" /></template>
      </NButton>
      <NButton
        :tag="Link"
        method="post"
        :href="route('notifications.markAsRead', notification.id)"
        size="tiny"
        tertiary
        circle
        @click="$emit('markAsRead', notification.id)"
      >
        <template #icon><NIcon :component="Checkmark24Filled" /></template>
      </NButton>
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
import route from "ziggy-js";

defineProps<{
  notification: any;
}>();

defineEmits<{ (event: "markAsRead", id: number): void }>();

const getRelativeTime = (timestamp: string) => {
  const DAY_MILLISECONDS = 1000 * 60 * 60 * 24;

  const notificationDateTime = new Date(timestamp);

  const rtf = new Intl.RelativeTimeFormat("lt", {
    numeric: "auto",
  });
  const daysDifference = Math.round(
    (notificationDateTime.getTime() - new Date().getTime()) / DAY_MILLISECONDS
  );

  return rtf.format(daysDifference, "day");
};
</script>
