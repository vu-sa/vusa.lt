<template>
  <OverviewPage
    eyebrow="ViSAK"
    :title="$t('visak.tenant_overview.title')"
    :head-title="`ViSAK · ${$t('visak.tenant_overview.title')}`"
    :lead="$t(hasStats ? 'visak.tenant_overview.lead' : 'visak.tenant_overview.lead_public')"
  >
    <template v-if="hasStats" #actions>
      <TenantScopeSelector
        compact
        :label="$t('visak.tenant_scope.stats_label')"
        :description="$t('visak.tenant_scope.stats_description')"
        :tenants="props.statsTenants"
        :selected-tenants="timelineFilters.selectedStatsTenants.value"
        @update:selected-tenants="timelineFilters.setSelectedStatsTenants"
      />
    </template>

    <!-- Tenant numbers arrive with the timeline request; until then a placeholder, not a wrong zero. -->
    <template v-if="hasStats">
      <OverviewNumbers v-if="tenantTimelineData.loaded.value" :numbers />
      <OverviewNumbersSkeleton v-else :count="canViewTenantTasks ? 4 : 3" />
    </template>

    <div v-if="hasStats" class="grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16" data-slot="atstovavimas-tenant-primary-section">
      <div class="min-w-0">
        <InstitutionAttentionSkeleton v-if="!tenantTimelineData.loaded.value" />
        <InstitutionsNeedingAttention
          v-else
          :institutions="attention"
          :title="$t('visak.institution_summary.needs_attention')"
          :limit="ATTENTION_LIMIT"
          @record="recordActivityFor"
        />
      </div>

      <aside class="min-w-0">
        <TenantInsightsTabs
          :trend-data="statusHistory.data.value ?? []"
          :trend-summary
          :trend-loading="statusHistory.isFetching.value"
          :days="historyDays"
          :representative-activity
          :representatives-loading="tenantTimelineData.isFetching.value"
          :tenant-ids="timelineFilters.selectedStatsTenants.value"
          @update:days="historyDays = $event"
        />
      </aside>
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
          :institution-tenant="tenantInstitutionTenant"
          :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
          :institution-has-activity="ganttData.tenantInstitutionHasActivity.value"
          :institution-periodicity="tenantInstitutionPeriodicity"
          :duty-members="ganttData.tenantDutyMembers.value"
          :inactive-periods="ganttData.tenantInactivePeriods.value"
          :is-hidden="actions.showFullscreenGantt.value"
          :loading-range="meetingsLoadingRange"
          :meetings-loading="meetingsLoadingVisible"
          :representative-activity
          @create-meeting="onCreateMeeting"
          @create-check-in="actions.onGapCreateCheckIn"
          @fullscreen="actions.onGanttFullscreen('tenant')"
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

    <WorkspaceSectionTiles v-if="hasStats" workspace-key="atstovavimas" variant="home" />

    <!-- FullscreenGanttModal first so dialogs opened from within it appear on top -->
    <FullscreenGanttModal
      :is-open="actions.showFullscreenGantt.value"
      gantt-type="tenant"
      :available-tenants="props.ganttTenants"
      :user-institutions="[]"
      :user-meetings="[]"
      :user-gaps="[]"
      :user-institution-names="{}"
      :user-institution-tenant="{}"
      :tenant-institutions="ganttData.formattedTenantInstitutions.value"
      :tenant-meetings="ganttData.tenantMeetings.value"
      :tenant-gaps="ganttData.tenantGaps.value"
      :tenant-institution-names
      :tenant-institution-tenant
      :tenant-institution-has-public-meetings
      :tenant-institution-has-activity="ganttData.tenantInstitutionHasActivity.value"
      :tenant-institution-periodicity
      :tenant-duty-members="ganttData.tenantDutyMembers.value"
      :tenant-inactive-periods="ganttData.tenantInactivePeriods.value"
      :tenant-names
      :tenant-loading-range="meetingsLoadingRange"
      :tenant-meetings-loading="meetingsLoadingVisible"
      @update:is-open="actions.showFullscreenGantt.value = $event"
      @create-meeting="onCreateMeeting"
      @create-check-in="actions.onGapCreateCheckIn"
      @range-changed="onTenantRangeChanged"
    />

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
import { useIntersectionObserver, useMediaQuery } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

import FullscreenGanttModal from './Components/FullscreenGanttModal.vue';
import InstitutionAttentionSkeleton from './Components/InstitutionAttentionSkeleton.vue';
import OverviewNumbersSkeleton from './Components/OverviewNumbersSkeleton.vue';
import TenantInsightsTabs from './Components/TenantInsightsTabs.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import TenantTimelineSection from './Components/TenantTimelineSection.vue';
import TimelineGanttSkeleton from './Components/TimelineGanttSkeleton.vue';
import { useAtstovavimasActions } from './Composables/useAtstovavimasActions';
import { useGanttChartData } from './Composables/useGanttChartData';
import { provideGanttSettings } from './Composables/useGanttSettings';
import { summarizeStatusTrend } from './Composables/statusTrend';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import { useTenantMeetings } from './Composables/useTenantMeetings';
import { useTenantStatusHistory } from './Composables/useTenantStatusHistory';
import { useTenantTimelineData } from './Composables/useTenantTimelineData';
import type { AtstovavimasInstitution, AtstovavimasTenant, InstitutionStatusSummaryData } from './types';

import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import WorkspaceSectionTiles from '@/Components/Overview/WorkspaceSectionTiles.vue';
import type { InstitutionActivityInsight } from '@/Components/Home/types';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useApi } from '@/Composables/useApi';

const props = defineProps<{
  /** The padaliniai the user manages: the statistics are theirs alone. Empty for a rep. */
  statsTenants: AtstovavimasTenant[];
  /** Every padalinys: the Gantt shows public meetings anywhere, plus the user's own bodies. */
  ganttTenants: AtstovavimasTenant[];
  defaultGanttTenantIds: string[];
  /** May read ViSAK → Užduotys; the open-task number counts the selected padaliniai's tasks. */
  canViewTenantTasks: boolean;
}>();

const ATTENTION_LIMIT = 5;

const actionWindow = useActionWindow();
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const hasStats = computed(() => props.statsTenants.length > 0);

const timelineFilters = provideTimelineFilters([], props.ganttTenants, {
  statsTenants: props.statsTenants,
  defaultGanttTenantIds: props.defaultGanttTenantIds,
});
const actions = useAtstovavimasActions([]);
const tenantTimelineData = useTenantTimelineData();
const ganttRows = useTenantTimelineData<AtstovavimasInstitution[]>('api.v1.admin.visak.gantt');
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
  },
);

const tenantNames = computed(() => ganttData.getTenantNames());

const attention = computed<InstitutionActivityInsight[]>(() => {
  // Name the padalinys only when the list mixes several.
  const showTenant = timelineFilters.selectedStatsTenants.value.length > 1;

  return tenantInstitutionsData.value
    .filter((institution: AtstovavimasInstitution) => institution.activity_status?.requires_action)
    .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
    .map(institution => ({
      id: String(institution.id),
      name: String(institution.name ?? ''),
      tenant_name: showTenant ? tenantNames.value[String(institution.tenant_id ?? institution.tenant?.id)] ?? null : null,
      ...institution.activity_status,
    }));
});

const selectedTenantParam = computed(() => timelineFilters.selectedStatsTenants.value.join(','));

// Institucijos filters by padalinys name, so the selection travels as short names.
const institutionsWith = (activityStatus: string) => route('institutions.index', {
  activity_status: activityStatus,
  tenant_shortname: timelineFilters.selectedStatsTenants.value
    .map(id => props.statsTenants.find(tenant => String(tenant.id) === String(id))?.shortname)
    .filter(Boolean)
    .join(','),
});

// Only the total is read, so one row per request is enough.
const tenantOpenTasks = useApi<{ total: number }>(
  computed(() => route('api.v1.admin.tasks.index', { scope: 'tenant', per_page: 1, tenant: selectedTenantParam.value })),
  { immediate: hasStats.value && props.canViewTenantTasks, refetch: hasStats.value && props.canViewTenantTasks, showErrorToast: false },
);

const numbers = computed<OverviewNumberItem[]>(() => [
  { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: institutionSummary.value.overdue, href: institutionsWith('overdue'), tone: 'danger' },
  { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: institutionSummary.value.approaching, href: institutionsWith('approaching'), tone: 'attention' },
  { key: 'no_activity', label: $t('visak.institution_summary.no_activity'), value: institutionSummary.value.no_activity, href: institutionsWith('no_activity'), tone: 'neutral' },
  ...(props.canViewTenantTasks
    ? [{
        key: 'open_tasks',
        label: $t('visak.overview.numbers.open_tasks'),
        value: tenantOpenTasks.data.value?.total ?? 0,
        href: route('tasks.summary', { tenant: selectedTenantParam.value }),
      }]
    : []),
]);

function recordActivityFor(institution: InstitutionActivityInsight): void {
  actionWindow.open({ flow: 'institution.report', institution: { id: institution.id, name: institution.name } });
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

const trendSummary = computed(() => {
  const summary = summarizeStatusTrend(statusHistory.data.value ?? []);

  return summary
    ? $t(`visak.overview.trend.${summary.direction}`, { days: historyDays.value, from: summary.from, to: summary.to })
    : $t('visak.overview.trend.none');
});

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

onMounted(() => {
  tenantTimelineData.load(timelineFilters.selectedStatsTenants.value);
  ganttRows.load(timelineFilters.selectedTenantForGantt.value);
  loadInitialMeetingWindow();
});

watch(() => timelineFilters.selectedStatsTenants.value, (tenantIds) => {
  tenantTimelineData.load(tenantIds);
}, { deep: true });

watch(() => timelineFilters.selectedTenantForGantt.value, (newTenants, oldTenants) => {
  if (newTenants.length > 0) {
    ganttRows.load(newTenants);
    if (newTenants.join(',') !== (oldTenants ?? []).join(',')) {
      tenantMeetings.reset();
      loadInitialMeetingWindow();
    }
  }
}, { deep: true });

// --- Gantt lookup maps ------------------------------------------------------------------------

const tenantInstitutionNames = computed(() => {
  const result: Record<string, string> = {};
  for (const institution of ganttData.tenantInstitutions.value) {
    result[institution.id as string] = String(institution.name ?? '');
  }
  return result;
});

const tenantInstitutionTenant = computed(() => {
  const result: Record<string, string> = {};
  for (const institution of ganttData.tenantInstitutions.value) {
    result[institution.id as string] = String(institution.tenant_id ?? '');
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
  if (hasStats.value && props.canViewTenantTasks) {
    void tenantOpenTasks.execute();
  }
  tenantTimelineData.load(timelineFilters.selectedStatsTenants.value, true);
  ganttRows.load(timelineFilters.selectedTenantForGantt.value, true);
  void tenantMeetings.refresh();
}
</script>
