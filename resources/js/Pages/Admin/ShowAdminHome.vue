<template>
  <OverviewPage :title="$t('Mano VU SA')">
    <template #hero>
      <HomeHero :greeting :image="heroImage" :summary="taskSummary" />
    </template>

    <AccessChangeBand v-if="accessChanges.length > 0" :changes="accessChanges" />

    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16" data-slot="home-primary-section">
      <div class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <AttentionQueue
          :tasks="visibleTasks"
          :stats="taskStats"
          :remaining-count="Math.max(0, taskStats.total - visibleTasks.length)"
          :more-href="route('userTasks')"
        />

        <Deferred v-if="hasAtstovavimas" :data="deferredProps">
          <template #fallback>
            <CollectionSkeleton :rows="3" />
          </template>
          <InstitutionsNeedingAttention
            :institutions="institutionsNeedingAttention ?? []"
            @record="recordActivityFor"
          />
        </Deferred>

        <ReservationDraftSummary v-if="reservationDraft" :draft="reservationDraft" variant="home" />
      </div>

      <aside class="min-w-0">
        <CreateShortcuts />
      </aside>
    </div>

    <QuickAccess :registration-forms data-slot="home-destinations-section" />

    <div class="grid gap-10 border-t border-border pt-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16 lg:pt-14" data-slot="home-secondary-section">
      <div class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <UpcomingMeetingsList
          v-if="hasAtstovavimas"
          :meetings="upcomingMeetings"
          :total="upcomingMeetingsTotal"
          :href="route('dashboard.atstovavimas')"
        />
        <OverviewStatusList />
      </div>

      <aside class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <Deferred :data="deferredProps">
          <template #fallback>
            <CollectionSkeleton :rows="3" />
          </template>
          <FollowedInstitutionsList
            v-if="followedInstitutions?.total"
            :followed="followedInstitutions"
          />
          <RecentlyEditedList :records="recentlyEdited ?? []" />
          <SiteContentLists
            :events="upcomingCalendarEvents ?? []"
            :news="latestNews ?? []"
          />
        </Deferred>
      </aside>
    </div>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';
import type { DriveStep } from 'driver.js';

import AccessChangeBand from '@/Components/Home/AccessChangeBand.vue';
import AttentionQueue from '@/Components/Home/AttentionQueue.vue';
import CreateShortcuts from '@/Components/Home/CreateShortcuts.vue';
import QuickAccess from '@/Components/Home/QuickAccess.vue';
import FollowedInstitutionsList from '@/Components/Home/FollowedInstitutionsList.vue';
import HomeHero from '@/Components/Home/HomeHero.vue';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import RecentlyEditedList from '@/Components/Home/RecentlyEditedList.vue';
import SiteContentLists from '@/Components/Home/SiteContentLists.vue';
import UpcomingMeetingsList from '@/Components/Home/UpcomingMeetingsList.vue';
import type {
  HomeAccessChange,
  HomeFollowedInstitutions,
  HomeHeroImage,
  HomeMeeting,
  HomeNewsPreview,
  HomeRecentRecord,
  HomeRegistrationForm,
  HomeTask,
  InstitutionActivityInsight,
} from '@/Components/Home/types';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import type { ReservationDraftSummaryData } from '@/Components/Reservations/ReservationDraftRow.vue';
import ReservationDraftSummary from '@/Components/Reservations/ReservationDraftSummary.vue';
import { CollectionSkeleton, OverviewStatusList } from '@/Components/Patterns';
import { addressivize } from '@/Utils/String';
import { useProductTour } from '@/Composables/useProductTour';
import { useIsMobile } from '@/Composables/useIsMobile';
import { provideTour } from '@/Composables/useTourProvider';
import { useActionWindow, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';

interface TaskStats {
  total: number;
  overdue: number;
  dueSoon: number;
}

// The first response carries the queue and upcoming meetings; the rest arrives as one deferred group.
const props = defineProps<{
  accessChanges: HomeAccessChange[];
  /** Set when the URL asked for the ActionWindow (a reminder's answer buttons, U21). */
  actionWindowLaunch: { flow: 'meeting.create' | 'check-in'; institution: ActionWindowInstitutionRef } | null;
  taskStats: TaskStats;
  upcomingTasks: HomeTask[];
  upcomingMeetings: HomeMeeting[];
  upcomingMeetingsTotal: number;
  followedInstitutions?: HomeFollowedInstitutions;
  heroImage: HomeHeroImage | null;
  institutionsNeedingAttention?: InstitutionActivityInsight[];
  upcomingCalendarEvents?: App.Entities.Calendar[];
  latestNews?: HomeNewsPreview[];
  recentlyEdited?: HomeRecentRecord[];
  registrationForms: HomeRegistrationForm[];
  /** The user's unfinished reservation, so a started one is one tap away. */
  reservationDraft: ReservationDraftSummaryData | null;
}>();

const deferredProps = ['institutionsNeedingAttention', 'upcomingCalendarEvents', 'latestNews', 'recentlyEdited', 'followedInstitutions'];
const visibleTasks = computed(() => props.upcomingTasks.slice(0, 3));

const page = usePage<PageProps>();

const hasAtstovavimas = computed(() => Boolean(
  props.upcomingMeetings?.length
  || page.props.auth?.can?.create?.meeting
  || page.props.auth?.can?.index?.meeting,
));

const actionWindow = useActionWindow();

const isMobile = useIsMobile();

const tourStep = (key: string, anchor?: string): DriveStep => ({
  element: anchor ? `[data-tour="${anchor}"]` : undefined,
  popover: {
    title: $t(`tutorials.admin_home.${key}.title`),
    description: $t(`tutorials.admin_home.${key}.description`),
  },
});

// Phones and desktops have different chrome, so each gets its own walk through it.
const tourSteps = computed<DriveStep[]>(() => isMobile.value
  ? [
      tourStep('welcome'),
      tourStep('section_switcher', 'section-switcher'),
      tourStep('command_palette_mobile', 'command-palette-mobile'),
      tourStep('action_create', 'action-create-mobile'),
      tourStep('tasks_card', 'tasks-card'),
      tourStep('mobile_menu', 'mobile-menu'),
    ]
  : [
      tourStep('welcome'),
      tourStep('workspaces', 'workspace-picker'),
      tourStep('all_sections', 'all-sections'),
      tourStep('command_palette', 'command-palette'),
      tourStep('action_create', 'action-create'),
      tourStep('tasks_card', 'tasks-card'),
      tourStep('quick_actions', 'quick-actions'),
      tourStep('account_menu', 'account-menu'),
    ]);

const { startTour, startTourIfNew } = useProductTour({
  tourId: 'admin-welcome-v2',
  steps: () => tourSteps.value,
});

provideTour(startTour);

/** Lets the shell and the first cards settle before driver.js measures them. */
const TOUR_START_DELAY_MS = 1000;

// A reminder's answer buttons open Pradžia with the window already on the right flow (U21).
onMounted(() => {
  const launch = props.actionWindowLaunch;

  if (!launch) {
    setTimeout(() => startTourIfNew(), TOUR_START_DELAY_MS);
    return;
  }

  actionWindow.open({ flow: launch.flow, institution: launch.institution });

  // Strip the query so a refresh does not reopen a window the rep has already answered.
  const url = new URL(window.location.href);
  url.searchParams.delete('window');
  url.searchParams.delete('institution');
  window.history.replaceState(window.history.state, '', url);
});

// Lithuanian addresses the rep in the vocative: "Labas, Justinai".
const greeting = computed(() => {
  const firstName = page.props.auth?.user?.name?.split(' ')[0];
  if (!firstName) return $t('Labas');
  return `${$t('Labas')}, ${page.props.app.locale === 'lt' ? addressivize(firstName) : firstName}`;
});

const taskSummary = computed(() => {
  if (props.taskStats.total === 0) {
    return null;
  }

  const waiting = $t('home.summary.waiting', { count: String(props.taskStats.total) });

  return props.taskStats.overdue > 0
    ? `${waiting} · ${$t('home.summary.overdue', { count: String(props.taskStats.overdue) })}`
    : waiting;
});

const recordActivityFor = (institution: InstitutionActivityInsight) => {
  actionWindow.open({ flow: 'institution.report', institution: { id: institution.id, name: institution.name } });
};

</script>
