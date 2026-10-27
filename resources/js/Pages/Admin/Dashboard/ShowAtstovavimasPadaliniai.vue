<template>
  <OverviewPage
    eyebrow="ViSAK"
    :title="$t('visak.tenant_overview.title')"
    :head-title="`ViSAK · ${$t('visak.tenant_overview.title')}`"
    :lead="$t(hasStats ? 'visak.tenant_overview.lead' : 'visak.tenant_overview.lead_public')"
  >
    <template v-if="hasStats" #actions>
      <div class="flex flex-wrap items-center gap-3">
        <TenantScopeSelector
          compact
          :label="$t('visak.tenant_scope.stats_label')"
          :description="$t('visak.tenant_scope.stats_description')"
          :tenants="props.statsTenants"
          :selected-tenants="timelineFilters.selectedStatsTenants.value"
          @update:selected-tenants="timelineFilters.setSelectedStatsTenants"
        />
      </div>
    </template>

    <!-- Tenant numbers arrive with the timeline request; until then a placeholder, not a wrong zero. -->
    <template v-if="hasStats">
      <OverviewNumbers v-if="tenantTimelineData.loaded.value" :numbers />
      <OverviewNumbersSkeleton v-else :count="4" />
      <p class="-mt-2 text-xs text-muted-foreground" data-testid="counted-scopes-note">
        {{ $t('visak.tenant_overview.counted_scopes', { scopes: countedScopes.join(', ') }) }}
      </p>
    </template>

    <div v-if="hasStats" class="grid gap-8 lg:grid-cols-2 lg:gap-10" data-slot="atstovavimas-tenant-primary-section">
      <div class="min-w-0">
        <InstitutionAttentionSkeleton v-if="!tenantTimelineData.loaded.value" />
        <InstitutionsNeedingAttention
          v-else
          :institutions="attention"
          :title="$t('visak.institution_summary.needs_attention')"
          :limit="ATTENTION_LIMIT"
          :askable="canAskAboutActivity"
          @record="recordActivityFor"
          @ask="askAboutActivity"
        />
      </div>
      <OverviewSection :title="$t('visak.overview.trend.title')" :icon="ChartArea" variant="home" class="min-w-0">
        <InstitutionStatusTrendChart
          :data="statusHistory.data.value ?? []"
          :missing-dates="statusHistory.missingDates.value"
          :days="historyDays"
          :loading="statusHistory.isFetching.value"
          :baseline-date="statusHistory.baselineDate.value"
          @update:days="historyDays = $event"
        />
        <p v-if="statusHistory.pending.value" class="mt-2 text-sm text-muted-foreground" role="status">
          {{ $t('metrics.history.pending') }}
        </p>
        <p v-if="statusHistory.error.value" class="mt-2 text-sm text-status-danger" role="alert">
          {{ $t('metrics.history.error') }}
        </p>
      </OverviewSection>
    </div>

    <!-- The timeline is a workbench: it renders only once it is near the viewport, and never on a phone. -->
    <section v-if="isAtLeastMd" :aria-label="$t('visak.overview.timeline.title')">
      <div ref="timelineAnchor" class="min-h-64">
        <TimelineGanttSkeleton v-if="!timelineVisible || !ganttRows.loaded.value" />
        <TenantTimelineSection
          v-else
          :available-tenants="props.ganttTenants"
          :tenant-institutions="ganttData.formattedTenantInstitutions.value"
          :meetings="ganttData.tenantMeetings.value"
          :gaps="ganttData.tenantGaps.value"
          :institution-names="tenantInstitutionNames"
          :tenant-names
          :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
          :institution-has-activity="ganttData.tenantInstitutionHasActivity.value"
          :institution-periodicity="tenantInstitutionPeriodicity"
          :duty-members="ganttData.tenantDutyMembers.value"
          :inactive-periods="ganttData.tenantInactivePeriods.value"
          :loading-range="meetingsLoadingRange"
          :meetings-loading="meetingsLoadingVisible"
          :representative-activity
          :activity-requests="ganttActivityRequests.data.value ?? []"
          :show-activity-requests-option="canAskAboutActivity || (ganttActivityRequests.data.value?.length ?? 0) > 0"
          @create-meeting="onCreateMeeting"
          @create-check-in="actions.onGapCreateCheckIn"
          @range-changed="onTenantRangeChanged"
        />
      </div>
    </section>
    <p v-else class="border-t border-border pt-3 text-sm text-muted-foreground">
      {{ $t('visak.overview.timeline.phone_note') }}
      <Link :href="route('meetings.index')" class="text-foreground underline underline-offset-4">
        {{ $t('visak.overview.timeline.phone_link') }}
      </Link>
    </p>

    <div v-if="hasStats" class="grid gap-8 lg:grid-cols-2 lg:gap-10" data-slot="atstovavimas-tenant-secondary-section">
      <section ref="metricsAnchor" class="min-h-48 min-w-0" data-slot="atstovavimas-rep-metrics">
        <OverviewSection :title="$t('visak.tenant_overview.rep_metrics')" :icon="ChartNoAxesCombined" variant="home">
          <p v-if="repMetrics.error.value" class="mb-3 text-sm text-status-danger" role="alert">
            {{ $t('metrics.history.error') }}
          </p>
          <TenantRepMetrics :data="scopedRepMetrics" :loading="!metricsVisible || repMetrics.isFetching.value" />
        </OverviewSection>
      </section>
      <OverviewSection :title="$t('visak.tenant_overview.representatives')" :icon="Users" variant="home" class="min-w-0" data-slot="atstovavimas-representatives">
        <RepresentativeActivitySection
          v-if="representativeActivity"
          :stats="representativeActivity.stats"
          :users="representativeActivity.preview_users"
          :tenant-ids="timelineFilters.selectedStatsTenants.value"
          :loading="tenantTimelineData.isFetching.value"
        />
        <RepresentativeActivitySkeleton v-else />
      </OverviewSection>
    </div>

    <WorkspaceSectionTiles v-if="hasStats" workspace-key="atstovavimas" variant="home" />

    <AddCheckInDialog
      v-if="actions.showCreateCheckIn.value"
      :open="!!actions.showCreateCheckIn.value"
      :institution-id="actions.showCreateCheckIn.value.institutionId!"
      :institution-name="checkInInstitutionName"
      :initial-start-date="actions.showCreateCheckIn.value.startDate"
      :initial-end-date="actions.showCreateCheckIn.value.endDate"
      @close="handleCheckInDialogClose"
    />
  </OverviewPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChartArea, ChartNoAxesCombined, Users } from '@lucide/vue';
import { useIntersectionObserver, useMediaQuery } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

import InstitutionAttentionSkeleton from './Components/InstitutionAttentionSkeleton.vue';
import OverviewNumbersSkeleton from './Components/OverviewNumbersSkeleton.vue';
import RepresentativeActivitySection from './Components/RepresentativeActivitySection.vue';
import RepresentativeActivitySkeleton from './Components/RepresentativeActivitySkeleton.vue';
import InstitutionStatusTrendChart from './Components/InstitutionStatusTrendChart.vue';
import TenantRepMetrics from './Components/TenantRepMetrics.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import TenantTimelineSection from './Components/TenantTimelineSection.vue';
import TimelineGanttSkeleton from './Components/TimelineGanttSkeleton.vue';
import { useAtstovavimasActions } from './Composables/useAtstovavimasActions';
import { useGanttChartData } from './Composables/useGanttChartData';
import { provideGanttSettings } from './Composables/useGanttSettings';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import { useTenantMeetings } from './Composables/useTenantMeetings';
import { useTenantStatusHistory } from './Composables/useTenantStatusHistory';
import { useTenantTimelineData } from './Composables/useTenantTimelineData';
import type { AtstovavimasInstitution, AtstovavimasTenant, GanttActivityRequest, InstitutionStatusSummaryData, TenantRepMetrics as TenantRepMetricsData } from './types';

import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import WorkspaceSectionTiles from '@/Components/Overview/WorkspaceSectionTiles.vue';
import type { InstitutionActivityInsight } from '@/Components/Home/types';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { InstitutionScope } from '@/Types/enums';
import { useApi } from '@/Composables/useApi';
import { OverviewSection } from '@/Components/Patterns';

const props = defineProps<{
  /** The padaliniai the user manages: the statistics are theirs alone. Empty for a rep. */
  statsTenants: AtstovavimasTenant[];
  /** Every padalinys: the Gantt shows public meetings anywhere, plus the user's own bodies. */
  ganttTenants: AtstovavimasTenant[];
  defaultGanttTenantIds: string[];
  /** May read ViSAK → Užduotys; the open-task number counts the selected padaliniai's tasks. */
  canViewTenantTasks: boolean;
  canAskAboutActivity?: boolean;
  /** Institution scopes the statistics count (RepMetricSettings); the Gantt shows every scope. */
  countedScopes?: string[];
}>();

const countedScopes = computed(() => props.countedScopes ?? []);

const ATTENTION_LIMIT = 4;

const actionWindow = useActionWindow();
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const hasStats = computed(() => props.statsTenants.length > 0);

const timelineFilters = provideTimelineFilters([], props.ganttTenants, {
  statsTenants: props.statsTenants,
  defaultGanttTenantIds: props.defaultGanttTenantIds,
  ganttFollowsStats: hasStats.value,
});
const actions = useAtstovavimasActions([]);
const tenantTimelineData = useTenantTimelineData();
const ganttRows = useTenantTimelineData<AtstovavimasInstitution[]>('api.v1.admin.visak.gantt');
const ganttActivityRequests = useTenantTimelineData<GanttActivityRequest[]>('api.v1.admin.visak.activityRequests');
const tenantMeetings = useTenantMeetings(() => timelineFilters.selectedTenantForGantt.value);
const tenantInstitutionsData = computed(() => tenantTimelineData.data.value?.institutions ?? []);
const ganttInstitutionsData = computed(() => ganttRows.data.value ?? []);
const representativeActivity = computed(() => tenantTimelineData.data.value?.representative_activity);
const ganttData = useGanttChartData(ganttInstitutionsData, props.ganttTenants, tenantMeetings.meetings);

provideGanttSettings();

// --- Attention and numbers --------------------------------------------------------------------

const institutionSummary = computed<InstitutionStatusSummaryData>(() =>
  tenantTimelineData.data.value?.institution_summary ?? {
    all: 0,
    needs_attention: 0,
    overdue: 0,
    approaching: 0,
    no_activity: 0,
    current: 0,
    recent_check_in: 0,
  },
);

const tenantNames = computed(() => ganttData.getTenantNames());

const attention = computed<InstitutionActivityInsight[]>(() => {
  // Name the padalinys only when the list mixes several.
  const showTenant = timelineFilters.selectedStatsTenants.value.length > 1;

  return tenantInstitutionsData.value
    .filter((institution: AtstovavimasInstitution) => institution.in_summary !== false && institution.activity_status?.requires_action)
    .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
    .map(institution => ({
      id: String(institution.id),
      name: String(institution.name ?? ''),
      tenant_name: showTenant ? tenantNames.value[String(institution.tenant_id ?? institution.tenant?.id)] ?? null : null,
      ...institution.activity_status,
    }));
});

// Institucijos filters by padalinys name, so the selection travels as short names.
const institutionsWith = (activityStatus: string) => route('institutions.index', {
  activity_status: activityStatus,
  tenant_shortname: timelineFilters.selectedStatsTenants.value
    .map(id => props.statsTenants.find(tenant => String(tenant.id) === String(id))?.shortname)
    .filter(Boolean)
    .join(','),
});

const repMetrics = useApi<TenantRepMetricsData>(
  computed(() => route('api.v1.admin.visak.repMetrics', { tenant_ids: timelineFilters.selectedStatsTenants.value })),
  { immediate: false, refetch: false, showErrorToast: false },
);

const metricsAnchor = ref<HTMLElement | null>(null);
const metricsVisible = ref(false);
const metricsLoadedScope = ref('');
const metricsScope = computed(() => timelineFilters.selectedStatsTenants.value.join(','));
const scopedRepMetrics = computed(() => metricsLoadedScope.value === metricsScope.value ? repMetrics.data.value : null);
useIntersectionObserver(metricsAnchor, ([entry]) => {
  if (entry?.isIntersecting) metricsVisible.value = true;
}, { rootMargin: '200px' });
watch([metricsVisible, metricsScope], async ([visible, scope]) => {
  if (!visible || !hasStats.value) return;
  metricsLoadedScope.value = '';
  repMetrics.abort();
  await repMetrics.execute();
  if (metricsScope.value === scope) metricsLoadedScope.value = scope;
});

const numbers = computed<OverviewNumberItem[]>(() => [
  { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: institutionSummary.value.overdue, href: institutionsWith('overdue'), tone: 'danger' },
  { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: institutionSummary.value.approaching, href: institutionsWith('approaching'), tone: 'attention' },
  { key: 'recent_check_in', label: $t('activity_requests.recent_check_in'), value: institutionSummary.value.recent_check_in, href: institutionsWith('recent_check_in'), tone: 'info' },
  { key: 'no_activity', label: $t('visak.institution_summary.no_activity'), value: institutionSummary.value.no_activity, href: institutionsWith('no_activity'), tone: 'neutral' },

]);

function recordActivityFor(institution: InstitutionActivityInsight): void {
  actionWindow.open({ flow: 'institution.report', institution: { id: institution.id, name: institution.name } });
}

function askAboutActivity(institutions: InstitutionActivityInsight[]): void {
  actionWindow.open({
    flow: 'activity.request',
    activityRequestFilters: {
      tenantIds: [...timelineFilters.selectedStatsTenants.value],
      governanceScope: InstitutionScope.University,
    },
    institutions: institutions.map(institution => ({ id: institution.id, name: institution.name })),
  });
}

// Tenant institutions are rarely the viewer's own, so the window needs the name handed to it.
function onCreateMeeting(payload: { institution_id: string | number; suggestedAt: Date }): void {
  actions.onGapCreateMeeting({ ...payload, institutionName: tenantInstitutionNames.value[String(payload.institution_id)] });
}

// --- The trend chart --------------------------------------------------------------------------

const historyDays = ref(90);
const statusHistory = useTenantStatusHistory();

watch(
  () => [timelineFilters.selectedStatsTenants.value.join(','), historyDays.value] as const,
  () => {
    if (timelineFilters.selectedStatsTenants.value.length > 0) {
      statusHistory.load(timelineFilters.selectedStatsTenants.value, historyDays.value);
    }
  },
  { immediate: true },
);

// --- The timeline workbench -------------------------------------------------------------------

const timelineAnchor = ref<HTMLElement | null>(null);
const timelineVisible = ref(false);
useIntersectionObserver(timelineAnchor, ([entry]) => {
  if (entry?.isIntersecting) {
    timelineVisible.value = true;
  }
}, { rootMargin: '200px' });

const meetingsLoadingRange = computed(() => {
  const pending = tenantMeetings.pendingWindow.value;
  return pending ? { from: new Date(pending.from), until: new Date(pending.until) } : null;
});

// Only shown after 300ms so a fast cache hit never flashes it.
const meetingsLoadingVisible = ref(false);
let meetingsLoadingTimeout: ReturnType<typeof setTimeout> | null = null;
watch(tenantMeetings.isFetching, (loading) => {
  if (meetingsLoadingTimeout) {
    clearTimeout(meetingsLoadingTimeout);
  }

  if (loading) {
    meetingsLoadingTimeout = setTimeout(() => {
      meetingsLoadingVisible.value = true;
    }, 300);
  }
  else {
    meetingsLoadingVisible.value = false;
  }
});

watch(ganttRows.isFetching, (loading) => {
  timelineFilters.tenantInstitutionsLoading.value = loading;
}, { immediate: true });

watch(ganttRows.loaded, (loaded) => {
  timelineFilters.tenantInstitutionsLoaded.value = loaded;
}, { immediate: true });

// Meetings around today; further windows are fetched as the Gantt range extends.
function loadInitialMeetingWindow(): void {
  const today = new Date();
  tenantMeetings.ensureRange(
    new Date(today.getFullYear(), today.getMonth() - 3, 1),
    new Date(today.getFullYear(), today.getMonth() + 4, 0),
  );
}

function onTenantRangeChanged(min: Date, max: Date): void {
  tenantMeetings.ensureRange(min, max);
}

// The Gantt's three requests are the heaviest on the page: wait until it can actually be seen.
const ganttActive = computed(() => isAtLeastMd.value && timelineVisible.value);

onMounted(() => {
  tenantTimelineData.load(timelineFilters.selectedStatsTenants.value);
});

watch(ganttActive, (active) => {
  if (!active) return;
  const tenants = timelineFilters.selectedTenantForGantt.value;
  if (tenants.length === 0) return;
  ganttRows.load(tenants);
  ganttActivityRequests.load(tenants);
  loadInitialMeetingWindow();
}, { immediate: true });

watch(() => timelineFilters.selectedStatsTenants.value, (tenantIds) => {
  tenantTimelineData.load(tenantIds);
}, { deep: true });

watch(() => timelineFilters.selectedTenantForGantt.value, (newTenants, oldTenants) => {
  if (newTenants.length === 0) return;
  const changed = newTenants.join(',') !== (oldTenants ?? []).join(',');
  // Loaded windows belong to the old selection even while the Gantt is hidden.
  if (changed) tenantMeetings.reset();
  if (!ganttActive.value) return;
  ganttRows.load(newTenants);
  ganttActivityRequests.load(newTenants);
  if (changed) loadInitialMeetingWindow();
}, { deep: true });

// --- Gantt lookup maps ------------------------------------------------------------------------

const tenantInstitutionNames = computed(() => {
  const result: Record<string, string> = {};
  for (const institution of ganttData.tenantInstitutions.value) {
    result[institution.id as string] = String(institution.name ?? '');
  }
  return result;
});

const tenantInstitutionHasPublicMeetings = computed(() => {
  const result: Record<string, boolean> = {};
  for (const institution of ganttData.tenantInstitutions.value) {
    result[institution.id as string] = Boolean(institution.has_public_meetings);
  }
  return result;
});

const tenantInstitutionPeriodicity = computed(() =>
  ganttData.getInstitutionPeriodicity(ganttData.tenantInstitutions.value as unknown as AtstovavimasInstitution[]),
);

const checkInInstitutionName = computed(() => {
  const institutionId = actions.showCreateCheckIn.value?.institutionId;

  return institutionId ? tenantInstitutionNames.value[institutionId] : undefined;
});

function handleCheckInDialogClose(): void {
  actions.showCreateCheckIn.value = null;
  refreshDashboard();
}

watch(actionWindow.isOpen, (open, wasOpen) => {
  if (wasOpen && !open) refreshDashboard();
});

function refreshDashboard(): void {
  tenantTimelineData.load(timelineFilters.selectedStatsTenants.value, true);
  if (ganttActive.value) {
    ganttRows.load(timelineFilters.selectedTenantForGantt.value, true);
    ganttActivityRequests.load(timelineFilters.selectedTenantForGantt.value, true);
  }
  void statusHistory.load(timelineFilters.selectedStatsTenants.value, historyDays.value, true);
  if (metricsVisible.value) void repMetrics.execute();
  if (ganttActive.value) void tenantMeetings.refresh();
}
</script>
