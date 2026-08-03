<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <div :class="['flex h-6 w-6 shrink-0 items-center justify-center rounded-full', eventStyle.badgeClass]">
          <component :is="eventStyle.icon" class="h-3.5 w-3.5" :class="eventStyle.iconClass" />
        </div>
        <span class="text-sm font-medium">{{ $t(`activity.event.${entry.event}`) }}</span>
        <Badge v-if="!entry.subject.is_root" variant="outline" size="tiny">
          {{ entry.subject.label }}
        </Badge>
      </div>
      <div class="shrink-0">
        <UserPopover v-if="entry.causer" :size="18" show-name :user="entry.causer" />
        <span v-else class="text-xs text-zinc-400 dark:text-zinc-500">{{ $t('activity.system') }}</span>
      </div>
    </div>

    <div v-if="entry.event === 'updated' && entry.changes.length > 0" class="ml-8 space-y-1.5">
      <ActivityChangeRow v-for="change in entry.changes" :key="change.key" :change />
    </div>

    <div v-if="entry.event === 'relation_updated' && entry.relation_change" class="ml-8">
      <ActivityRelationChange :change="entry.relation_change" />
    </div>

    <p :title="entry.created_at ?? undefined" class="ml-8 text-xs text-zinc-400 dark:text-zinc-500">
      {{ relativeTime }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpDown, History, Pencil, Plus, Trash2, Users } from 'lucide-vue-next';

import ActivityChangeRow from './ActivityChangeRow.vue';
import ActivityRelationChange from './ActivityRelationChange.vue';

import UserPopover from '@/Components/Avatars/UserPopover.vue';
import { Badge } from '@/Components/ui/badge';
import { formatRelativeTime } from '@/Utils/IntlTime';
import type { ActivityEntry, ActivityEvent } from '@/Types/activityLog';

const props = defineProps<{
  entry: ActivityEntry;
}>();

const EVENT_STYLES: Record<ActivityEvent, { icon: typeof Plus; badgeClass: string; iconClass: string }> = {
  created: {
    icon: Plus,
    badgeClass: 'bg-green-100 dark:bg-green-900/30',
    iconClass: 'text-green-600 dark:text-green-400',
  },
  updated: {
    icon: Pencil,
    badgeClass: 'bg-blue-100 dark:bg-blue-900/30',
    iconClass: 'text-blue-600 dark:text-blue-400',
  },
  deleted: {
    icon: Trash2,
    badgeClass: 'bg-red-100 dark:bg-red-900/30',
    iconClass: 'text-red-600 dark:text-red-400',
  },
  restored: {
    icon: History,
    badgeClass: 'bg-amber-100 dark:bg-amber-900/30',
    iconClass: 'text-amber-600 dark:text-amber-400',
  },
  relation_updated: {
    icon: Users,
    badgeClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600 dark:text-purple-400',
  },
  content_reordered: {
    icon: ArrowUpDown,
    badgeClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600 dark:text-purple-400',
  },
};

const eventStyle = computed(() => EVENT_STYLES[props.entry.event] ?? EVENT_STYLES.updated);

const relativeTime = computed(() => props.entry.created_at ? formatRelativeTime(props.entry.created_at) : '');
</script>
