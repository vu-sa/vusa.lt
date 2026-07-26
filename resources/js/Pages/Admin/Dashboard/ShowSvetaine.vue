<template>
  <AdminContentPage :title="$t('Svetainė')">
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 inline-flex items-center gap-6">
        <h3 class="mb-0">
          Pasirinkti padalinį
        </h3>
        <div>
          <Select :model-value="selectedTenantId" @update:model-value="handleTenantUpdateValue">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Pasirinkite padalinį" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <section class="mb-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
          <div>
            <h3 class="mb-0">
              {{ $t('analytics.title') }}
            </h3>
            <p v-if="analytics?.hostname" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              {{ $t('analytics.hostname_hint', { hostname: analytics.hostname }) }}
            </p>
          </div>
          <Tabs v-model="period">
            <TabsList>
              <TabsTrigger value="7d">
                {{ $t('analytics.period_7d') }}
              </TabsTrigger>
              <TabsTrigger value="30d">
                {{ $t('analytics.period_30d') }}
              </TabsTrigger>
              <TabsTrigger value="12m">
                {{ $t('analytics.period_12m') }}
              </TabsTrigger>
            </TabsList>
          </Tabs>
        </div>

        <p class="mb-4 flex items-start gap-2 rounded-lg bg-zinc-100 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
          <Info class="mt-0.5 size-4 shrink-0" />
          <span>{{ $t('analytics.since_notice') }}</span>
        </p>

        <div v-if="isFetchingAnalytics" class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
          <Skeleton v-for="n in 3" :key="n" class="h-44 w-full rounded-xl" />
        </div>

        <EmptyState
          v-else-if="!analytics?.available"
          :title="$t('analytics.unavailable_title')"
          :description="$t('analytics.unavailable_description')" />

        <EmptyState
          v-else-if="!analytics.totals?.pageviews"
          :title="$t('analytics.empty_title')"
          :description="$t('analytics.empty_description')" />

        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>
                <div class="inline-flex items-center gap-2">
                  <Eye class="size-5" />
                  {{ $t('analytics.pageviews') }}
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-2 gap-2">
                <p>{{ $t('analytics.pageviews') }}</p>
                <p>{{ $t('analytics.visitors') }}</p>
                <span class="inline-block text-4xl font-bold">
                  {{ analytics.totals.pageviews }}
                </span>
                <span class="inline-block text-4xl font-bold">
                  {{ analytics.totals.visitors }}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>{{ $t('analytics.trend') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div ref="analyticsWrapper" class="mx-auto w-fit" />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>
                <div class="inline-flex items-center gap-2">
                  <component :is="PageIconFilled" />
                  {{ $t('analytics.top_pages') }}
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ol class="flex flex-col gap-1 text-sm">
                <li
                  v-for="page in analytics.topPages"
                  :key="page.path"
                  class="flex items-center justify-between gap-4">
                  <span class="truncate text-zinc-600 dark:text-zinc-300">{{ page.path }}</span>
                  <span class="shrink-0 font-semibold tabular-nums">{{ page.views }}</span>
                </li>
              </ol>
            </CardContent>
          </Card>
        </div>
      </section>
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { areaY, line, plot, ruleY } from '@observablehq/plot';
import { ref, watch, computed, nextTick } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Info } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import EmptyState from '@/Components/Empty/EmptyState.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Skeleton } from '@/Components/ui/skeleton';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { useApi } from '@/Composables/useApi';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { PageIconFilled } from '@/Components/icons';
import type { AnalyticsOverviewData } from '@/Types/api.d';

const { tenants, providedTenant } = defineProps<{
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
}>();

const selectedTenantId = computed(() => providedTenant?.id ? String(providedTenant.id) : undefined);

const handleTenantUpdateValue = (value: string) => {
  router.reload({ data: { tenant_id: Number(value) } });
};

/**
 * Tenant-scoped traffic from the self-hosted Umami instance. The URL is computed so that
 * changing either the tenant or the period re-fetches; `refetch` watches it.
 */
const period = ref<AnalyticsOverviewData['period']>('30d');

const analyticsUrl = computed(() => route('api.v1.admin.analytics.overview', {
  tenant_id: providedTenant?.id,
  period: period.value,
}));

const { data: analytics, isFetching: isFetchingAnalytics } = useApi<AnalyticsOverviewData>(
  analyticsUrl,
  {
    refetch: true,
    immediate: Boolean(providedTenant?.id),
    // The section renders its own unavailable state; a toast on every dashboard load
    // whenever Umami is down would be noise.
    showErrorToast: false,
  },
);

const analyticsWrapper = ref<HTMLElement | null>(null);

// Umami returns 'YYYY-MM-DD HH:mm:ss'; normalise to ISO so Date parsing is not
// implementation-defined.
const analyticsSeries = computed(() => analytics.value?.series?.map(point => ({
  ...point,
  date: new Date(point.date.replace(' ', 'T')),
})) ?? []);

const generateAnalyticsPlot = () => plot({
  x: { type: 'time', label: null },
  y: { grid: true, label: null, round: true, nice: true, ticks: 3 },
  marks: [
    ruleY([0]),
    areaY(analyticsSeries.value, { x: 'date', y: 'pageviews', fill: '#aa243022' }),
    line(analyticsSeries.value, { x: 'date', y: 'pageviews', stroke: '#aa2430', strokeWidth: 2 }),
  ],
  marginTop: 20,
  marginBottom: 30,
  marginLeft: 35,
  width: 350,
  height: 170,
});

// The chart card only exists once data has arrived, so wait for the DOM before drawing.
watch(analyticsSeries, async () => {
  await nextTick();

  if (!analyticsWrapper.value) {
    return;
  }

  analyticsWrapper.value.innerHTML = '';
  analyticsWrapper.value.appendChild(generateAnalyticsPlot());
});

// Setup breadcrumbs for the Svetaine page
usePageBreadcrumbs([
  { label: $t('Svetainė'), icon: PageIconFilled },
]);
</script>
