<template>
  <NBadge :offset="[-2, -4]" :value="notifications?.length">
    <NPopover
      trigger="click"
      size="small"
      placement="bottom-end"
      :show-arrow="true"
    >
      <template #trigger>
        <NButton circle text
          ><template #icon
            ><NIcon
              :size="24"
              :component="Icons.NOTIFICATION"
            ></NIcon></template
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
import { NBadge, NButton, NDivider, NIcon, NPopover } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import Icons from "@/Types/Icons/regular";

import NotificationItem from "./NotificationItem.vue";

const notifications = ref(
  usePage().props.value.auth?.user?.unreadNotifications
);

const removeNotification = (id: number) => {
  if (!notifications.value) return;

  notifications.value = notifications.value.filter(
    (notification: any) => notification.id !== id
  );
};
</script>
