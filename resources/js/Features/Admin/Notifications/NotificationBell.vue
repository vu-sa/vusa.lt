<template>
  <NBadge :offset="[-2, -4]" :value="notifications?.length">
    <NPopover trigger="click" title="Pranešimai" :show-arrow="true" scrollable>
      <template #trigger>
        <NButton v-bind="$attrs">
          <template #icon>
            <NIcon :size="18" :component="Icons.NOTIFICATION" />
          </template>
        </NButton>
      </template>
      <template #header>
        <header class="flex justify-between gap-4 min-w-xs items-center">
          <span class="text-lg font-bold text-zinc-900 dark:text-zinc-50">{{
            $t("Pranešimai")
            }}</span>
          <SmartLink :href="route('notifications.index')" class="text-xs gap-1 items-center inline-flex text-zinc-500 dark:text-zinc-400">
            {{
              $t("Žiūrėti visus")
            }}
            <IFluentChevronRight24Regular />
          </SmartLink>
        </header>
      </template>
      <div v-if="notifications.length > 0" class="max-h-96 max-w-xs overflow-auto pr-4 sm:max-w-lg">
        <template v-for="notification in notifications" :key="notification.id">
          <NotificationItem :notification="notification" @mark-as-read="removeNotification" />
        </template>
      </div>
      <div v-else>
        <span class="text-xs">{{ $t("Naujų pranešimų nėra") }}.</span>
      </div>
    </NPopover>
  </NBadge>
</template>

<script setup lang="tsx">
import {
  useMessage,
  useNotification,
} from "naive-ui";
import {
  type UseWebNotificationOptions,
  useWebNotification,
} from "@vueuse/core";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import Icons from "@/Types/Icons/regular";

import NotificationItem from "./NotificationItem.vue";
import type { NotificationData } from "./NotificationItem.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

const notifications = ref(usePage().props.auth?.user?.unreadNotifications);

const message = useMessage();

const removeNotification = (id: number) => {
  if (!notifications.value) return;

  notifications.value = notifications.value.filter(
    (notification: any) => notification.id !== id
  );

  message.success("Komentaras pažymėtas kaip perskaitytas.");
};

const notification = useNotification();

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
//     lang: usePage().props.app.locale,
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
