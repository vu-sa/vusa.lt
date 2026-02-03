<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Atstovų aktyvumas')">
    <!-- Status indicator - corner accent based on activity health -->
    <div :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2">
        <Users :class="iconClasses" aria-hidden="true" />
        {{ $t('Atstovų aktyvumas') }}
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 flex flex-col justify-center">
      <!-- Loading state -->
      <div v-if="loading" class="space-y-4 py-4">
        <Skeleton class="h-12 w-24" />
        <div class="flex gap-2">
          <Skeleton class="h-6 w-20" />
          <Skeleton class="h-6 w-20" />
          <Skeleton class="h-6 w-20" />
        </div>
      </div>

      <!-- Stats display -->
      <template v-else>
        <!-- Main metrics section -->
        <div class="flex items-end gap-4 mb-6">
          <div class="flex items-baseline gap-1">
            <span :class="[
              'text-4xl font-bold',
              activityRatio >= 0.7 ? 'text-emerald-600 dark:text-emerald-400' :
              activityRatio >= 0.4 ? 'text-amber-600 dark:text-amber-400' :
              'text-zinc-700 dark:text-zinc-300'
            ]" :aria-label="$t('Aktyvūs atstovai') + ': ' + stats.activeLast30Days">
              {{ stats.activeLast30Days }}
            </span>
            <span class="text-lg text-zinc-500 dark:text-zinc-400">/{{ stats.total }}</span>
          </div>
        </div>

        <!-- Activity breakdown badges -->
        <div class="flex flex-wrap gap-2 mb-6">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <div
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                >
                  <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                  {{ stats.activeToday }} {{ $t('šiandien') }}
                </div>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Prisijungė šiandien') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                  <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                  {{ stats.activeLast7Days }} {{ $t('per 7 d.') }}
                </div>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Prisijungė per pastarąsias 7 dienas') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                  <div class="h-1.5 w-1.5 rounded-full bg-amber-500 dark:bg-amber-400" />
                  {{ stats.activeLast30Days }} {{ $t('per 30 d.') }}
                </div>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Prisijungė per pastarąsias 30 dienų') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <TooltipProvider v-if="stats.neverLoggedIn > 0">
            <Tooltip>
              <TooltipTrigger as-child>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                  <AlertCircle class="h-3 w-3" />
                  {{ stats.neverLoggedIn }} {{ $t('niekada') }}
                </div>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Niekada neprisijungė prie sistemos') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Visual progress indicator -->
        <div class="space-y-2">
          <div class="flex justify-between text-xs text-zinc-600 dark:text-zinc-400">
            <span>{{ $t('30 dienų aktyvumas') }}</span>
            <span>{{ Math.round(activityRatio * 100) }}%</span>
          </div>
          <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="progressBarClasses"
              :style="{ width: `${activityRatio * 100}%` }"
            />
          </div>
        </div>
      </template>
    </CardContent>

    <CardFooter :class="[dashboardCardFooterClasses, 'p-4 relative z-10']">
      <!-- Insight message based on activity health -->
      <div class="text-xs text-center w-full">
        <template v-if="stats.neverLoggedIn > 0">
          <div class="font-medium text-red-600 dark:text-red-400">
            {{ $t('Reikia dėmesio') }}
          </div>
          <div class="text-zinc-600 dark:text-zinc-400">
            {{ stats.neverLoggedIn }} {{ stats.neverLoggedIn === 1 ? $t('atstovas niekada neprisijungė') : $t('atstovai niekada neprisijungė') }}
          </div>
        </template>
        <template v-else-if="inactiveCount > 0">
          <div class="font-medium text-amber-600 dark:text-amber-400">
            {{ $t('Galima pagerinti') }}
          </div>
          <div class="text-zinc-600 dark:text-zinc-400">
            {{ inactiveCount }} {{ inactiveCount === 1 ? $t('atstovas neaktyvus') : $t('atstovai neaktyvūs') }} {{ $t('daugiau nei 30 dienų') }}
          </div>
        </template>
        <template v-else>
          <div class="font-medium text-emerald-600 dark:text-emerald-400">
            {{ $t('Puikus aktyvumas') }}
          </div>
          <div class="text-zinc-600 dark:text-zinc-400">
            {{ $t('Visi atstovai aktyvūs per pastarąsias 30 dienų') }}
          </div>
        </template>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Users, AlertCircle } from 'lucide-vue-next';

import type { RepresentativeActivityStats } from '../types';

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Skeleton } from '@/Components/ui/skeleton';
import { dashboardCardClasses, dashboardCardFooterClasses } from '@/Composables/useDashboardCardStyles';

interface Props {
  stats: RepresentativeActivityStats;
  loading?: boolean;
}

const props = defineProps<Props>();

// Activity ratio (30 day active / total)
const activityRatio = computed(() => {
  if (props.stats.total === 0) return 0;
  return props.stats.activeLast30Days / props.stats.total;
});

// Inactive users (total - 30 day active)
const inactiveCount = computed(() => {
  return props.stats.total - props.stats.activeLast30Days;
});

// Health status based on activity
const healthStatus = computed(() => {
  if (props.stats.neverLoggedIn > 0) return 'critical';
  if (activityRatio.value >= 0.7) return 'healthy';
  if (activityRatio.value >= 0.4) return 'moderate';
  return 'low';
});

const healthLabel = computed(() => {
  switch (healthStatus.value) {
    case 'healthy': return $t('Puiku');
    case 'moderate': return $t('Vidutinis');
    case 'low': return $t('Žemas');
    case 'critical': return $t('Reikia dėmesio');
    default: return '';
  }
});

const healthBadgeClasses = computed(() => {
  switch (healthStatus.value) {
    case 'healthy': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    case 'moderate': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    case 'low': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    case 'critical': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    default: return 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300';
  }
});

const statusIndicatorClasses = computed(() => {
  const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45';
  return `${base} bg-zinc-300/50 dark:bg-zinc-600/30`;
});

const iconClasses = computed(() => {
  switch (healthStatus.value) {
    case 'healthy': return 'h-5 w-5 text-emerald-600 dark:text-emerald-400/80';
    case 'moderate': return 'h-5 w-5 text-amber-600 dark:text-amber-400/80';
    case 'low': return 'h-5 w-5 text-orange-600 dark:text-orange-400/80';
    case 'critical': return 'h-5 w-5 text-red-600 dark:text-red-400/80';
    default: return 'h-5 w-5 text-zinc-600 dark:text-zinc-400';
  }
});

const progressBarClasses = computed(() => {
  switch (healthStatus.value) {
    case 'healthy': return 'bg-emerald-500 dark:bg-emerald-400';
    case 'moderate': return 'bg-amber-500 dark:bg-amber-400';
    case 'low': return 'bg-orange-500 dark:bg-orange-400';
    case 'critical': return 'bg-red-500 dark:bg-red-400';
    default: return 'bg-zinc-500 dark:bg-zinc-400';
  }
});
</script>
