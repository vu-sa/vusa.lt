<template>
  <!-- Sits in the padalinys overview's aside tabs, which carry its title. -->
  <div class="flex flex-col gap-3" data-slot="representative-activity">
    <div v-if="loading" class="flex flex-col gap-3">
      <Skeleton class="h-8 w-32" />
      <Skeleton v-for="row in 4" :key="row" class="h-5 w-full" />
    </div>

    <template v-else>
      <p class="flex items-baseline gap-2">
        <span class="text-3xl font-semibold tabular-nums text-foreground">{{ stats.activeLast30Days }}</span>
        <span class="text-sm text-muted-foreground">/ {{ stats.total }} · {{ $t('Prisijungė per pastarąsias 30 dienų') }}</span>
      </p>

      <dl class="divide-y divide-border/60 border-y border-border/60 text-sm">
        <div v-for="row in breakdown" :key="row.key" class="flex items-center justify-between gap-4 py-2">
          <dt class="text-muted-foreground">
            {{ row.label }}
          </dt>
          <dd :class="['font-semibold tabular-nums', row.danger && row.value > 0 ? 'text-status-danger' : 'text-foreground']">
            {{ row.value }}
          </dd>
        </div>
      </dl>

      <!-- The people to nudge first; the dialog holds everyone else. -->
      <ul v-if="leastActive.length > 0" class="divide-y divide-border/60">
        <li v-for="user in leastActive" :key="user.id" class="flex items-center gap-3 py-2">
          <UserAvatar :user="{ name: user.name, profile_photo_path: user.profile_photo_path ?? null }" :size="28" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium">{{ user.name }}</span>
            <span v-if="user.duties[0]?.institution_name" class="block truncate text-xs text-muted-foreground">
              {{ user.duties[0].institution_name }}
            </span>
          </span>
          <span :class="['shrink-0 text-xs', user.category === 'never' ? 'text-status-danger' : 'text-muted-foreground']">
            {{ user.category === 'never' || !user.last_action ? $t('Niekada') : formatRelativeTime(new Date(user.last_action)) }}
          </span>
        </li>
      </ul>

      <Button variant="outline" size="sm" class="self-start pointer-coarse:h-11" @click="openTable('inactive')">
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
import { Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { RepresentativeActivityStats, RepresentativeUser } from '../types';

import RepresentativeDataTable from './RepresentativeDataTable.vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatRelativeTime } from '@/Utils/IntlTime';

const props = defineProps<{
  stats: RepresentativeActivityStats;
  users: RepresentativeUser[];
  tenantIds: string[];
  loading?: boolean;
}>();

const PREVIEW_COUNT = 5;

const CATEGORY_ORDER: Record<RepresentativeUser['category'], number> = {
  never: 0,
  stale: 1,
  month: 2,
  week: 3,
  today: 4,
};

const breakdown = computed(() => [
  { key: 'today', label: $t('Prisijungė šiandien'), value: props.stats.activeToday, danger: false },
  { key: 'week', label: $t('Prisijungė per pastarąsias 7 dienas'), value: props.stats.activeLast7Days, danger: false },
  { key: 'never', label: $t('Niekada neprisijungė prie sistemos'), value: props.stats.neverLoggedIn, danger: true },
]);

const leastActive = computed(() =>
  props.users
    .filter(user => user.category === 'never' || user.category === 'stale')
    .sort((a, b) => CATEGORY_ORDER[a.category] - CATEGORY_ORDER[b.category])
    .slice(0, PREVIEW_COUNT),
);

const showDataTable = ref(false);
const dataTableInitialTab = ref<'active' | 'inactive'>('inactive');

function openTable(tab: 'active' | 'inactive'): void {
  dataTableInitialTab.value = tab;
  showDataTable.value = true;
}
</script>
