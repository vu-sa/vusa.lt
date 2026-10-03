<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.svetaine.title')"
    :title="$t('svetaine.overview.title')"
    :head-title="`${$t('shell.workspaces.svetaine.title')} · ${$t('svetaine.overview.title')}`"
    :lead="$t('svetaine.overview.lead')"
  >
    <template v-if="tenants.length > 0" #actions>
      <Select :model-value="selectedTenantId" @update:model-value="handleTenantUpdateValue">
        <SelectTrigger class="w-48 pointer-coarse:h-11" :aria-label="$t('svetaine.overview.tenant')">
          <SelectValue :placeholder="$t('svetaine.overview.tenant')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
            {{ tenant.shortname }}
          </SelectItem>
        </SelectContent>
      </Select>
    </template>

    <OverviewNumbers v-if="numbers.length > 0" :numbers />

    <WorkspaceSectionTiles workspace-key="svetaine" />

    <OverviewSection v-if="tenants.length > 0" :title="$t('svetaine.overview.traffic.title')">
      <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p v-if="analytics?.hostname" class="text-sm text-muted-foreground">
            {{ $t('analytics.hostname_hint', { hostname: analytics.hostname }) }}
          </p>
          <OverviewScopeSwitch v-model="period" :options="periodOptions" :label="$t('svetaine.overview.period')" />
        </div>

        <p class="text-sm text-muted-foreground">
          {{ $t('analytics.since_notice') }}
        </p>

        <Skeleton v-if="isFetchingAnalytics" class="h-48 w-full" />

        <EmptyState
          v-else-if="!analytics?.available"
          :title="$t('analytics.unavailable_title')"
          :description="$t('analytics.unavailable_description')"
        />

        <EmptyState
          v-else-if="!analytics.totals?.pageviews"
          :title="$t('analytics.empty_title')"
          :description="$t('analytics.empty_description')"
        />

        <OverviewChart v-else :summary="trafficSummary">
          <div ref="analyticsWrapper" class="w-full overflow-x-auto border border-border bg-card p-2" />
        </OverviewChart>
      </div>
    </OverviewSection>

    <OverviewSection
      v-if="analytics?.available && analytics.topPages.length > 0"
      :title="$t('svetaine.overview.top_pages')"
    >
      <ol class="divide-y divide-border border-y border-border text-sm" data-slot="top-pages">
        <li v-for="page in analytics.topPages" :key="page.path" class="flex items-center justify-between gap-4 px-1 py-2.5">
          <span class="truncate">{{ page.path }}</span>
          <span class="shrink-0 font-semibold tabular-nums">{{ page.views }}</span>
        </li>
      </ol>
    </OverviewSection>
  </OverviewPage>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { areaY, line, plot, ruleY } from '@observablehq/plot';
import { computed, nextTick, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { summarizeTrafficTrend } from './Composables/trafficTrend';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewChart from '@/Components/Overview/OverviewChart.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import WorkspaceSectionTiles from '@/Components/Overview/WorkspaceSectionTiles.vue';
import OverviewScopeSwitch from '@/Components/Overview/OverviewScopeSwitch.vue';
import { EmptyState, OverviewSection } from '@/Components/Patterns';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Skeleton } from '@/Components/ui/skeleton';
import { useApi } from '@/Composables/useApi';
import type { AnalyticsOverviewData } from '@/Types/api.d';

const props = defineProps<{
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
  counts: { newsDrafts: number | null; calendarDrafts: number | null; news: number | null; pages: number | null };
}>();

const selectedTenantId = computed(() => props.providedTenant?.id ? String(props.providedTenant.id) : undefined);

const handleTenantUpdateValue = (value: string) => {
  router.reload({ data: { tenant_id: Number(value) } });
};

// Each number opens the list it counts, narrowed to the same unit (O17).
const numbers = computed<OverviewNumberItem[]>(() => {
  const tenantId = props.providedTenant?.id;
  const listing = (routeName: string, filter: Record<string, unknown> = {}) =>
    route(routeName, { filters: JSON.stringify({ ...filter, tenant_id: tenantId }) });

  const candidates: (OverviewNumberItem & { hidden: boolean })[] = [
    {
      key: 'news_drafts',
      label: $t('svetaine.overview.numbers.news_drafts'),
      value: props.counts.newsDrafts ?? 0,
      href: listing('news.index', { draft: true }),
      tone: 'attention',
      hidden: props.counts.newsDrafts === null,
    },
    {
      key: 'calendar_drafts',
      label: $t('svetaine.overview.numbers.calendar_drafts'),
      value: props.counts.calendarDrafts ?? 0,
      href: listing('calendar.index', { is_draft: true }),
      tone: 'attention',
      hidden: props.counts.calendarDrafts === null,
    },
    {
      key: 'news',
      label: $t('svetaine.overview.numbers.news'),
      value: props.counts.news ?? 0,
      href: listing('news.index'),
      hidden: props.counts.news === null,
    },
    {
      key: 'pages',
      label: $t('svetaine.overview.numbers.pages'),
      value: props.counts.pages ?? 0,
      href: listing('pages.index'),
      hidden: props.counts.pages === null,
    },
  ];

  return candidates.filter(candidate => !candidate.hidden).map(({ hidden: _hidden, ...number }) => number);
});

/**
 * Tenant-scoped traffic from the self-hosted Umami instance. The URL is computed so that
 * changing either the tenant or the period re-fetches; `refetch` watches it.
 */
const period = ref<AnalyticsOverviewData['period']>('30d');

const periodOptions = computed(() => [
  { value: '7d', label: $t('analytics.period_7d') },
  { value: '30d', label: $t('analytics.period_30d') },
  { value: '12m', label: $t('analytics.period_12m') },
]);

const analyticsUrl = computed(() => route('api.v1.admin.analytics.overview', {
  tenant_id: props.providedTenant?.id,
  period: period.value,
}));

const { data: analytics, isFetching: isFetchingAnalytics } = useApi<AnalyticsOverviewData>(
  analyticsUrl,
  {
    refetch: true,
    immediate: Boolean(props.providedTenant?.id),
    // The section renders its own unavailable state; a toast on every dashboard load
    // whenever Umami is down would be noise.
    showErrorToast: false,
  },
);

const analyticsWrapper = ref<HTMLElement | null>(null);

// Umami returns 'YYYY-MM-DD HH:mm:ss'; normalise to ISO so Date parsing is not implementation-defined.
const analyticsSeries = computed(() => analytics.value?.series?.map(point => ({
  ...point,
  date: new Date(point.date.replace(' ', 'T')),
})) ?? []);

// The figure's text twin (O17): the plot alone must never be the only carrier of the message.
const trafficSummary = computed(() => {
  const totals = analytics.value?.totals;
  const trend = summarizeTrafficTrend(analytics.value?.series ?? []);

  if (!totals || !trend) {
    return '';
  }

  return `${$t('svetaine.overview.traffic.summary', {
    views: String(totals.pageviews),
    visitors: String(totals.visitors),
    date: trend.peakDate,
    peak: String(trend.peakViews),
  })} ${$t(`svetaine.overview.traffic.${trend.direction}`)}`;
});

// The brand token, not a hex: dark mode swaps it (red → amber) without this file knowing.
const generateAnalyticsPlot = () => plot({
  x: { type: 'time', label: null },
  y: { grid: true, label: null, round: true, nice: true, ticks: 3 },
  marks: [
    ruleY([0]),
    areaY(analyticsSeries.value, { x: 'date', y: 'pageviews', fill: 'var(--brand-fill)', fillOpacity: 0.15 }),
    line(analyticsSeries.value, { x: 'date', y: 'pageviews', stroke: 'var(--brand-fill)', strokeWidth: 2 }),
  ],
  marginTop: 20,
  marginBottom: 30,
  marginLeft: 35,
  width: 720,
  height: 200,
});

// The figure only exists once data has arrived, so wait for the DOM before drawing.
watch(analyticsSeries, async () => {
  await nextTick();

  if (!analyticsWrapper.value) {
    return;
  }

  analyticsWrapper.value.innerHTML = '';
  analyticsWrapper.value.appendChild(generateAnalyticsPlot());
});
</script>
