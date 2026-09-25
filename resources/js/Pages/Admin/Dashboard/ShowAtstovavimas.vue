<template>
  <OverviewPage
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

    <OverviewNumbers :numbers />

    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16" data-slot="atstovavimas-primary-section">
      <div class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <InstitutionsNeedingAttention data-tour="attention-card" :institutions="attention" @record="recordActivityFor" />
        <UpcomingMeetingsList :meetings="scopedUpcoming" :total="scopedUpcomingTotal" :href="route('meetings.index')" />
      </div>

      <aside class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <Deferred data="coordinators">
          <template #fallback>
            <CoordinatorSkeleton />
          </template>
          <CoordinatorCard :coordinators="coordinators ?? []" compact />
        </Deferred>
        <!-- No skeleton: most duty types have no reference files yet, and the block then never appears. -->
        <Deferred data="referenceDocuments">
          <template #fallback />
          <ReferenceDocumentTiles v-if="referenceDocuments?.length" :files="referenceDocuments" />
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

    <!-- FullscreenGanttModal first so dialogs opened from within it appear on top -->
    <FullscreenGanttModal
      :is-open="actions.showFullscreenGantt.value"
      gantt-type="user"
      :available-tenants="[]"
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
      :tenant-institutions="[]"
      :tenant-meetings="[]"
      :tenant-gaps="[]"
      :tenant-institution-names="{}"
      :tenant-institution-tenant="{}"
      :tenant-names
      @update:is-open="actions.showFullscreenGantt.value = $event"
      @create-meeting="actions.onGapCreateMeeting"
      @create-check-in="actions.onGapCreateCheckIn"
    />

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
import { ArrowRight, Eye } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import FullscreenGanttModal from './Components/FullscreenGanttModal.vue';
import CoordinatorSkeleton from './Components/CoordinatorSkeleton.vue';
import TenantScopeSelector from './Components/TenantScopeSelector.vue';
import TimelineGanttSkeleton from './Components/TimelineGanttSkeleton.vue';
import UserTimelineSection from './Components/UserTimelineSection.vue';
import { useAtstovavimasActions } from './Composables/useAtstovavimasActions';
import { useAtstovavimasData } from './Composables/useAtstovavimasData';
import { provideGanttSettings } from './Composables/useGanttSettings';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import type { AtstovavimasInstitution, AtstovavimasMeeting, AtstovavimasUser } from './types';
import {
  buildInstitutionNamesMap,
  buildInstitutionPeriodicityMap,
  buildInstitutionPublicMeetingsMap,
  buildInstitutionTenantMap,
  calculateInactivePeriods,
  extractDutyMembers,
} from './utils/ganttHelpers';

import { Button } from '@/Components/ui/button';
import { OverviewStatusList } from '@/Components/Patterns';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { type FileableFileItem, ReferenceDocumentTiles } from '@/Components/Files';
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

const props = defineProps<{
  user: AtstovavimasUser;
  userInstitutions: App.Entities.Institution[];
  relatedInstitutions?: AtstovavimasInstitution[];
  mayHaveRelatedInstitutions?: boolean;
  canViewTenantOverview: boolean;
  openTasksCount: number;
  coordinators?: HomeCoordinator[];
  referenceDocuments?: FileableFileItem[];
  upcomingMeetings: { items: HomeMeeting[]; total: number };
  followedInstitutions?: HomeFollowedInstitutions;
}>();

const actionWindow = useActionWindow();
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const relatedInstitutions = computed<AtstovavimasInstitution[]>(() =>
  (props.relatedInstitutions ?? []).map(institution => ({ ...institution, id: String(institution.id) })),
);

// Getter keeps reactivity when an Inertia partial reload replaces the prop.
const atstovavimasData = useAtstovavimasData(() => props.user);
const timelineFilters = provideTimelineFilters(atstovavimasData.institutions.value, []);
const actions = useAtstovavimasActions(props.userInstitutions);

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

function meetingBelongsToSelectedUserInstitutions(meeting: AtstovavimasMeeting): boolean {
  if (meeting.institution_id) {
    return userScopedInstitutionIds.value.has(String(meeting.institution_id));
  }

  return meeting.institutions?.some(institution => userScopedInstitutionIds.value.has(String(institution.id))) ?? false;
}

const userScopedMeetings = computed(() =>
  atstovavimasData.sortedMeetings.value.filter(meetingBelongsToSelectedUserInstitutions),
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

const userScopedGanttMeetings = computed(() =>
  atstovavimasData.allUserMeetings.value.filter(meeting =>
    userScopedInstitutionIds.value.has(String(meeting.institution_id)),
  ),
);

const userScopedGaps = computed(() =>
  atstovavimasData.userGaps.value.filter(gap => userScopedInstitutionIds.value.has(String(gap.institution_id))),
);

// --- Attention and numbers --------------------------------------------------------------------

const attention = computed<InstitutionActivityInsight[]>(() =>
  userScopedInstitutions.value
    .filter(institution => institution.activity_status?.requires_action)
    .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
    .map(institution => ({ id: String(institution.id), name: String(institution.name ?? ''), ...institution.activity_status })),
);

function countByStatus(status: 'overdue' | 'approaching'): number {
  return userScopedInstitutions.value.filter(institution => institution.activity_status?.status === status).length;
}

const numbers = computed<OverviewNumberItem[]>(() => [
  { key: 'overdue', label: $t('visak.overview.numbers.overdue'), value: countByStatus('overdue'), href: route('institutions.index', { activity_status: 'overdue' }), tone: 'danger' },
  { key: 'approaching', label: $t('visak.overview.numbers.approaching'), value: countByStatus('approaching'), href: route('institutions.index', { activity_status: 'approaching' }), tone: 'attention' },
  {
    key: 'incomplete_meetings',
    label: $t('visak.overview.numbers.incomplete_meetings'),
    value: userScopedMeetings.value.filter(meeting => meeting.completion_status === 'incomplete').length,
    href: route('meetings.index', { completion_status: 'incomplete' }),
    tone: 'attention',
  },
  { key: 'open_tasks', label: $t('visak.overview.numbers.open_tasks'), value: props.openTasksCount, href: route('userTasks') },
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

const userInstitutionNames = computed(() => buildInstitutionNamesMap(userScopedInstitutions.value));
const userInstitutionTenant = computed(() => buildInstitutionTenantMap(userScopedInstitutions.value));
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
