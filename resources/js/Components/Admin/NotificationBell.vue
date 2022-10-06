<template>
  <NBadge :dot="notifications.length > 0">
    <NPopover trigger="click" size="small" :show-arrow="true">
      <template #trigger>
        <NButton circle size="small" text
          ><template #icon><NIcon :component="Alert24Regular"></NIcon></template
        ></NButton>
      </template>
      <div
        v-if="notifications.length > 0"
        class="max-h-96 max-w-xs overflow-auto pr-4 sm:max-w-lg"
      >
        <header class="flex">
          <span class="text-lg font-bold text-zinc-900 dark:text-zinc-50"
            >Pranešimai</span
          >
        </header>
        <NDivider style="margin: 0.5em 0 0.5em 0" />
        <template v-for="notification in notifications" :key="notification.id">
          <NotificationItem
            :notification="notification"
            @mark-as-read="removeNotification"
          />
        </template>
      </div>
      <div v-else><span class="text-xs">Naujų pranešimų nėra.</span></div>
    </NPopover>
  </NBadge>
</template>

<script setup lang="ts">
import { Alert24Regular } from "@vicons/fluent";
import { NBadge, NButton, NDivider, NIcon, NPopover } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

import NotificationItem from "./Notifications/NotificationItem.vue";

const notifications = ref(usePage().props.value.user?.notifications);

const removeNotification = (id: number) => {
  notifications.value = notifications.value.filter(
    (notification: any) => notification.id !== id
  );
};
</script>
