<template>
  <div
    role="button"
    class="flex max-w-sm items-center gap-2 rounded-sm p-2 text-zinc-700 transition hover:bg-zinc-200/80 dark:text-zinc-50 dark:hover:bg-zinc-800/80"
    @click="Inertia.visit(notification.data.object.url)"
  >
    <NIcon :component="People24Regular" />
    <div
      v-if="notificationType"
      class="w-full text-xs text-zinc-700 dark:text-zinc-300"
    >
      <component
        :is="getNotificationComponent(notificationType)"
        :data="notification.data"
      />
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ formatRelativeTime(new Date(notification.created_at)) }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <NButton size="tiny" tertiary circle @click.stop="handleClick">
        <template #icon><NIcon :component="Checkmark24Filled" /></template>
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Checkmark24Filled, People24Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { defineAsyncComponent } from "vue";
import { formatRelativeTime } from "@/Utils/IntlTime";
import { useAxios } from "@vueuse/integrations/useAxios";

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
};

const getNotificationComponent = (type: string) => {
  switch (type) {
    case "MemberRegistered":
      return notificationComponents.MemberRegistered;
    case "CommentNotification":
      return notificationComponents.ModelCommented;
    default:
      return notificationComponents.ModelCommented;
  }
};
</script>
