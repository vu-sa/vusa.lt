<template>
  <OverviewPage
    eyebrow="ViSAK"
    :title="$t('representatives.title')"
    :head-title="`ViSAK · ${$t('representatives.title')}`"
    :lead="$t('representatives.lead')"
  >
    <template v-if="tenants.length > 1" #actions>
      <TenantScopeSelector
        compact
        :description="$t('representatives.tenant_scope_description')"
        :tenants
        :selected-tenants
        @update:selected-tenants="selectTenants"
      />
    </template>

    <Deferred data="overview">
      <template #fallback>
        <OverviewNumbersSkeleton :count="4" />
        <CollectionSkeleton :rows="5" />
      </template>

      <template v-if="overview">
        <ul class="grid grid-cols-2 gap-x-6 gap-y-4 border-t border-border pt-4 lg:grid-cols-4" data-slot="representative-numbers">
          <li v-for="number in numbers" :key="number.key">
            <component
              :is="number.filter ? 'button' : 'div'"
              :type="number.filter ? 'button' : undefined"
              :class="['group flex min-h-11 flex-col items-start gap-1 text-left', number.filter && 'underline-offset-4 hover:underline focus-visible:underline']"
              :data-number="number.key"
              @click="number.filter && showList(number.filter)"
            >
              <span :class="['text-3xl font-semibold leading-none tabular-nums', number.danger && number.value !== 0 ? 'text-status-danger' : 'text-foreground']">
                {{ number.display ?? number.value }}
              </span>
              <span class="text-sm text-muted-foreground group-hover:text-foreground">{{ number.label }}</span>
            </component>
          </li>
        </ul>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] lg:gap-10">
          <RepresentativeAttentionList :reps="attentionReps" class="min-w-0" @show-all="showList('attention')" />

          <OverviewSection :title="$t('representatives.activity.title')" :icon="Activity" variant="home" class="min-w-0" data-slot="representative-activity">
            <RepresentativeShareBar :segments="activitySegments" :active-key="activityFilter" @select="toggleList" />
            <p class="text-sm text-muted-foreground" data-slot="representative-own-work">
              {{ $t('representatives.activity.own_work', { count: String(overview.summary.recordedOwn), total: String(inOffice) }) }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ $t('representatives.activity.caption') }}
            </p>
          </OverviewSection>
        </div>

        <div v-if="hasMetrics || learning" class="grid gap-8 lg:grid-cols-2 lg:gap-10">
          <section v-if="hasMetrics" ref="metricsAnchor" class="min-h-48 min-w-0" data-slot="representative-metrics">
            <OverviewSection :title="$t('representatives.metrics.title')" :icon="ChartNoAxesCombined" variant="home">
              <p v-if="repMetrics.error.value" class="text-sm text-status-danger" role="alert">
                {{ $t('metrics.history.error') }}
              </p>
              <TenantRepMetrics :data="scopedRepMetrics" :loading="!metricsVisible || repMetrics.isFetching.value" />
            </OverviewSection>
          </section>
          <OverviewSection v-if="learning" :title="$t('representatives.onboarding.title')" :icon="Route" variant="home" class="min-w-0" data-slot="representative-onboarding">
            <RepresentativeShareBar :segments="onboardingSegments" />
            <p class="text-xs text-muted-foreground">
              {{ $t('representatives.onboarding.caption') }}
            </p>
          </OverviewSection>
        </div>

        <section ref="listAnchor" class="scroll-mt-[calc(var(--shell-chrome-height,0px)+1rem)]" data-slot="representative-all">
          <OverviewSection :title="$t('representatives.list.title')" :icon="Users" variant="home" :count="overview.summary.total">
            <RepresentativeList v-model:filter="listFilter" :reps="overview.reps" />
          </OverviewSection>
        </section>
      </template>
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, router } from '@inertiajs/vue3';
import { Activity, ChartNoAxesCombined, Route, Users } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, nextTick, ref, watch } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { CollectionSkeleton, OverviewSection } from '@/Components/Patterns';
import { useApi } from '@/Composables/useApi';
import RepresentativeAttentionList from '@/Features/Admin/Representatives/RepresentativeAttentionList.vue';
import RepresentativeList from '@/Features/Admin/Representatives/RepresentativeList.vue';
import RepresentativeShareBar, { type ShareSegment } from '@/Features/Admin/Representatives/RepresentativeShareBar.vue';
import type { RepActivity, RepListFilter, RepresentativesOverview } from '@/Features/Admin/Representatives/types';

import OverviewNumbersSkeleton from './Components/OverviewNumbersSkeleton.vue';
import TenantRepMetrics from './Components/TenantRepMetrics.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import type { AtstovavimasTenant, TenantRepMetrics as TenantRepMetricsData } from './types';

const props = defineProps<{
  /** The padaliniai the viewer manages; empty for a coordinator who only coordinates. */
  tenants: AtstovavimasTenant[];
  selectedTenantIds: string[];
  overview?: RepresentativesOverview;
}>();

const selectedTenants = ref<string[]>([...props.selectedTenantIds]);
watch(() => props.selectedTenantIds, (ids) => {
  selectedTenants.value = [...ids];
});

function selectTenants(ids: string[]): void {
  selectedTenants.value = ids;
  router.reload({ data: { tenants: ids }, only: ['selectedTenantIds', 'overview'] });
}

const listFilter = ref<RepListFilter>('all');
const listAnchor = ref<HTMLElement | null>(null);

async function showList(filter: RepListFilter): Promise<void> {
  listFilter.value = filter;
  await nextTick();
  listAnchor.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// A second press on the same segment clears it, so the bar works as a toggle.
function toggleList(key: string): void {
  if (listFilter.value === key) {
    listFilter.value = 'all';

    return;
  }
  void showList(key as RepActivity);
}

const activityFilter = computed(() => (['week', 'month', 'stale', 'never'] as RepListFilter[]).includes(listFilter.value) ? listFilter.value : null);

const attentionReps = computed(() => props.overview?.reps.filter(rep => rep.attention !== null) ?? []);
const inOffice = computed(() => (props.overview?.summary.total ?? 0) - (props.overview?.summary.incoming ?? 0));
const learning = computed(() => props.overview?.summary.learning ?? null);

interface NumberItem { key: string; label: string; value: number; display?: string; filter?: RepListFilter; danger?: boolean }

const numbers = computed<NumberItem[]>(() => {
  const summary = props.overview?.summary;
  if (!summary) return [];

  const items: NumberItem[] = [
    { key: 'total', label: $t('representatives.numbers.total'), value: summary.total, filter: 'all' },
    { key: 'attention', label: $t('representatives.attention.title'), value: summary.attention, filter: 'attention', danger: true },
  ];
  if (summary.learning) {
    const percent = Math.round((summary.learning.finished / summary.learning.reps) * 100);
    items.push({ key: 'ready', label: $t('representatives.numbers.ready'), value: percent, display: `${percent} %` });
  }
  items.push({ key: 'incoming', label: $t('representatives.numbers.incoming'), value: summary.incoming, filter: 'incoming' });

  return items;
});

const activitySegments = computed<ShareSegment[]>(() => {
  const activity = props.overview?.summary.activity;

  return [
    { key: 'week', label: $t('representatives.activity.week'), value: activity?.week ?? 0, tone: 'success' },
    { key: 'month', label: $t('representatives.activity.month'), value: activity?.month ?? 0, tone: 'info' },
    { key: 'stale', label: $t('representatives.activity.stale'), value: activity?.stale ?? 0, tone: 'attention' },
    { key: 'never', label: $t('representatives.activity.never'), value: activity?.never ?? 0, tone: 'danger' },
  ];
});

const onboardingSegments = computed<ShareSegment[]>(() => [
  { key: 'finished', label: $t('representatives.onboarding.finished'), value: learning.value?.finished ?? 0, tone: 'success' },
  { key: 'inProgress', label: $t('representatives.onboarding.in_progress'), value: learning.value?.inProgress ?? 0, tone: 'progress' },
  { key: 'notStarted', label: $t('representatives.onboarding.not_started'), value: learning.value?.notStarted ?? 0, tone: 'neutral' },
]);

// --- Padalinys metrics: the heaviest request, loaded once the panel nears the viewport. ---------

const hasMetrics = computed(() => selectedTenants.value.length > 0);
const repMetrics = useApi<TenantRepMetricsData>(
  computed(() => route('api.v1.admin.visak.repMetrics', { tenant_ids: selectedTenants.value })),
  { immediate: false, refetch: false, showErrorToast: false },
);
const metricsAnchor = ref<HTMLElement | null>(null);
const metricsVisible = ref(false);
const metricsLoadedScope = ref('');
const metricsScope = computed(() => selectedTenants.value.join(','));
const scopedRepMetrics = computed(() => metricsLoadedScope.value === metricsScope.value ? repMetrics.data.value : null);

useIntersectionObserver(metricsAnchor, ([entry]) => {
  if (entry?.isIntersecting) metricsVisible.value = true;
}, { rootMargin: '200px' });

watch([metricsVisible, metricsScope], async ([visible, scope]) => {
  if (!visible || !hasMetrics.value) return;
  metricsLoadedScope.value = '';
  repMetrics.abort();
  await repMetrics.execute();
  if (metricsScope.value === scope) metricsLoadedScope.value = scope;
});
</script>
