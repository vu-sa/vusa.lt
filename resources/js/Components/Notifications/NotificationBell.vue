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
        ><header class="flex justify-between gap-4">
          <span class="text-lg font-bold text-zinc-900 dark:text-zinc-50"
            >Pranešimai</span
          ><NButton
            :disabled="notifications.length === 0"
            size="tiny"
            :loading="loading"
            text
            @click="handleAllRead"
            >Pažymėti visus<template #icon
              ><NIcon :component="CheckmarkCircle24Regular"></NIcon></template
          ></NButton></header
      ></template>
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
import { usePage } from "@inertiajs/vue3";
import { useWebNotification } from "@vueuse/core";
import Icons from "@/Types/Icons/regular";
import type { EventHook, UseWebNotificationOptions } from "@vueuse/core";

import { CheckmarkCircle24Regular } from "@vicons/fluent";
import { useAxios } from "@vueuse/integrations/useAxios";
import NotificationItem, {
  type NotificationData,
} from "./NotificationItem.vue";

const notifications = ref(
  usePage().props.auth?.user?.unreadNotifications
);

const message = useMessage();
const loading = ref(false);

const removeNotification = (id: number) => {
  if (!notifications.value) return;

  notifications.value = notifications.value.filter(
    (notification: any) => notification.id !== id
  );

  message.success("Komentaras pažymėtas kaip perskaitytas.");
};

const handleAllRead = async () => {
  loading.value = true;

  const { isFinished } = await useAxios(route("notifications.markAllAsRead"), {
    method: "POST",
  });

  if (isFinished.value) {
    notifications.value = [];
    loading.value = false;
    message.success("Visi pranešimai pažymėti kaip perskaityti.");
  }
};

const notification = useNotification();
const onWebNotificationClick = ref<EventHook | null>(null);

// window.Echo.private(
//   "App.Models.User." + usePage().props.auth?.user.id
// ).notification((notificationSent: NotificationData) => {
//   notification.info({
//     content() {
//       return <div v-html={notificationSent.text}></div>;
//     },
//     avatar() {
//       return (
//         <NAvatar
//           src={
//             notificationSent.subject?.image ??
//             usePage().props.auth?.user.profile_photo_path
//           }
//         ></NAvatar>
//       );
//     },
//   });

//   const options: UseWebNotificationOptions = {
//     title: notificationSent.text.replaceAll(/<\/?[^>]+(>|$)/gi, ""),
//     dir: "auto",
//     lang: usePage().props.locale,
//     renotify: true,
//     tag: "notification",
//     icon: notificationSent.subject?.image ?? usePage().props.auth?.user,
//   };

//   const { isSupported, onClick, show } = useWebNotification(options);

//   if (isSupported.value) {
//     show();
//     onClick.on((evt: Event) => {
//       window.location.assign(notificationSent.object.url);
//     });
//   }
// });
</script>
