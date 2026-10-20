<template>
  <div class="flex flex-col gap-3" data-slot="representative-activity">
    <RepresentativeActivitySkeleton v-if="loading" />

    <template v-else>
      <dl class="grid grid-cols-2 gap-x-6 gap-y-4" data-slot="representative-activity-numbers">
        <div v-for="row in breakdown" :key="row.key" :data-number="row.key" class="flex min-h-11 flex-col gap-1">
          <dd :class="['text-3xl font-semibold tabular-nums leading-none', row.danger && row.value > 0 ? 'text-status-danger' : 'text-foreground']">
            {{ row.key === 'month' ? `${row.value} / ${stats.total}` : row.value }}
          </dd>
          <dt class="text-sm text-muted-foreground">
            {{ row.label }}
          </dt>
        </div>
      </dl>

      <ul v-if="recentlyActive.length > 0" class="divide-y divide-border/60">
        <li v-for="user in recentlyActive" :key="user.id" class="flex items-center gap-3 py-2">
          <UserAvatar :user="{ name: user.name, profile_photo_path: user.profile_photo_path ?? null }" :size="28" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium">{{ user.name }}</span>
            <span v-if="user.duties[0]?.institution_name" class="block truncate text-xs text-muted-foreground">
              {{ user.duties[0].institution_name }}
            </span>
          </span>
          <span class="shrink-0 text-xs text-muted-foreground">
            {{ formatNearDate(user.last_action) }}
          </span>
        </li>
      </ul>

      <Button variant="outline" size="sm" class="self-start pointer-coarse:h-11" @click="openTable('active')">
        <Users aria-hidden="true" />
        {{ $t('Visi atstovai') }}
      </Button>
    </template>

    <RepresentativeDataTable
      :tenant-ids
      :stats
      :is-open="showDataTable"
      :initial-tab="dataTableInitialTab"
      @update:is-open="showDataTable = $event"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Users } from '@lucide/vue';
import { computed, ref } from 'vue';

import type { RepresentativeActivityStats, RepresentativeUser } from '../types';

import RepresentativeDataTable from './RepresentativeDataTable.vue';
import RepresentativeActivitySkeleton from './RepresentativeActivitySkeleton.vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';

const props = defineProps<{
  stats: RepresentativeActivityStats;
  users: RepresentativeUser[];
  tenantIds: string[];
  loading?: boolean;
}>();

const { formatNearDate } = useDateFormatter();

const PREVIEW_COUNT = 3;

const breakdown = computed(() => [
  { key: 'month', label: $t('metrics.activity.month'), value: props.stats.activeLast30Days, danger: false },
  { key: 'today', label: $t('metrics.activity.today'), value: props.stats.activeToday, danger: false },
  { key: 'week', label: $t('metrics.activity.week'), value: props.stats.activeLast7Days, danger: false },
  { key: 'never', label: $t('metrics.activity.never'), value: props.stats.neverLoggedIn, danger: true },
]);

const recentlyActive = computed(() =>
  props.users
    .filter(user => user.last_action && ['today', 'week', 'month'].includes(user.category))
    .sort((a, b) => new Date(b.last_action!).getTime() - new Date(a.last_action!).getTime())
    .slice(0, PREVIEW_COUNT),
);

const showDataTable = ref(false);
const dataTableInitialTab = ref<'active' | 'inactive'>('active');

function openTable(tab: 'active' | 'inactive'): void {
  dataTableInitialTab.value = tab;
  showDataTable.value = true;
}
</script>
