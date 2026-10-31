<template>
  <article
    class="group relative flex items-start gap-3 p-4 transition-colors hover:bg-secondary/40 sm:gap-4"
    :class="notification.read_at ? '' : 'bg-secondary/20'"
    data-slot="notification-row"
  >
    <!-- Icon -->
    <div
      :class="[
        'mt-0.5 flex size-9 shrink-0 items-center justify-center border border-border sm:size-10',
        colors.combined,
      ]"
    >
      <component :is="icon" class="size-4 sm:size-5" />
    </div>

    <!-- Content -->
    <div class="min-w-0 flex-1 space-y-1">
      <!-- Eyebrow: Category Tag & Unread marker dot -->
      <div class="flex items-center gap-1.5">
        <span class="text-[10px] font-bold uppercase tracking-wide text-brand">
          {{ categoryTag }}
        </span>
        <span
          v-if="!notification.read_at"
          class="size-1.5 shrink-0 bg-brand-fill"
          aria-hidden="true"
        />
      </div>

      <!-- Title with avatar if present -->
      <div class="flex items-center gap-2">
        <img
          v-if="notification.data.subject?.image"
          :src="notification.data.subject.image"
          :alt="notification.data.subject.name"
          class="size-4 shrink-0 object-cover sm:size-5"
        >
        <h4
          class="text-sm leading-snug line-clamp-2 text-pretty"
          :class="notification.read_at ? 'font-medium text-muted-foreground' : 'font-semibold text-foreground'"
        >
          <NotificationLink
            v-if="url"
            :href="url"
            class="transition-colors hover:text-brand"
            @click="markRead"
          >
            {{ title }}
          </NotificationLink>
          <span v-else>{{ title }}</span>
        </h4>
      </div>

      <!-- Body message -->
      <p
        v-if="message"
        class="line-clamp-2 text-xs text-muted-foreground sm:text-sm"
        v-html="message"
      />

      <!-- Context rows: what this is about -->
      <dl
        v-if="context.length"
        data-slot="notification-context"
        class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 border-t border-border/60 pt-2 text-xs"
      >
        <template v-for="row in context" :key="row.label">
          <dt class="text-muted-foreground">
            {{ row.label }}
          </dt>
          <dd class="min-w-0 truncate font-medium text-foreground">
            {{ row.value }}
          </dd>
        </template>
      </dl>

      <!-- Timestamp -->
      <div class="pt-1">
        <span class="text-xs text-muted-foreground">
          {{ formattedTime }}
        </span>
      </div>
    </div>

    <div data-slot="notification-row-actions">
      <CollectionRowActions :spotlight :actions="actions ?? notificationRowActions(notification)" @navigate="markRead" @select="key => key === 'read' ? emit('markAsRead', notification.id) : emit('delete', notification.id)" />
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { notificationRowActions } from './notificationRowActions';

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
