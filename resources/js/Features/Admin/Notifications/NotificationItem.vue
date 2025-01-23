<template>
  <div role="button"
    class="flex max-w-sm cursor-pointer items-center gap-2 rounded-xs p-2 text-zinc-700 transition hover:bg-zinc-200/80 dark:text-zinc-50 dark:hover:bg-zinc-800/80"
    @click="router.visit(notification.data.object.url)">
    <div v-if="notificationType" class="w-full text-xs text-zinc-700 dark:text-zinc-300">
      <component :is="getNotificationComponent(notificationType)" :notification="notification" />
    </div>
    <div class="flex flex-col gap-2">
      <NButton size="tiny" tertiary circle @click.stop="handleClick">
        <template #icon>
          <IFluentCheckmark24Filled />
        </template>
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from "vue";
import { router } from "@inertiajs/vue3";
import { useAxios } from "@vueuse/integrations/useAxios";

import { formatRelativeTime } from "@/Utils/IntlTime";

export type NotificationData = {
  object: {
    modelClass: string;
    name: string | null;
    url: string;
  };
  subject: {
    image: string;
    modelClass: string;
    name: string;
  };
  text: string;
};

const props = defineProps<{
  notification: App.Entities.Notification<NotificationData>;
}>();

const emit = defineEmits<{
  (event: "markAsRead", id: string): void;
  (event: "hidePopover"): void;
}>();

const notificationType = props.notification.type.split("\\").pop();

const handleClick = async () => {
  let { execute } = useAxios(
    route("notifications.markAsRead", props.notification.id),
    { method: "post" },
    { immediate: false }
  );

  await execute();

  emit("markAsRead", props.notification.id);
};

const notificationComponents = {
  MemberRegistered: defineAsyncComponent(
    () => import("./NotificationTypes/MemberRegistered.vue")
  ),
  ModelCommented: defineAsyncComponent(
    () => import("./NotificationTypes/ModelCommented.vue")
  ),
  StateChangeNotification: defineAsyncComponent(
    () => import("./NotificationTypes/StateChangeNotification.vue")
  ),
};

const getNotificationComponent = (type: string) => {
  switch (type) {
    case "MemberRegistered":
      return notificationComponents.MemberRegistered;
    case "CommentNotification":
      return notificationComponents.ModelCommented;
    case "StateChangeNotification":
      return notificationComponents.StateChangeNotification;
    default:
      return notificationComponents.ModelCommented;
  }
};
</script>
