<template>
  <CollectionRow
    :actions="actions ?? notificationRowActions(notification)"
    :spotlight
    class="p-4"
    :class="notification.read_at ? '' : 'bg-secondary/20'"
    data-slot="notification-row"
    @navigate="markRead"
    @select="key => key === 'read' ? emit('markAsRead', notification.id) : emit('delete', notification.id)"
  >
    <template #lead>
      <div :class="['mt-0.5 flex size-9 shrink-0 items-center justify-center border border-border', colors.combined]">
        <component :is="icon" class="size-4" />
      </div>
    </template>

    <div class="space-y-1">
      <p class="flex min-h-5 flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
        <span v-if="!notification.read_at" class="size-1.5 shrink-0 bg-brand-fill" aria-hidden="true" />
        <span class="text-[11px] font-bold uppercase tracking-wide text-brand">{{ categoryTag }}</span>
        <time :datetime="notification.created_at">{{ formattedTime }}</time>
      </p>

      <div class="flex items-center gap-2">
        <img
          v-if="notification.data.subject?.image"
          :src="notification.data.subject.image"
          :alt="notification.data.subject.name"
          class="size-5 shrink-0 object-cover"
        >
        <h4
          class="line-clamp-2 text-sm leading-snug text-pretty"
          :class="notification.read_at ? 'font-medium text-muted-foreground' : 'font-semibold text-foreground'"
        >
          <NotificationLink v-if="url" :href="url" class="transition-colors hover:text-brand" @click="markRead">
            {{ title }}
          </NotificationLink>
          <span v-else>{{ title }}</span>
        </h4>
      </div>

      <p v-if="message" class="line-clamp-2 text-sm text-muted-foreground" v-html="message" />

      <ContextFacts v-if="context.length" class="mt-2" data-slot="notification-context">
        <ContextFact v-for="row in context" :key="row.label" :label="row.label" :value="row.value" />
      </ContextFacts>
    </div>
  </CollectionRow>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { notificationRowActions } from './notificationRowActions';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { ContextFact, ContextFacts } from '@/Components/Patterns';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import {
  getNotificationIcon,
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  getNotificationContext,
  getNotificationCategoryTag,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import NotificationLink from '@/Components/Notifications/NotificationLink.vue';
import { markNotificationRead } from '@/Composables/markNotificationRead';

const props = defineProps<{
  notification: Notification;
  actions?: AdminRecordAction[];
  spotlight?: boolean;
}>();

const emit = defineEmits<{
  (event: 'markAsRead', id: string): void;
  (event: 'delete', id: string): void;
}>();

const icon = computed(() => getNotificationIcon(props.notification));
const colors = computed(() => getNotificationColorClasses(props.notification));
const categoryTag = computed(() => getNotificationCategoryTag(props.notification));
const title = computed(() => getNotificationTitle(props.notification));
const message = computed(() => getNotificationMessage(props.notification));
const url = computed(() => getNotificationUrl(props.notification));
const context = computed(() => getNotificationContext(props.notification));
const formattedTime = computed(() => formatNotificationTime(props.notification));

const markRead = () => {
  if (!props.notification.read_at) {
    markNotificationRead(props.notification.id);
  }
};
</script>
