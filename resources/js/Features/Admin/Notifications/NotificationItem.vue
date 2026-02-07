<template>
  <button
    class="flex cursor-pointer items-center gap-2 rounded-xs p-2 transition hover:bg-zinc-200/80 dark:hover:bg-zinc-800/80 text-left w-full"
    :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !notification.read_at }"
    @click="handleNavigate"
  >
    <UnifiedNotification
      :notification="notification"
      class="flex-1 min-w-0"
      @mute-thread="handleMuteThread"
    />

    <div class="flex flex-col gap-1 shrink-0">
      <Button
        v-if="!notification.read_at"
        variant="ghost"
        size="icon-xs"
        class="rounded-full"
        :title="$t('notifications.mark_as_read')"
        @click.stop="handleMarkAsRead"
      >
        <IFluentCheckmark24Filled />
      </Button>
    </div>
  </button>
</template>

<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";

import { Button } from "@/Components/ui/button";
import UnifiedNotification from "./NotificationTypes/UnifiedNotification.vue";
import IFluentCheckmark24Filled from '~icons/fluent/checkmark24-filled';

export type NotificationData = {
  // New standardized structure
  category?: string;
  modelClass?: string;
  title?: string;
  body?: string;
  url?: string;
  icon?: string;
  color?: string;
  actions?: Array<{ label: string; url: string }>;
  subject?: {
    modelClass: string;
    name: string;
    image?: string;
  };
  object?: {
    modelClass: string;
    name: string | null;
    url: string;
    id?: string;
  };
  // Legacy fields for backward compatibility
  text?: string;
};

const props = defineProps<{
  notification: App.Entities.Notification<NotificationData>;
}>();

const emit = defineEmits<{
  (event: "markAsRead", id: string): void;
  (event: "hidePopover"): void;
  (event: "muteThread", modelClass: string, modelId: string): void;
}>();

const handleNavigate = () => {
  const url = props.notification.data.url || props.notification.data.object?.url;
  if (url) {
    emit("hidePopover");
    router.visit(url);
  }
};

const handleMarkAsRead = async () => {
  const { execute } = useFetch(
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

const handleMuteThread = (modelClass: string, modelId: string) => {
  emit("muteThread", modelClass, modelId);
};
</script>
