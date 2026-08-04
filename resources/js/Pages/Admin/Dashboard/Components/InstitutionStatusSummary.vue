<template>
  <div>
    <Card data-tour="institution-status-summary">
      <CardHeader class="gap-2">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="space-y-1">
            <CardTitle class="flex items-center gap-2">
              <Landmark class="h-5 w-5 text-primary" />
              {{ $t('visak.institution_summary.title') }}
            </CardTitle>
            <CardDescription>
              {{ $t('visak.institution_summary.description') }}
            </CardDescription>
          </div>
          <Loader2 v-if="loading" class="h-5 w-5 animate-spin text-muted-foreground" />
        </div>
      </CardHeader>

      <CardContent>
        <Tabs v-model="innerTab">
          <TabsList class="gap-2">
            <TabsTrigger value="overview">
              {{ $t('visak.institution_summary.overview_tab') }}
            </TabsTrigger>
            <SpotlightPopover
              :title="$t('visak.institution_summary.trend_spotlight_title')"
              :description="$t('visak.institution_summary.trend_spotlight_description')"
              :is-dismissed="trendSpotlight.isDismissed.value"
              position="bottom"
              @dismiss="trendSpotlight.dismiss"
            >
              <TabsTrigger value="trend" data-testid="institution-summary-trend-tab">
                {{ $t('visak.institution_summary.trend_tab') }}
              </TabsTrigger>
            </SpotlightPopover>
          </TabsList>

          <TabsContent value="overview" class="mt-4">
            <div v-if="loading && summary.all === 0">
              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <Skeleton v-for="index in 6" :key="index" class="h-20 rounded-lg" />
              </div>
            </div>

            <div v-else class="space-y-4 transition-opacity" :class="{ 'opacity-60': loading }">
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div
                  v-for="counter in counters"
                  :key="counter.key"
                  class="rounded-lg border bg-card p-3"
                  :class="counter.className"
                >
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-muted-foreground">{{ counter.label }}</span>
                    <component :is="counter.icon" class="h-4 w-4" />
                  </div>
                  <div class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ counter.value }}
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <Button
                  variant="outline"
                  size="sm"
                  class="gap-2"
                  :disabled="institutions.length === 0"
                  data-testid="institution-summary-dialog-trigger"
                  @click="dialogOpen = true"
                >
                  <TableProperties class="h-4 w-4" />
                  {{ $t('visak.institution_summary.view_institutions') }}
                </Button>
              </div>
            </div>
          </TabsContent>

          <TabsContent value="trend" class="mt-4">
            <InstitutionStatusTrendChart
              :data="statusHistory.data.value ?? []"
              :days="historyDays"
              :loading="statusHistory.isFetching.value"
              @update:days="historyDays = $event"
            />
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen">
      <DialogContent class="max-h-[90vh] w-full max-w-[95vw] overflow-y-auto p-4 sm:max-w-[90vw] sm:p-6">
        <DialogHeader>
          <DialogTitle>{{ $t('visak.institution_summary.dialog_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('visak.institution_summary.dialog_description') }}
          </DialogDescription>
        </DialogHeader>

        <SimpleDataTable
          v-if="dialogOpen"
          :data="sortedInstitutions"
          :columns
          :page-size="10"
          enable-pagination
          enable-filtering
          :enable-column-visibility="false"
          :empty-message="$t('visak.institution_summary.empty')"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="tsx">
import { computed, ref, watch, type Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import {
  AlertTriangle,
  CalendarCheck,
  CalendarClock,
  CalendarX,
  CheckCircle2,
  CircleGauge,
  Clock,
  Landmark,
  Loader2,
  TableProperties,
} from 'lucide-vue-next';

import type { AtstovavimasInstitution, InstitutionStatusSummaryData } from '../types';
import { useTenantStatusHistory } from '../Composables/useTenantStatusHistory';

import InstitutionStatusTrendChart from './InstitutionStatusTrendChart.vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Skeleton } from '@/Components/ui/skeleton';
import Tabs from '@/Components/ui/tabs/Tabs.vue';
import TabsContent from '@/Components/ui/tabs/TabsContent.vue';
import TabsList from '@/Components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/Components/ui/tabs/TabsTrigger.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { InstitutionActivityStatusName } from '@/Types/InstitutionActivity';
import { formatStaticTime } from '@/Utils/IntlTime';

const props = withDefaults(defineProps<{
  institutions: AtstovavimasInstitution[];
  summary: InstitutionStatusSummaryData;
  tenantIds?: string[];
  loading?: boolean;
}>(), {
  tenantIds: () => [],
});
const dialogOpen = ref(false);

const innerTab = ref<'overview' | 'trend'>('overview');
const trendSpotlight = useFeatureSpotlight('institution-status-trend-v1');
const historyDays = ref(90);
const statusHistory = useTenantStatusHistory();

// Lazy: only fetched once the Trend tab is first opened, then kept in sync with
// the selected tenants / range from there on.
watch(
  () => [innerTab.value, props.tenantIds.join(','), historyDays.value] as const,
  ([tab]) => {
    if (tab === 'trend' && props.tenantIds.length > 0) {
      statusHistory.load(props.tenantIds, historyDays.value);
      trendSpotlight.dismiss();
    }
  },
);

interface Counter {
  key: string;
  label: string;
  value: number;
  icon: Component;
  className: string;
}

const statusIcons: Record<InstitutionActivityStatusName, Component> = {
  no_activity: CalendarX,
  healthy: CheckCircle2,
  approaching: Clock,
  overdue: AlertTriangle,
  covered_by_upcoming_meeting: CalendarClock,
  covered_by_check_in: CalendarCheck,
};

const statusClasses: Record<InstitutionActivityStatusName, string> = {
  no_activity: 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
  healthy: 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-900/40 dark:text-emerald-300',
  approaching: 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-700/50 dark:bg-amber-900/40 dark:text-amber-300',
  overdue: 'border-orange-200 bg-orange-100 text-orange-700 dark:border-orange-700/50 dark:bg-orange-900/40 dark:text-orange-300',
  covered_by_upcoming_meeting: 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-700/50 dark:bg-blue-900/40 dark:text-blue-300',
  covered_by_check_in: 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-900/40 dark:text-emerald-300',
};

const sortedInstitutions = computed(() => {
  return [...props.institutions].sort((left, right) => {
    const priorityDifference = right.activity_status.priority - left.activity_status.priority;

    if (priorityDifference !== 0) {
      return priorityDifference;
    }

    return left.name.localeCompare(right.name);
  });
});

const counters = computed<Counter[]>(() => [
  {
    key: 'all',
    label: $t('visak.institution_summary.all'),
    value: props.summary.all,
    icon: Landmark,
    className: '',
  },
  {
    key: 'attention',
    label: $t('visak.institution_summary.needs_attention'),
    value: props.summary.needs_attention,
    icon: CircleGauge,
    className: 'border-rose-200/80 bg-rose-50/60 dark:border-rose-900/60 dark:bg-rose-950/20',
  },
  {
    key: 'overdue',
    label: $t('visak.institution_summary.overdue'),
    value: props.summary.overdue,
    icon: AlertTriangle,
    className: 'border-orange-200/80 bg-orange-50/60 dark:border-orange-900/60 dark:bg-orange-950/20',
  },
  {
    key: 'approaching',
    label: $t('visak.institution_summary.approaching'),
    value: props.summary.approaching,
    icon: Clock,
    className: 'border-amber-200/80 bg-amber-50/60 dark:border-amber-900/60 dark:bg-amber-950/20',
  },
  {
    key: 'no_activity',
    label: $t('visak.institution_summary.no_activity'),
    value: props.summary.no_activity,
    icon: CalendarX,
    className: 'border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-900/30',
  },
  {
    key: 'current',
    label: $t('visak.institution_summary.current'),
    value: props.summary.current,
    icon: CheckCircle2,
    className: 'border-emerald-200/80 bg-emerald-50/60 dark:border-emerald-900/60 dark:bg-emerald-950/20',
  },
]);

const columns = computed<ColumnDef<AtstovavimasInstitution>[]>(() => [
  {
    accessorKey: 'name',
    header: () => $t('Institucija'),
    cell: ({ row }) => (
      <Link
        href={route('institutions.show', row.original.id)}
        class="font-medium text-primary hover:underline"
      >
        {row.original.name}
      </Link>
    ),
  },
  {
    id: 'tenant',
    accessorFn: institution => institution.tenant?.shortname ?? '',
    header: () => $t('Padalinys'),
    cell: ({ row }) => row.original.tenant?.shortname ?? '—',
  },
  {
    id: 'status',
    accessorFn: institution => institution.activity_status.status,
    header: () => $t('visak.institution_summary.status'),
    cell: ({ row }) => {
      const { status } = row.original.activity_status;
      const StatusIcon = statusIcons[status];

      return (
        <Badge variant="outline" class={['gap-1.5 whitespace-normal', statusClasses[status]]}>
          <StatusIcon class="h-3.5 w-3.5 shrink-0" />
          {$t(`visak.activity.activity_status.${status}`)}
        </Badge>
      );
    },
  },
  {
    id: 'progress',
    accessorFn: institution => institution.activity_status.progress_percentage ?? -1,
    header: () => $t('visak.institution_summary.periodicity'),
    cell: ({ row }) => {
      const status = row.original.activity_status;

      if (status.effective_days_since_activity === null) {
        return <span class="text-muted-foreground">—</span>;
      }

      const progress = Math.min(Math.max(status.progress_percentage ?? 0, 0), 100);

      return (
        <div class="min-w-28 space-y-1.5">
          <div class="text-sm tabular-nums">
            {`${status.effective_days_since_activity} / ${status.periodicity_days} ${$t('d.')}`}
          </div>
          <div class="h-1.5 overflow-hidden rounded-full bg-muted">
            <div
              class={[
                'h-full rounded-full',
                status.status === 'overdue'
                  ? 'bg-orange-500'
                  : status.status === 'approaching'
                    ? 'bg-amber-500'
                    : 'bg-emerald-500',
              ]}
              style={{ width: `${progress}%` }}
            />
          </div>
        </div>
      );
    },
  },
  {
    id: 'activity_reference',
    accessorFn: institution => activityReferenceDate(institution),
    header: () => $t('visak.institution_summary.activity_reference'),
    cell: ({ row }) => activityReference(row.original),
  },
]);

function activityReferenceDate(institution: AtstovavimasInstitution): string {
  const status = institution.activity_status;
  return status.next_meeting_at ?? status.active_check_in_until ?? status.last_activity_at ?? '';
}

function activityReference(institution: AtstovavimasInstitution): string {
  const status = institution.activity_status;

  if (status.status === 'covered_by_upcoming_meeting' && status.next_meeting_at) {
    return $t('visak.institution_summary.next_meeting', { date: formatDate(status.next_meeting_at) });
  }

  if (status.status === 'covered_by_check_in' && status.active_check_in_until) {
    return $t('visak.activity.activity_status.reported_until', {
      date: formatDate(status.active_check_in_until),
    });
  }

  if (status.last_activity_at) {
    if (status.last_activity_type === 'check_in') {
      return $t('visak.activity.activity_status.reported_until', {
        date: formatDate(status.last_activity_at),
      });
    }

    return $t('visak.institution_summary.last_meeting', { date: formatDate(status.last_activity_at) });
  }

  return '—';
}

function formatDate(value: string): string {
  return formatStaticTime(new Date(value), {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}
</script>
