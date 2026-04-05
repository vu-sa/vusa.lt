<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Atstovų sąrašas')">
    <!-- Status indicator -->
    <div :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2.5">
        <UserCheck :class="iconClasses" aria-hidden="true" />
        <span class="font-semibold">{{ $t('Atstovų sąrašas') }}</span>
        <span class="text-xs px-2 py-1 rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 ml-auto font-medium">
          {{ displayedUsers.length }}/{{ users.length }}
        </span>
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 space-y-4 pt-2">
      <!-- Loading state -->
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 4" :key="i" class="flex items-center gap-3">
          <Skeleton class="h-8 w-8 rounded-full" />
          <div class="flex-1 space-y-1">
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-3 w-24" />
          </div>
        </div>
      </div>

      <!-- Tab switcher -->
      <template v-else>
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="grid w-full grid-cols-2 h-8">
            <TabsTrigger value="active" class="text-xs gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400" />
              {{ $t('Aktyvūs') }}
              <span class="text-zinc-500">({{ activeUsers.length }})</span>
            </TabsTrigger>
            <TabsTrigger value="inactive" class="text-xs gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-red-400" />
              {{ $t('Neaktyvūs') }}
              <span class="text-zinc-500">({{ inactiveUsers.length }})</span>
            </TabsTrigger>
          </TabsList>

          <TabsContent value="active" class="mt-3 space-y-2">
            <template v-if="activeUsers.length > 0">
              <RepresentativeUserRow
                v-for="user in activeUsers.slice(0, maxDisplayCount)"
                :key="user.id"
                :user
              />
              <div v-if="activeUsers.length > maxDisplayCount" class="text-center pt-2">
                <button
                  type="button"
                  class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline transition-colors"
                  @click="$emit('show-all', 'active')"
                >
                  {{ $t('ir dar') }} {{ activeUsers.length - maxDisplayCount }}...
                </button>
              </div>
            </template>
            <div v-else class="text-center py-6 text-zinc-500 dark:text-zinc-400">
              <div class="text-3xl mb-2">
                😔
              </div>
              <p class="text-sm">
                {{ $t('Nėra aktyvių atstovų') }}
              </p>
            </div>
          </TabsContent>

          <TabsContent value="inactive" class="mt-3 space-y-2">
            <template v-if="inactiveUsers.length > 0">
              <RepresentativeUserRow
                v-for="user in inactiveUsers.slice(0, maxDisplayCount)"
                :key="user.id"
                :user
              />
              <div v-if="inactiveUsers.length > maxDisplayCount" class="text-center pt-2">
                <button
                  type="button"
                  class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:underline transition-colors"
                  @click="$emit('show-all', 'inactive')"
                >
                  {{ $t('ir dar') }} {{ inactiveUsers.length - maxDisplayCount }}...
                </button>
              </div>
            </template>
            <div v-else class="text-center py-6 text-emerald-600 dark:text-emerald-400">
              <div class="text-3xl mb-2">
                🎉
              </div>
              <p class="text-sm font-medium">
                {{ $t('Visi atstovai aktyvūs!') }}
              </p>
            </div>
          </TabsContent>
        </Tabs>
      </template>
    </CardContent>

    <CardFooter :class="[dashboardCardFooterClasses, 'p-4 relative z-10']">
      <Button size="sm" variant="outline" class="w-full font-medium" @click="$emit('show-all')">
        <Users class="h-3.5 w-3.5 mr-2" />
        {{ $t('Visi atstovai') }}
      </Button>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { UserCheck, Users } from 'lucide-vue-next';

import type { RepresentativeUser } from '../types';

import RepresentativeUserRow from './RepresentativeUserRow.vue';

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Skeleton } from '@/Components/ui/skeleton';
import { dashboardCardClasses, dashboardCardFooterClasses } from '@/Composables/useDashboardCardStyles';

interface Props {
  users: RepresentativeUser[];
  loading?: boolean;
  maxDisplayCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
  maxDisplayCount: 4,
});

defineEmits<{
  'show-all': [tab?: 'active' | 'inactive'];
}>();

const activeTab = ref<'active' | 'inactive'>('inactive');

// Split users by activity status
// Active = logged in within 30 days
// Inactive = never logged in OR logged in more than 30 days ago
const activeUsers = computed(() => {
  return props.users.filter(u =>
    u.category === 'today' || u.category === 'week' || u.category === 'month',
  );
});

const inactiveUsers = computed(() => {
  // Never logged in first, then stale (oldest first)
  return props.users.filter(u =>
    u.category === 'never' || u.category === 'stale',
  );
});

const displayedUsers = computed(() => {
  return activeTab.value === 'active' ? activeUsers.value : inactiveUsers.value;
});

// Determine card styling based on inactive count
const hasInactiveUsers = computed(() => inactiveUsers.value.length > 0);
const hasNeverLoggedIn = computed(() => props.users.some(u => u.category === 'never'));

const statusIndicatorClasses = computed(() => {
  const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45';
  if (hasNeverLoggedIn.value) return `${base} bg-red-400/60 dark:bg-red-700/35`;
  if (hasInactiveUsers.value) return `${base} bg-amber-400/60 dark:bg-amber-700/35`;
  return `${base} bg-emerald-400/60 dark:bg-emerald-700/35`;
});

const iconClasses = computed(() => {
  if (hasNeverLoggedIn.value) return 'h-5 w-5 text-red-600 dark:text-red-400/80';
  if (hasInactiveUsers.value) return 'h-5 w-5 text-amber-600 dark:text-amber-400/80';
  return 'h-5 w-5 text-emerald-600 dark:text-emerald-400/80';
});
</script>
