<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
  getNotificationIcon, 
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  notificationColors,
  type Notification 
} from '@/Composables/useNotificationFormatting';

import IFluentAlert24Regular from '~icons/fluent/alert24-regular';
import IFluentArrowRight16Filled from '~icons/fluent/arrow-right16-filled';

const props = defineProps<{
  notification?: Notification;
}>();

// Helper to check if notification is valid
const hasValidNotification = computed(() => {
  return props.notification && props.notification.data && typeof props.notification.data === 'object';
});

// Use computed to handle potentially undefined notification
const colors = computed(() => {
  if (!hasValidNotification.value) {
    return notificationColors.gray;
  }
  return getNotificationColorClasses(props.notification!);
});

const IconComponent = computed(() => {
  if (!hasValidNotification.value) {
    return IFluentAlert24Regular;
  }
  return getNotificationIcon(props.notification!);
});

const title = computed(() => {
  if (!hasValidNotification.value) {
    return $t('Naujas pranešimas');
  }
  return getNotificationTitle(props.notification!);
});

const message = computed(() => {
  if (!hasValidNotification.value) {
    return '';
  }
  return getNotificationMessage(props.notification!);
});

const url = computed(() => {
  if (!hasValidNotification.value) {
    return null;
  }
  return getNotificationUrl(props.notification!);
});

const handleView = () => {
  if (url.value) {
    router.visit(url.value);
  }
};
</script>

<template>
  <div class="flex items-start gap-2.5 w-full min-w-[280px]">
    <!-- Icon -->
    <div
      :class="[
        'flex items-center justify-center size-8 rounded-full shrink-0',
        colors.combined
      ]"
    >
      <component :is="IconComponent" class="size-4" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0 py-0.5">
      <p class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate">
        {{ title }}
      </p>
      <p 
        class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 mt-0.5"
        v-html="message"
      />
    </div>

    <!-- View action -->
    <button
      v-if="url"
      @click="handleView"
      class="inline-flex items-center justify-center size-7 rounded-md text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
    >
      <IFluentArrowRight16Filled class="size-4" />
    </button>
  </div>
</template>
