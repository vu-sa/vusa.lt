<template>
  <OverviewPage
    class="max-md:gap-12"
    eyebrow="ViSAK"
    :title="$t('visak.overview.title')"
    :head-title="`ViSAK · ${$t('visak.overview.title')}`"
    :lead="$t('visak.overview.lead')"
  >
    <template v-if="timelineFilters.availableTenantsUser.value.length > 1 || canViewTenantOverview" #actions>
      <TenantScopeSelector
        v-if="timelineFilters.availableTenantsUser.value.length > 1"
        compact
        :tenants="timelineFilters.availableTenantsUser.value"
        :selected-tenants="timelineFilters.userTenantFilter.value"
        @update:selected-tenants="timelineFilters.setUserTenantFilter"
      />
      <Button v-if="canViewTenantOverview" as-child variant="ghost" size="sm" class="pointer-coarse:h-11">
        <Link :href="route('dashboard.atstovavimas.padaliniai')">
          {{ $t('visak.overview.tenant_link') }}
          <ArrowRight aria-hidden="true" />
        </Link>
      </Button>
    </template>

    <OverviewNumbers v-if="!showNumbersBesideCoordinators" :numbers class="max-md:gap-y-6" />

    <div v-show="!isPrimarySectionEmpty" class="grid gap-12 md:gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16" data-slot="atstovavimas-primary-section">
      <div class="flex min-w-0 flex-col gap-12 md:gap-10 lg:gap-14">
        <OverviewNumbers v-if="showNumbersBesideCoordinators" :numbers two-columns class="max-md:gap-y-6 lg:mt-8" />
        <InstitutionsNeedingAttention data-tour="attention-card" :institutions="attention" @record="recordActivityFor" />
        <UpcomingMeetingsList :meetings="scopedUpcoming" :total="scopedUpcomingTotal" :href="route('meetings.index')" />
      </div>

      <aside class="flex min-w-0 flex-col gap-12 md:gap-10 lg:gap-14">
        <Deferred data="coordinators">
          <template #fallback>
            <CoordinatorSkeleton />
          </template>
          <CoordinatorCard :coordinators="coordinators ?? []" compact />
        </Deferred>
      </aside>
    </div>

    <!-- The timeline is a workbench: it renders only once it is near the viewport, and never on a phone. -->
    <section
      v-if="isAtLeastMd"
      data-tour="visak-timeline"
      :aria-label="$t('visak.overview.timeline.title')"
    >
      <div ref="timelineAnchor" class="min-h-64">
        <TimelineGanttSkeleton v-if="!timelineVisible" />
        <UserTimelineSection
          v-else
          :institutions="userScopedInstitutions"
          :meetings="userScopedGanttMeetings"
          :gaps="userScopedGaps"
          :institution-names="userInstitutionNames"
          :tenant-names
          :institution-has-public-meetings="userInstitutionHasPublicMeetings"
          :institution-periodicity="userInstitutionPeriodicity"
          :duty-members="userDutyMembers"
          :inactive-periods="userInactivePeriods"
          :related-institutions
          :may-have-related-institutions="props.mayHaveRelatedInstitutions"
          :activity-requests="props.activityRequests ?? []"
          :institution-has-activity
          :loading-segments="institutionMeetings.loadingSegments.value"
          :meetings-loading="meetingsLoadingVisible"
          :agenda-loader="institutionMeetings.loadAgenda"
          @create-meeting="actions.onGapCreateMeeting"
          @create-check-in="actions.onGapCreateCheckIn"
          @range-changed="(min: Date, max: Date) => institutionMeetings.ensureRange(min, max)"
        />
      </div>
    </section>
    <p v-else class="border-t border-border pt-3 text-sm text-muted-foreground" data-slot="atstovavimas-timeline-phone-note">
      {{ $t('visak.overview.timeline.phone_note') }}
      <Link :href="route('meetings.index')" class="text-foreground underline underline-offset-4">
        {{ $t('visak.overview.timeline.phone_link') }}
      </Link>
    </p>

    <Deferred data="followedInstitutions">
      <template #fallback />
      <FollowedInstitutionsList v-if="followedInstitutions?.total" :followed="followedInstitutions" />
      <OverviewSection
        v-else-if="followedInstitutions"
        :title="$t('Sekamos institucijos')"
        :empty-text="$t('Dar nieko neseki')"
        :icon="Eye"
        empty
      />
    </Deferred>

    <OverviewStatusList />

    <AddCheckInDialog
      v-if="actions.showCreateCheckIn.value"
      :open="!!actions.showCreateCheckIn.value"
      :institution-id="actions.showCreateCheckIn.value.institutionId!"
      :institution-name="checkInInstitutionName"
      :initial-start-date="actions.showCreateCheckIn.value.startDate"
      :initial-end-date="actions.showCreateCheckIn.value.endDate"
      :reload-props="['user', 'userInstitutions']"
      @close="actions.showCreateCheckIn.value = null"
    />
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, Link } from '@inertiajs/vue3';
import { useIntersectionObserver, useMediaQuery } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, Eye } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

import { provideGanttSettings } from '@/Components/Graphs/composables/useGanttSettings';
import { Button } from '@/Components/ui/button';
import { OverviewStatusList } from '@/Components/Patterns';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import CoordinatorCard from '@/Components/Home/CoordinatorCard.vue';
import UpcomingMeetingsList from '@/Components/Home/UpcomingMeetingsList.vue';
import FollowedInstitutionsList from '@/Components/Home/FollowedInstitutionsList.vue';
import type { HomeCoordinator, HomeFollowedInstitutions, HomeMeeting, InstitutionActivityInsight } from '@/Components/Home/types';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useIsMobile } from '@/Composables/useIsMobile';
import { useProductTour } from '@/Composables/useProductTour';
import { provideTour } from '@/Composables/useTourProvider';

import {
  buildInstitutionNamesMap,
  buildInstitutionPeriodicityMap,
  buildInstitutionPublicMeetingsMap,
  calculateInactivePeriods,
  extractDutyMembers,
} from './utils/ganttHelpers';
import type { AtstovavimasInstitution, AtstovavimasUser, GanttActivityRequest } from './types';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import { useAtstovavimasData } from './Composables/useAtstovavimasData';
import { useAtstovavimasActions } from './Composables/useAtstovavimasActions';
import { useInstitutionMeetings } from './Composables/useTenantMeetings';
import UserTimelineSection from './Components/UserTimelineSection.vue';
import TimelineGanttSkeleton from './Components/TimelineGanttSkeleton.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import CoordinatorSkeleton from './Components/CoordinatorSkeleton.vue';

const props = defineProps<{
  user: AtstovavimasUser;
  userInstitutions: AtstovavimasInstitution[];
  /** Meetings fetched first; the Gantt asks for more as it scrolls. */
  initialMeetingsWindow: { from: string; to: string };
  relatedInstitutions?: AtstovavimasInstitution[];
  mayHaveRelatedInstitutions?: boolean;
  canViewTenantOverview: boolean;
  openTasksCount: number;
  coordinators?: HomeCoordinator[];
  upcomingMeetings: { items: HomeMeeting[]; total: number };
  followedInstitutions?: HomeFollowedInstitutions;
  /** Deferred: open activity requests over the user's own bodies */
  activityRequests?: GanttActivityRequest[];
}>();

const actionWindow = useActionWindow();
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const relatedInstitutions = computed<AtstovavimasInstitution[]>(() =>
  (props.relatedInstitutions ?? []).map(institution => ({ ...institution, id: String(institution.id) })),
);

// Getter keeps reactivity when an Inertia partial reload replaces the prop.
const atstovavimasData = useAtstovavimasData(() => props.user, () => props.userInstitutions);
const timelineFilters = provideTimelineFilters(atstovavimasData.institutions.value, []);
const actions = useAtstovavimasActions(props.userInstitutions as unknown as App.Entities.Institution[]);

provideGanttSettings();

const userScopedInstitutions = computed(() => {
  const selectedTenantIds = new Set(timelineFilters.userTenantFilter.value);

  return atstovavimasData.institutions.value.filter(institution =>
    selectedTenantIds.has(String(institution.tenant?.id)),
  );
});

const userScopedInstitutionIds = computed(() =>
  new Set(userScopedInstitutions.value.map(institution => String(institution.id))),
);

// Followed institutions sit outside the tenant selector, which only lists the user's duty tenants.
const scopedUpcoming = computed<HomeMeeting[]>(() => {
  const selectedTenantIds = new Set(timelineFilters.userTenantFilter.value);

  return props.upcomingMeetings.items.filter(meeting =>
    meeting.is_followed || selectedTenantIds.has(String(meeting.tenant_id)),
  );
});

// Only the unfiltered total is known beyond the capped payload.
const scopedUpcomingTotal = computed(() =>
  scopedUpcoming.value.length === props.upcomingMeetings.items.length ? props.upcomingMeetings.total : scopedUpcoming.value.length,
);

// Related institutions joined only once their filter is on; the store resets when the set changes.
const shownRelatedInstitutionIds = computed(() =>
  timelineFilters.showRelatedInstitutionsUser.value ? relatedInstitutions.value.map(institution => institution.id) : [],
);

const meetingInstitutionIds = computed(() => [
  ...new Set([...atstovavimasData.institutions.value.map(institution => String(institution.id)), ...shownRelatedInstitutionIds.value]),
].sort());

const institutionMeetings = useInstitutionMeetings(() => meetingInstitutionIds.value);

const userScopedGanttMeetings = computed(() => {
  const shown = new Set([...userScopedInstitutionIds.value, ...shownRelatedInstitutionIds.value]);

  return institutionMeetings.meetings.value.filter(meeting => shown.has(String(meeting.institution_id)));
});

const institutionHasActivity = computed(() => Object.fromEntries(
  [...atstovavimasData.institutions.value, ...relatedInstitutions.value].map(institution => [
    String(institution.id),
    Boolean(institution.last_meeting_date || institution.next_meeting_date || institution.check_ins?.length),
  ]),
));

const userScopedGaps = computed(() =>
  atstovavimasData.userGaps.value.filter(gap => userScopedInstitutionIds.value.has(String(gap.institution_id))),
);

// --- Attention and numbers --------------------------------------------------------------------

const attention = computed<InstitutionActivityInsight[]>(() =>
  userScopedInstitutions.value
    .filter(institution => institution.activity_status?.requires_action)
    .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
    .map(institution => ({
      id: String(institution.id),
      name: String(institution.name ?? ''),
      is_secretary: institution.is_secretary === true || institution.is_administered === true,
      is_administered: institution.is_secretary === true || institution.is_administered === true,
      ...institution.activity_status,
    })),
);

const showNumbersBesideCoordinators = computed(() =>
  attention.value.length === 0 && scopedUpcoming.value.length === 0 && (props.coordinators?.length ?? 0) > 0,
);

const isPrimarySectionEmpty = computed(() =>
  attention.value.length === 0
  && scopedUpcoming.value.length === 0
  && props.coordinators?.length === 0,
);

function countByStatus(status: 'overdue' | 'approaching' | 'recent_check_in'): number {
  return userScopedInstitutions.value.filter(institution => institution.activity_status?.status === status).length;
}

const numbers = computed<OverviewNumberItem[]>(() => [
  { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: countByStatus('overdue'), href: route('institutions.index', { activity_status: 'overdue' }), tone: 'danger' },
  { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: countByStatus('approaching'), href: route('institutions.index', { activity_status: 'approaching' }), tone: 'attention' },
  { key: 'recent_check_in', label: $t('activity_requests.recent_check_in'), value: countByStatus('recent_check_in'), href: route('institutions.index', { activity_status: 'recent_check_in' }), tone: 'info' },
  {
    key: 'incomplete_meetings',
    label: $t('visak.overview.numbers.incomplete_meetings'),
    value: userScopedInstitutions.value.reduce((sum, institution) => sum + (institution.incomplete_meetings_count ?? 0), 0),
    href: route('meetings.index', { completion_status: 'incomplete' }),
    tone: 'attention',
  },
  { key: 'open_tasks', label: $t('visak.overview.numbers.open_tasks'), value: props.openTasksCount, href: route('tasks.index') },
]);

function recordActivityFor(institution: InstitutionActivityInsight): void {
  actionWindow.open({ flow: 'institution.report', institution: { id: institution.id, name: institution.name } });
}

// --- The timeline workbench -------------------------------------------------------------------

const timelineAnchor = ref<HTMLElement | null>(null);
const timelineVisible = ref(false);
useIntersectionObserver(timelineAnchor, ([entry]) => {
  if (entry?.isIntersecting) {
    timelineVisible.value = true;
  }
}, { rootMargin: '200px' });

function loadInitialMeetingWindow(): void {
  institutionMeetings.ensureRange(
    new Date(`${props.initialMeetingsWindow.from}T00:00:00`),
    new Date(`${props.initialMeetingsWindow.to}T00:00:00`),
    { pad: false },
  );
}

// Fetched on load rather than on scroll: the request is small, and the Gantt is ready when reached.
watch(isAtLeastMd, (wide) => {
  if (wide) loadInitialMeetingWindow();
}, { immediate: true });

watch(meetingInstitutionIds, (ids, previous) => {
  if (ids.join(',') === previous.join(',')) return;
  institutionMeetings.reset();
  if (isAtLeastMd.value) loadInitialMeetingWindow();
});

// A meeting created or edited from the ActionWindow must show without a page reload.
watch(actionWindow.isOpen, (open, wasOpen) => {
  if (wasOpen && !open && isAtLeastMd.value) institutionMeetings.refresh();
});

// Only shown after 300ms so a fast response never flashes it.
const meetingsLoadingVisible = ref(false);
let meetingsLoadingTimeout: ReturnType<typeof setTimeout> | null = null;
watch(institutionMeetings.isFetching, (loading) => {
  if (meetingsLoadingTimeout) clearTimeout(meetingsLoadingTimeout);
  if (loading) {
    meetingsLoadingTimeout = setTimeout(() => {
      meetingsLoadingVisible.value = true;
    }, 300);
  }
  else {
    meetingsLoadingVisible.value = false;
  }
});

const userInstitutionNames = computed(() => buildInstitutionNamesMap(userScopedInstitutions.value));
const userInstitutionHasPublicMeetings = computed(() => buildInstitutionPublicMeetingsMap(userScopedInstitutions.value));
const userInstitutionPeriodicity = computed(() => buildInstitutionPeriodicityMap(userScopedInstitutions.value));
const userDutyMembers = computed(() => extractDutyMembers(userScopedInstitutions.value));
const userInactivePeriods = computed(() => calculateInactivePeriods(userScopedInstitutions.value, userDutyMembers.value));

const tenantNames = computed(() => {
  const names: Record<string, string> = {};

  [...atstovavimasData.institutions.value, ...relatedInstitutions.value].forEach((institution) => {
    if (institution.tenant?.id && institution.tenant.shortname) {
      names[String(institution.tenant.id)] = institution.tenant.shortname;
    }
  });

  return names;
});

const checkInInstitutionName = computed(() => {
  const institutionId = actions.showCreateCheckIn.value?.institutionId;

  return institutionId ? userInstitutionNames.value?.[institutionId] : undefined;
});

// --- Tour -------------------------------------------------------------------------------------

const isMobile = useIsMobile();

const { startTour, startTourIfNew } = useProductTour({
  tourId: 'atstovavimas-overview-v1',
  // Steps whose anchor is absent (an empty section, the timeline on a phone) are skipped.
  steps: () => [
    {
      popover: {
        title: $t('tutorials.atstovavimas_overview.welcome.title'),
        description: $t('tutorials.atstovavimas_overview.welcome.description'),
      },
    },
    {
      element: '[data-tour="attention-card"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.institutions_card.title'),
        description: $t('tutorials.atstovavimas_overview.institutions_card.description'),
      },
    },
    {
      element: '[data-tour="meetings-card"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.meetings_card.title'),
        description: $t('tutorials.atstovavimas_overview.meetings_card.description'),
      },
    },
    {
      element: isMobile.value ? '[data-tour="action-create-mobile"]' : '[data-tour="action-create"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.create_meeting.title'),
        description: $t('tutorials.atstovavimas_overview.create_meeting.description'),
      },
    },
    {
      element: '[data-tour="visak-timeline"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.timeline.title'),
        description: $t('tutorials.atstovavimas_overview.timeline.description'),
      },
    },
    {
      popover: {
        title: $t('tutorials.atstovavimas_overview.complete.title'),
        description: $t('tutorials.atstovavimas_overview.complete.description'),
      },
    },
  ],
});

provideTour(startTour);

const TOUR_START_DELAY_MS = 1000;

onMounted(() => {
  setTimeout(() => startTourIfNew(), TOUR_START_DELAY_MS);
});
</script>
