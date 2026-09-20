<template>
  <OverviewPage
    eyebrow="ViSAK"
    :title="$t('visak.overview.title')"
    :head-title="`ViSAK · ${$t('visak.overview.title')}`"
    :lead="$t('visak.overview.lead')"
  >
    <template v-if="showScopeControls" #actions>
      <OverviewScopeSwitch
        v-if="canViewTenantOverview"
        v-model="scope"
        :options="scopeOptions"
        :label="$t('visak.overview.scope.label')"
      />
      <TenantScopeSelector
        v-if="scope === 'user' && timelineFilters.availableTenantsUser.value.length > 0"
        compact
        :tenants="timelineFilters.availableTenantsUser.value"
        :selected-tenants="timelineFilters.userTenantFilter.value"
        @update:selected-tenants="timelineFilters.setUserTenantFilter"
      />
      <TenantScopeSelector
        v-else-if="scope === 'tenant' && canViewTenantOverview"
        compact
        :tenants="props.availableTenants"
        :selected-tenants="timelineFilters.selectedTenantForGantt.value"
        @update:selected-tenants="timelineFilters.setSelectedTenants"
      />
    </template>

    <InstitutionsNeedingAttention
      :institutions="attention"
      :title="scope === 'tenant' ? $t('visak.institution_summary.needs_attention') : undefined"
      @record="recordMeetingFor"
    />

    <OverviewNumbers v-if="numbersReady" :numbers />
    <Skeleton v-else class="h-20 w-full" />

    <OverviewSection
      v-if="scope === 'tenant'"
      :title="$t('visak.overview.trend.title')"
    >
      <OverviewChart :summary="trendSummary">
        <InstitutionStatusTrendChart
          :data="statusHistory.data.value ?? []"
          :days="historyDays"
          :loading="statusHistory.isFetching.value"
          @update:days="historyDays = $event"
        />
      </OverviewChart>
    </OverviewSection>

    <UpcomingMeetingsList
      v-if="scope === 'user'"
      :meetings="upcomingMeetings"
      :href="route('meetings.index')"
    />

    <!-- The timeline is a workbench: it renders only once it is near the viewport, and never on a phone. -->
    <section
      v-if="isAtLeastMd"
      class="border-t border-border pt-3"
      :aria-label="$t('visak.overview.timeline.title')"
    >
      <div ref="timelineAnchor" class="min-h-64">
        <TimelineGanttSkeleton v-if="!timelineVisible || !timelineReady" />
        <template v-else-if="scope === 'user'">
          <UserTimelineSection
            :institutions="userScopedInstitutions"
            :meetings="userScopedGanttMeetings"
            :gaps="userScopedGaps"
            :institution-names="userInstitutionNames"
            :tenant-names
            :institution-tenant="userInstitutionTenant"
            :institution-has-public-meetings="userInstitutionHasPublicMeetings"
            :institution-periodicity="userInstitutionPeriodicity"
            :duty-members="userDutyMembers"
            :inactive-periods="userInactivePeriods"
            :related-institutions
            :may-have-related-institutions="props.mayHaveRelatedInstitutions"
            @create-meeting="actions.onGapCreateMeeting"
            @create-check-in="actions.onGapCreateCheckIn"
            @fullscreen="actions.onGanttFullscreen('user')"
          />
        </template>
        <TenantTimelineSection
          v-else
          :available-tenants="props.availableTenants"
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
          :representative-activity="representativeActivityData"
          :show-tenant-selector="false"
          @create-meeting="actions.onGapCreateMeeting"
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

    <!-- FullscreenGanttModal first so dialogs opened from within it appear on top -->
    <FullscreenGanttModal
      :is-open="actions.showFullscreenGantt.value"
      :gantt-type="actions.fullscreenGanttType.value"
      :available-tenants="props.availableTenants"
      :user-institutions="userScopedInstitutions"
      :user-meetings="userScopedGanttMeetings"
      :user-gaps="userScopedGaps"
      :user-institution-names
      :user-institution-tenant
      :user-institution-has-public-meetings
      :user-institution-periodicity
      :user-duty-members
      :user-inactive-periods
      :user-related-institutions="relatedInstitutions"
      :may-have-related-institutions="props.mayHaveRelatedInstitutions"
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
      @create-meeting="actions.onGapCreateMeeting"
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
      :reload-tenant-ids="timelineFilters.selectedTenantForGantt.value"
      :reload-props="['user', 'userInstitutions']"
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
import InstitutionStatusTrendChart from './Components/InstitutionStatusTrendChart.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import TenantTimelineSection from './Components/TenantTimelineSection.vue';
import TimelineGanttSkeleton from './Components/TimelineGanttSkeleton.vue';
import UserTimelineSection from './Components/UserTimelineSection.vue';
import { useAtstovavimasActions } from './Composables/useAtstovavimasActions';
import { useAtstovavimasData } from './Composables/useAtstovavimasData';
import { useGanttChartData } from './Composables/useGanttChartData';
import { provideGanttSettings } from './Composables/useGanttSettings';
import { summarizeStatusTrend } from './Composables/statusTrend';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import { useTenantMeetings } from './Composables/useTenantMeetings';
import { useTenantStatusHistory } from './Composables/useTenantStatusHistory';
import { useTenantTimelineData } from './Composables/useTenantTimelineData';
import type {
  AtstovavimasInstitution,
  AtstovavimasMeeting,
  AtstovavimasTenant,
  AtstovavimasUser,
  InstitutionStatusSummaryData,
} from './types';

import { Skeleton } from '@/Components/ui/skeleton';
import { OverviewSection } from '@/Components/Patterns';
import OverviewScopeSwitch from '@/Components/Overview/OverviewScopeSwitch.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewChart from '@/Components/Overview/OverviewChart.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import UpcomingMeetingsList from '@/Components/Home/UpcomingMeetingsList.vue';
import type { HomeMeeting, InstitutionActivityInsight } from '@/Components/Home/types';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import { useActionWindow } from '@/Composables/useActionWindow';

const props = defineProps<{
  user: AtstovavimasUser;
  userInstitutions: App.Entities.Institution[];
  relatedInstitutions?: AtstovavimasInstitution[];
  mayHaveRelatedInstitutions?: boolean;
  availableTenants: AtstovavimasTenant[];
  openTasksCount: number;
}>();

type Scope = 'user' | 'tenant';

// Mirrors AtstovavimasSettings::getVisibleTenantIds(), materialised as `availableTenants`.
const canViewTenantOverview = computed(() => props.availableTenants.length > 0);

const scopeOptions = computed(() => [
  { value: 'user', label: $t('visak.overview.scope.mine') },
  { value: 'tenant', label: $t('visak.overview.scope.tenant') },
]);

// The scope lives in the URL; `?tab=tenant` is the old bookmark for the same thing.
function initialScope(): Scope {
  const params = new URLSearchParams(window.location.search);
  const requested = params.get('scope') ?? params.get('tab');

  return requested === 'tenant' && canViewTenantOverview.value ? 'tenant' : 'user';
}

const scope = ref<Scope>(initialScope());

watch(scope, (next) => {
  const url = new URL(window.location.href);
  url.searchParams.delete('tab');
  if (next === 'user') {
    url.searchParams.delete('scope');
  }
  else {
    url.searchParams.set('scope', next);
  }
  window.history.replaceState({}, '', url.toString());
});

const actionWindow = useActionWindow();
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const relatedInstitutions = computed<AtstovavimasInstitution[]>(() =>
  (props.relatedInstitutions ?? []).map(institution => ({ ...institution, id: String(institution.id) })),
);

// Getter keeps reactivity when an Inertia partial reload replaces the prop.
const atstovavimasData = useAtstovavimasData(() => props.user);
const timelineFilters = provideTimelineFilters(atstovavimasData.institutions.value, props.availableTenants);
const actions = useAtstovavimasActions(props.userInstitutions);
const tenantTimelineData = useTenantTimelineData();
const tenantMeetings = useTenantMeetings(() => timelineFilters.selectedTenantForGantt.value);
const tenantInstitutionsData = computed(() => tenantTimelineData.data.value?.institutions ?? []);
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
const representativeActivityData = computed(() => tenantTimelineData.data.value?.representative_activity);
const ganttData = useGanttChartData(tenantInstitutionsData, props.availableTenants, tenantMeetings.meetings);

provideGanttSettings();

const showScopeControls = computed(() =>
  canViewTenantOverview.value || timelineFilters.availableTenantsUser.value.length > 0,
);

// --- Personal scope ---------------------------------------------------------------------------

const userScopedInstitutions = computed(() => {
  const selectedTenantIds = new Set(timelineFilters.userTenantFilter.value);

  return atstovavimasData.institutions.value.filter(institution =>
    selectedTenantIds.has(String(institution.tenant?.id)),
  );
});

const userScopedInstitutionIds = computed(() =>
  new Set(userScopedInstitutions.value.map(institution => String(institution.id))),
);

function meetingBelongsToSelectedUserInstitutions(meeting: AtstovavimasMeeting): boolean {
  if (meeting.institution_id) {
    return userScopedInstitutionIds.value.has(String(meeting.institution_id));
  }

  return meeting.institutions?.some(institution => userScopedInstitutionIds.value.has(String(institution.id))) ?? false;
}

const userScopedMeetings = computed(() =>
  atstovavimasData.sortedMeetings.value.filter(meetingBelongsToSelectedUserInstitutions),
);

const upcomingMeetings = computed<HomeMeeting[]>(() =>
  atstovavimasData.upcomingMeetings.value
    .filter(meetingBelongsToSelectedUserInstitutions)
    .slice(0, 5)
    .map(meeting => ({
      id: String(meeting.id),
      title: meeting.title ?? '',
      start_time: meeting.start_time,
      institution_name: meeting.institutions?.[0]?.name ?? null,
    })),
);

const userScopedGanttMeetings = computed(() =>
  atstovavimasData.allUserMeetings.value.filter(meeting =>
    userScopedInstitutionIds.value.has(String(meeting.institution_id)),
  ),
);

const userScopedGaps = computed(() =>
  atstovavimasData.userGaps.value.filter(gap => userScopedInstitutionIds.value.has(String(gap.institution_id))),
);

// --- Attention and numbers --------------------------------------------------------------------

function toInsight(institution: AtstovavimasInstitution): InstitutionActivityInsight {
  return { id: String(institution.id), name: String(institution.name ?? ''), ...institution.activity_status };
}

const attention = computed<InstitutionActivityInsight[]>(() => {
  const institutions: AtstovavimasInstitution[] = scope.value === 'tenant'
    ? tenantInstitutionsData.value
    : userScopedInstitutions.value;

  return institutions
    .filter(institution => institution.activity_status?.requires_action)
    .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
    .map(toInsight);
});

function countByStatus(status: 'overdue' | 'approaching'): number {
  return userScopedInstitutions.value.filter(institution => institution.activity_status?.status === status).length;
}

// Tenant numbers arrive with the timeline request; until then a placeholder, not a wrong zero.
const numbersReady = computed(() => scope.value === 'user' || tenantTimelineData.loaded.value);

const numbers = computed<OverviewNumberItem[]>(() => {
  const openTasks: OverviewNumberItem = {
    key: 'open_tasks',
    label: $t('visak.overview.numbers.open_tasks'),
    value: props.openTasksCount,
    href: route('userTasks'),
  };

  if (scope.value === 'tenant') {
    return [
      { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: institutionSummary.value.overdue, href: route('institutions.index'), tone: 'danger' },
      { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: institutionSummary.value.approaching, href: route('institutions.index'), tone: 'attention' },
      { key: 'no_activity', label: $t('visak.institution_summary.no_activity'), value: institutionSummary.value.no_activity, href: route('institutions.index'), tone: 'neutral' },
      openTasks,
    ];
  }

  return [
    { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: countByStatus('overdue'), href: route('institutions.index'), tone: 'danger' },
    { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: countByStatus('approaching'), href: route('institutions.index'), tone: 'attention' },
    {
      key: 'incomplete_meetings',
      label: $t('visak.overview.numbers.incomplete_meetings'),
      value: userScopedMeetings.value.filter(meeting => meeting.completion_status === 'incomplete').length,
      href: route('meetings.index', { completion_status: 'incomplete' }),
      tone: 'attention',
    },
    openTasks,
  ];
});

function recordMeetingFor(institution: InstitutionActivityInsight): void {
  actionWindow.open({ flow: 'meeting.create', institution: { id: institution.id, name: institution.name } });
}

// --- The trend chart (tenant scope) -----------------------------------------------------------

const historyDays = ref(90);
const statusHistory = useTenantStatusHistory();

watch(
  () => [scope.value, timelineFilters.selectedTenantForGantt.value.join(','), historyDays.value] as const,
  ([current]) => {
    if (current === 'tenant' && timelineFilters.selectedTenantForGantt.value.length > 0) {
      statusHistory.load(timelineFilters.selectedTenantForGantt.value, historyDays.value);
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

// Personal data is already in the page; the tenant scope needs its request first.
const timelineReady = computed(() => scope.value === 'user' || tenantTimelineData.loaded.value);

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

watch(tenantTimelineData.isFetching, (loading) => {
  timelineFilters.tenantInstitutionsLoading.value = loading;
}, { immediate: true });

watch(tenantTimelineData.loaded, (loaded) => {
  timelineFilters.tenantInstitutionsLoaded.value = loaded;
}, { immediate: true });

function loadTenantData(): void {
  tenantTimelineData.load(timelineFilters.selectedTenantForGantt.value);
  loadInitialMeetingWindow();
}

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

watch(scope, (next) => {
  if (next === 'tenant' && canViewTenantOverview.value) {
    loadTenantData();
  }
});

onMounted(() => {
  if (scope.value === 'tenant' && canViewTenantOverview.value) {
    loadTenantData();
  }
});

watch(() => timelineFilters.selectedTenantForGantt.value, (newTenants, oldTenants) => {
  if (scope.value === 'tenant' && newTenants.length > 0) {
    tenantTimelineData.load(newTenants);
    if (newTenants.join(',') !== (oldTenants ?? []).join(',')) {
      tenantMeetings.reset();
      loadInitialMeetingWindow();
    }
  }
}, { deep: true });

// --- Gantt lookup maps ------------------------------------------------------------------------

const userInstitutionNames = computed(() => ganttData.getInstitutionNames(userScopedInstitutions.value));
const userInstitutionTenant = computed(() => ganttData.getInstitutionTenant(userScopedInstitutions.value));
const userInstitutionHasPublicMeetings = computed(() => ganttData.getInstitutionHasPublicMeetings(userScopedInstitutions.value));
const userInstitutionPeriodicity = computed(() => ganttData.getInstitutionPeriodicity(userScopedInstitutions.value));
const userDutyMembers = computed(() => ganttData.getDutyMembersFromInstitutions(userScopedInstitutions.value));
const userInactivePeriods = computed(() => ganttData.getInactivePeriodsFromInstitutions(userScopedInstitutions.value));

const tenantNames = computed(() => {
  const names = { ...ganttData.getTenantNames() };

  [...atstovavimasData.institutions.value, ...relatedInstitutions.value].forEach((institution) => {
    if (institution.tenant?.id && institution.tenant.shortname) {
      names[String(institution.tenant.id)] = institution.tenant.shortname;
    }
  });

  return names;
});

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
  if (!institutionId) {
    return undefined;
  }

  return userInstitutionNames.value?.[institutionId] ?? tenantInstitutionNames.value?.[institutionId] ?? undefined;
});

function handleCheckInDialogClose(): void {
  actions.showCreateCheckIn.value = null;
  if (scope.value === 'tenant') {
    tenantTimelineData.load(timelineFilters.selectedTenantForGantt.value, true);
    void tenantMeetings.refresh();
  }
}
</script>
