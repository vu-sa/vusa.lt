<template>
  <NBadge :offset="[-2, -4]" :value="notifications?.length">
    <NPopover
      trigger="click"
      title="Pranešimai"
      size="small"
      placement="bottom-end"
      :show-arrow="true"
      scrollable
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
      <template #header
        ><header class="flex">
          <span class="text-lg font-bold text-zinc-900 dark:text-zinc-50"
            >Pranešimai</span
          >
        </header></template
      >
      <div
        v-if="notifications.length > 0"
        class="max-h-96 max-w-xs overflow-auto pr-4 sm:max-w-lg"
      >
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

<script setup lang="tsx">
import {
  NAvatar,
  NBadge,
  NButton,
  NDivider,
  NIcon,
  NPopover,
  useMessage,
  useNotification,
} from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import Icons from "@/Types/Icons/regular";

import NotificationItem, {
  type NotificationData,
} from "./NotificationItem.vue";

const notifications = ref(
  usePage().props.value.auth?.user?.unreadNotifications
);

const message = useMessage();
const notification = useNotification();

const removeNotification = (id: number) => {
  if (!notifications.value) return;

  notifications.value = notifications.value.filter(
    (notification: any) => notification.id !== id
  );

  message.success("Komentaras pažymėtas kaip perskaitytas.");
};

window.Echo.private(
  "App.Models.User." + usePage().props.value.auth?.user.id
).notification((notificationSent: NotificationData) => {
  notification.info({
    content() {
      return <div v-html={notificationSent.text}></div>;
    },
    avatar() {
      return <NAvatar src={notificationSent.subject.image}></NAvatar>;
    },
  });
});
</script>
