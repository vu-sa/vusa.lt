<template>
  <button
    class="flex cursor-pointer items-center gap-2 rounded-xs p-2 transition hover:bg-zinc-200/80 dark:hover:bg-zinc-800/80 text-left"
    @click="router.visit(notification.data.object.url)">
    <div v-if="notificationType" class="w-full">
      <component :is="getNotificationComponent(notificationType)" :notification />
    </div>
    <div class="flex flex-col gap-2">
      <Button v-if="!notification.read_at" variant="ghost" size="icon-xs" class="rounded-full" @click.stop="handleClick">
        <IFluentCheckmark24Filled />
      </Button>
    </div>
  </button>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";

import { Button } from "@/Components/ui/button";

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
  let { execute } = useFetch(
    route("notifications.markAsRead", props.notification.id),
    {
      headers: {
        "X-CSRF-TOKEN": usePage().props.csrf_token,
      },
    },
    { immediate: false }
  ).post().json();

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
