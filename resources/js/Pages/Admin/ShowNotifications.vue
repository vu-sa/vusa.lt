<template>
  <CollectionPage
    :source
    collection="notifications"
    entity-type="notification"
    :eyebrow
    :title="$t('Pranešimai')"
    :lead="$t('Peržiūrėk naujus ir ankstesnius pranešimus.')"
    default-view="rows"
    :available-views="['rows']"
    :item-key="item => item.id"
    :quick-filters
    @quick-filter="toggleUnread"
  >
    <template #actions>
      <Button as-child variant="outline" size="lg" voice="sentence">
        <Link :href="route('profile.notifications')">
          <Settings aria-hidden="true" />
          {{ $t('shell.account.notifications') }}
        </Link>
      </Button>

      <DropdownMenu v-if="notifications.length > 0">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="lg" voice="sentence">
            <MoreHorizontal aria-hidden="true" />
            {{ $t('notifications.index.actions') }}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem v-if="unreadCount > 0" @click="handleMarkAllAsRead">
            {{ $t('notifications.index.mark_all_read') }}
          </DropdownMenuItem>
          <DropdownMenuItem v-if="readCount > 0" @click="handleDeleteRead">
            {{ $t('notifications.index.delete_read') }}
          </DropdownMenuItem>
          <DropdownMenuItem class="text-destructive" @click="handleDeleteAll">
            {{ $t('notifications.index.delete_all') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>
    <template #row="{ item }">
      <NotificationCard :notification="item" @mark-as-read="handleMarkAsRead" @delete="handleDelete" />
    </template>
    <template #empty>
      <EmptyState
        :icon="BellOff"
        :title="unreadOnly ? $t('notifications.index.empty_unread_title') : $t('notifications.index.empty_all_title')"
        :description="unreadOnly ? $t('notifications.index.empty_unread_body') : $t('notifications.index.empty_all_body')"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { computed, toRef } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BellOff, MoreHorizontal, Settings } from 'lucide-vue-next';

import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import NotificationCard from '@/Features/Admin/Notifications/NotificationCard.vue';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { EmptyState } from '@/Components/Patterns';
import { useToasts } from '@/Composables/useToasts';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import type { Notification } from '@/Composables/useNotificationFormatting';

const props = defineProps<{ notifications: Notification[] }>();

const eyebrow = computed(() => `${$t('shell.workspaces.pradzia.title')} · ${$t('Pranešimai')}`);

const toasts = useToasts();
const source = useLocalCollectionSource<Notification>({
  items: toRef(props, 'notifications'),
  searchText: item => [item.data.title, item.data.body, item.data.message],
  defaultSort: 'created_at:desc',
  sortOptions: [{ value: 'created_at:desc', label: $t('Naujausi'), by: item => item.created_at }],
  facets: [{
    field: 'read',
    label: $t('Būsena'),
    get: item => item.read_at ? 'read' : 'unread',
    valueLabel: value => value === 'unread' ? $t('notifications.index.filter_unread') : $t('Visi'),
  }],
});

if (!new URLSearchParams(window.location.search).has('read')) {
  source.setFilter('read', ['unread']);
}

const unreadCount = computed(() => props.notifications.filter(item => !item.read_at).length);
const readCount = computed(() => props.notifications.filter(item => item.read_at).length);
const unreadOnly = computed(() => source.filters.value.read === 'unread' || (Array.isArray(source.filters.value.read) && source.filters.value.read.includes('unread')));

const quickFilters = computed(() => [
  { id: 'unread', label: `${$t('notifications.index.filter_unread')} · ${unreadCount.value}`, active: unreadOnly.value },
  { id: 'all', label: $t('Visi'), active: !unreadOnly.value },
]);

function toggleUnread(id: string): void {
  source.setFilter('read', id === 'unread' ? ['unread'] : undefined);
}

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
