<template>
  <AdminContentPage :title="$t('Pranešimai')">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <!-- Filters -->
      <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-zinc-200 dark:border-zinc-800 p-1 bg-zinc-50 dark:bg-zinc-900/50">
          <button
            v-for="filter in filters"
            :key="filter.value"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200',
              activeFilter === filter.value
                ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm'
                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
            ]"
            @click="activeFilter = filter.value"
          >
            {{ filter.label }}
            <span
              v-if="filter.count > 0"
              :class="[
                'ml-1.5 px-1.5 py-0.5 text-xs rounded-full',
                activeFilter === filter.value
                  ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900'
                  : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300'
              ]"
            >
              {{ filter.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <DropdownMenu v-if="notifications.length > 0">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-1.5">
              <IFluentMoreHorizontal20Regular class="size-4" />
              {{ $t('Veiksmai') }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuItem
              v-if="unreadCount > 0"
              class="gap-2"
              @click="handleMarkAllAsRead"
            >
              <IFluentCheckmarkCircle24Regular class="size-4" />
              {{ $t('Pažymėti visus kaip skaitytus') }}
            </DropdownMenuItem>
            <DropdownMenuItem
              v-if="readCount > 0"
              class="gap-2 text-amber-600 dark:text-amber-400"
              @click="handleDeleteRead"
            >
              <IFluentDelete24Regular class="size-4" />
              {{ $t('Ištrinti perskaitytus') }}
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="unreadCount > 0 || readCount > 0" />
            <DropdownMenuItem
              class="gap-2 text-red-600 dark:text-red-400"
              @click="handleDeleteAll"
            >
              <IFluentDeleteDismiss24Regular class="size-4" />
              {{ $t('Ištrinti visus') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Notifications Content -->
    <div class="space-y-6">
      <!-- Empty State -->
      <div
        v-if="shownNotifications.length === 0"
        class="flex flex-col items-center justify-center py-16 px-4"
      >
        <div class="relative">
          <div class="flex items-center justify-center size-20 rounded-2xl bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900 mb-4">
            <IFluentAlertBadge24Regular class="size-10 text-zinc-400 dark:text-zinc-500" />
          </div>
          <div class="absolute -top-1 -right-1 size-6 rounded-full bg-emerald-500 flex items-center justify-center">
            <IFluentCheckmark24Filled class="size-4 text-white" />
          </div>
        </div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mt-2">
          {{ activeFilter === 'unread' ? $t('Naujų pranešimų nėra') : $t('Pranešimų nėra') }}
        </h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 text-center max-w-sm">
          {{ activeFilter === 'unread'
            ? $t('Visus pranešimus perskaitėte! Nauji pranešimai bus rodomi čia.')
            : $t('Kol kas pranešimų nėra. Jie bus rodomi čia, kai gausite naujų.')
          }}
        </p>
        <Button
          v-if="activeFilter === 'unread' && readCount > 0"
          variant="outline"
          size="sm"
          class="mt-4"
          @click="activeFilter = 'all'"
        >
          {{ $t('Rodyti visus pranešimus') }}
        </Button>
      </div>

      <!-- Grouped Notifications -->
      <template v-else>
        <div
          v-for="[period, periodNotifications] in groupedNotifications"
          :key="period"
          class="space-y-2"
        >
          <!-- Period Header -->
          <div class="flex items-center gap-3 px-1">
            <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
              {{ period }}
            </h3>
            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-800" />
            <span class="text-xs text-zinc-400 dark:text-zinc-500">
              {{ periodNotifications.length }}
            </span>
          </div>

          <!-- Notification Cards -->
          <TransitionGroup
            name="notification-card"
            tag="div"
            class="space-y-2"
          >
            <NotificationCard
              v-for="notification in periodNotifications"
              :key="notification.id"
              :notification
              @mark-as-read="handleMarkAsRead"
              @delete="handleDelete"
            />
          </TransitionGroup>
        </div>
      </template>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

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
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useToasts } from '@/Composables/useToasts';
import {
  groupNotificationsByTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import Icons from '@/Types/Icons/regular';
import IFluentCheckmarkCircle24Regular from '~icons/fluent/checkmark-circle-24-regular';
import IFluentCheckmark24Filled from '~icons/fluent/checkmark24-filled';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentDeleteDismiss24Regular from '~icons/fluent/delete-dismiss24-regular';
import IFluentMoreHorizontal20Regular from '~icons/fluent/more-horizontal20-regular';
import IFluentAlertBadge24Regular from '~icons/fluent/alert-badge24-regular';

const props = defineProps<{
  notifications: Notification[];
}>();

// Breadcrumbs
usePageBreadcrumbs([
  { label: $t('Pranešimai'), icon: Icons.NOTIFICATION },
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

const filters = computed(() => [
  { value: 'unread' as FilterValue, label: $t('Neskaityti'), count: unreadCount.value },
  { value: 'all' as FilterValue, label: $t('Visi'), count: props.notifications.length },
]);

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
      toasts.success($t('Pranešimas ištrintas.'));
    },
  });
};

const handleMarkAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveScroll: true,
    only: ['notifications'],
    onSuccess: () => {
      toasts.success($t('Visi pranešimai pažymėti kaip perskaityti.'));
    },
  });
};

const handleDeleteRead = () => {
  router.delete(route('notifications.destroy-all'), {
    preserveScroll: true,
    only: ['notifications'],
    data: { read_only: true },
    onSuccess: () => {
      toasts.success($t('Perskaityti pranešimai ištrinti.'));
    },
  });
};

const handleDeleteAll = () => {
  router.delete(route('notifications.destroy-all'), {
    preserveScroll: true,
    only: ['notifications'],
    onSuccess: () => {
      toasts.success($t('Visi pranešimai ištrinti.'));
    },
  });
};
</script>

<style scoped>
.notification-card-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.notification-card-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.notification-card-enter-active {
  transition: all 0.3s ease-out;
}

.notification-card-leave-active {
  transition: all 0.2s ease-in;
}

.notification-card-move {
  transition: transform 0.3s ease;
}
</style>
