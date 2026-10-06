<template>
  <div class="flex w-full min-w-[280px] items-start gap-2.5">
    <!-- Icon -->
    <div
      :class="[
        'flex size-8 shrink-0 items-center justify-center border border-border',
        colors.combined,
      ]"
    >
      <component :is="IconComponent" class="size-4" />
    </div>

    <!-- Content -->
    <div class="min-w-0 flex-1 py-0.5">
      <p class="truncate text-xs font-semibold text-foreground">
        {{ title }}
      </p>
      <p
        class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
        v-html="message"
      />
    </div>

    <!-- View action -->
    <button
      v-if="url"
      type="button"
      :aria-label="$t('Peržiūrėti')"
      class="inline-flex size-7 shrink-0 items-center justify-center border border-border text-muted-foreground transition-colors hover:border-brand hover:text-foreground"
      @click="handleView"
    >
      <ArrowRight class="size-3.5" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, Bell } from 'lucide-vue-next';

import {
  getNotificationIcon,
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  notificationColors,
  type Notification,
} from '@/Composables/useNotificationFormatting';

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
    return notificationColors.neutral;
  }
  return getNotificationColorClasses(props.notification!);
});

const IconComponent = computed(() => {
  if (!hasValidNotification.value) {
    return Bell;
  }
  return getNotificationIcon(props.notification!);
});

const title = computed(() => {
  if (!hasValidNotification.value) {
    return $t('notifications.toast_fallback_title');
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
