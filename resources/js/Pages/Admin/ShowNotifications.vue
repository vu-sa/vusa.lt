<template>
  <AdminContentPage :title="$t('Pranešimai')">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <!-- Filters -->
      <ToggleGroup
        type="single"
        :model-value="activeFilter"
        @update:model-value="(value) => { if (value) activeFilter = value as FilterValue; }"
      >
        <ToggleGroupItem value="unread">
          {{ $t('notifications.index.filter_unread') }}
          <span v-if="unreadCount > 0" class="ml-1.5 tabular-nums text-xs text-muted-foreground">
            {{ unreadCount }}
          </span>
        </ToggleGroupItem>
        <ToggleGroupItem value="all">
          {{ $t('Visi') }}
          <span v-if="notifications.length > 0" class="ml-1.5 tabular-nums text-xs text-muted-foreground">
            {{ notifications.length }}
          </span>
        </ToggleGroupItem>
      </ToggleGroup>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <DropdownMenu v-if="notifications.length > 0">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-1.5">
              <MoreHorizontal class="size-4" />
              {{ $t('notifications.index.actions') }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuItem
              v-if="unreadCount > 0"
              class="gap-2"
              @click="handleMarkAllAsRead"
            >
              <CheckCircle2 class="size-4" />
              {{ $t('notifications.index.mark_all_read') }}
            </DropdownMenuItem>
            <DropdownMenuItem
              v-if="readCount > 0"
              class="gap-2 text-status-attention focus:text-status-attention"
              @click="handleDeleteRead"
            >
              <Trash2 class="size-4" />
              {{ $t('notifications.index.delete_read') }}
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="unreadCount > 0 || readCount > 0" />
            <DropdownMenuItem
              class="gap-2 text-destructive focus:text-destructive"
              @click="handleDeleteAll"
            >
              <XCircle class="size-4" />
              {{ $t('notifications.index.delete_all') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Notifications Content -->
    <div class="space-y-6">
      <!-- Empty State -->
      <EmptyState
        v-if="shownNotifications.length === 0"
        :mode="activeFilter === 'unread' && readCount > 0 ? 'no-results' : 'empty'"
        :icon="BellOff"
        :title="activeFilter === 'unread' ? $t('notifications.index.empty_unread_title') : $t('notifications.index.empty_all_title')"
        :description="activeFilter === 'unread' ? $t('notifications.index.empty_unread_body') : $t('notifications.index.empty_all_body')"
        :clear-label="$t('notifications.view_all')"
        @clear="activeFilter = 'all'"
      />

      <!-- Grouped Notifications -->
      <template v-else>
        <div
          v-for="[period, periodNotifications] in groupedNotifications"
          :key="period"
          class="space-y-2"
        >
          <!-- Period Header -->
          <div class="flex items-center gap-3 px-1">
            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              {{ period }}
            </h3>
            <div class="flex-1 border-t border-border" />
            <span class="text-xs text-muted-foreground">
              {{ periodNotifications.length }}
            </span>
          </div>

          <!-- Notification rows -->
          <div class="divide-y divide-border border-y border-border">
            <NotificationCard
              v-for="notification in periodNotifications"
              :key="notification.id"
              :notification
              @mark-as-read="handleMarkAsRead"
              @delete="handleDelete"
            />
          </div>
        </div>
      </template>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BellOff, CheckCircle2, MoreHorizontal, Trash2, XCircle } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import NotificationCard from '@/Features/Admin/Notifications/NotificationCard.vue';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { EmptyState } from '@/Components/Patterns';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useToasts } from '@/Composables/useToasts';
import {
  groupNotificationsByTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import { NotificationIcon } from '@/Components/icons';

const props = defineProps<{
  notifications: Notification[];
}>();

// Breadcrumbs
usePageBreadcrumbs([
  { label: $t('Pranešimai'), icon: NotificationIcon },
]);

const toasts = useToasts();

// Filter state
type FilterValue = 'all' | 'unread';
const activeFilter = ref<FilterValue>('unread');

const unreadCount = computed(() =>
  props.notifications.filter(n => !n.read_at).length,
);

const readCount = computed(() =>
  props.notifications.filter(n => n.read_at).length,
);

const shownNotifications = computed(() => {
  if (activeFilter.value === 'unread') {
    return props.notifications.filter(n => !n.read_at);
  }
  return props.notifications;
});

const groupedNotifications = computed(() => {
  return groupNotificationsByTime(shownNotifications.value);
});

// Actions
const handleMarkAsRead = (id: string) => {
  router.post(route('notifications.markAsRead', id), {}, {
    preserveScroll: true,
    only: ['notifications'],
  });
};

const handleDelete = (id: string) => {
  router.delete(route('notifications.destroy', id), {
    preserveScroll: true,
    only: ['notifications'],
    onSuccess: () => {
      toasts.success($t('notifications.index.deleted'));
    },
  });
};

const handleMarkAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveScroll: true,
    only: ['notifications'],
    onSuccess: () => {
      toasts.success($t('notifications.index.all_marked_read'));
    },
  });
};

const handleDeleteRead = () => {
  router.delete(route('notifications.destroy-all'), {
    preserveScroll: true,
    only: ['notifications'],
    data: { read_only: true },
    onSuccess: () => {
      toasts.success($t('notifications.index.read_deleted'));
    },
  });
};

const handleDeleteAll = () => {
  router.delete(route('notifications.destroy-all'), {
    preserveScroll: true,
    only: ['notifications'],
    onSuccess: () => {
      toasts.success($t('notifications.index.all_deleted'));
    },
  });
};
</script>
