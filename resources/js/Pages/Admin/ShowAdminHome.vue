<template>
  <OverviewPage :title="$t('Mano VU SA')">
    <template #hero>
      <HomeHero :greeting :image="heroImage" :summary="taskSummary" />
    </template>

    <AccessChangeBand v-if="accessChanges.length > 0" :changes="accessChanges" />

    <FirstLoginChecklist
      v-if="onboardingChecklist"
      :checklist="onboardingChecklist"
      @record-meeting="actionWindow.open({ flow: 'meeting.create' })"
    />

    <!-- Tasks lead, destinations follow; on phones the rep order continues: meetings →
         institutions (home.md). Create shortcuts sit in the aside. -->
    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16">
      <div class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <AttentionQueue
          :tasks="visibleTasks"
          :stats="taskStats"
          :remaining-count="Math.max(0, taskStats.total - visibleTasks.length)"
          :more-href="route('userTasks')"
        />
        <QuickAccess :registration-forms />
        <UpcomingMeetingsList
          v-if="hasAtstovavimas"
          :meetings="upcomingMeetings"
          :href="route('dashboard.atstovavimas')"
        />
        <Deferred :data="deferredProps">
          <template #fallback>
            <CollectionSkeleton :rows="3" />
          </template>
          <InstitutionsNeedingAttention
            v-if="hasAtstovavimas"
            :institutions="institutionsNeedingAttention ?? []"
            @record="recordMeetingFor"
          />
        </Deferred>
        <OverviewStatusList />
      </div>

      <aside class="flex min-w-0 flex-col gap-10 lg:gap-14">
        <CreateShortcuts />
        <Deferred :data="deferredProps">
          <template #fallback>
            <CollectionSkeleton :rows="3" />
          </template>
          <CoordinatorCard :coordinator="coordinator ?? null" />
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
import CoordinatorCard from '@/Components/Home/CoordinatorCard.vue';
import CreateShortcuts from '@/Components/Home/CreateShortcuts.vue';
import QuickAccess from '@/Components/Home/QuickAccess.vue';
import FirstLoginChecklist from '@/Components/Home/FirstLoginChecklist.vue';
import HomeHero from '@/Components/Home/HomeHero.vue';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import RecentlyEditedList from '@/Components/Home/RecentlyEditedList.vue';
import SiteContentLists from '@/Components/Home/SiteContentLists.vue';
import UpcomingMeetingsList from '@/Components/Home/UpcomingMeetingsList.vue';
import type {
  HomeAccessChange,
  HomeChecklist,
  HomeCoordinator,
  HomeHeroImage,
  HomeMeeting,
  HomeNewsPreview,
  HomeRecentRecord,
  HomeRegistrationForm,
  HomeTask,
  InstitutionActivityInsight,
} from '@/Components/Home/types';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
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
  onboardingChecklist: HomeChecklist | null;
  accessChanges: HomeAccessChange[];
  /** Set when the URL asked for the ActionWindow (a reminder's answer buttons, U21). */
  actionWindowLaunch: { flow: 'meeting.create' | 'check-in'; institution: ActionWindowInstitutionRef } | null;
  taskStats: TaskStats;
  upcomingTasks: HomeTask[];
  upcomingMeetings: HomeMeeting[];
  heroImage: HomeHeroImage | null;
  institutionsNeedingAttention?: InstitutionActivityInsight[];
  upcomingCalendarEvents?: App.Entities.Calendar[];
  latestNews?: HomeNewsPreview[];
  recentlyEdited?: HomeRecentRecord[];
  coordinator?: HomeCoordinator | null;
  registrationForms: HomeRegistrationForm[];
}>();

const deferredProps = ['institutionsNeedingAttention', 'upcomingCalendarEvents', 'latestNews', 'recentlyEdited', 'coordinator'];
const visibleTasks = computed(() => props.upcomingTasks.slice(0, 3));

const page = usePage<PageProps>();

const hasAtstovavimas = computed(() => Boolean(
  props.upcomingMeetings?.length
  || page.props.auth?.can?.create?.meeting
  || page.props.auth?.can?.index?.meeting,
));

const actionWindow = useActionWindow();

const isMobile = useIsMobile();

const tourSteps = computed<DriveStep[]>(() => {
  if (isMobile.value) {
    return [
      {
        element: '[data-tour="command-palette"]',
        popover: {
          title: $t('tutorials.admin_home.command_palette.title'),
          description: $t('tutorials.admin_home.command_palette.description'),
        },
      },
      {
        element: '[data-tour="action-create-mobile"]',
        popover: {
          title: $t('tutorials.admin_home.action_create.title'),
          description: $t('tutorials.admin_home.action_create.description'),
        },
      },
      {
        element: '[data-tour="tasks-card"]',
        popover: {
          title: $t('tutorials.admin_home.tasks_card.title'),
          description: $t('tutorials.admin_home.tasks_card.description'),
        },
      },
      {
        element: '[data-tour="mobile-menu"]',
        popover: {
          title: $t('tutorials.admin_home.account_menu.title'),
          description: $t('tutorials.admin_home.account_menu.description'),
        },
      },
    ];
  }

  return [
    {
      element: '[data-tour="workspace-picker"]',
      popover: {
        title: $t('tutorials.admin_home.workspaces.title'),
        description: $t('tutorials.admin_home.workspaces.description'),
      },
    },
    {
      element: '[data-tour="command-palette"]',
      popover: {
        title: $t('tutorials.admin_home.command_palette.title'),
        description: $t('tutorials.admin_home.command_palette.description'),
      },
    },
    {
      element: '[data-tour="action-create"]',
      popover: {
        title: $t('tutorials.admin_home.action_create.title'),
        description: $t('tutorials.admin_home.action_create.description'),
      },
    },
    {
      element: '[data-tour="tasks-card"]',
      popover: {
        title: $t('tutorials.admin_home.tasks_card.title'),
        description: $t('tutorials.admin_home.tasks_card.description'),
      },
    },
    {
      element: '[data-tour="account-menu"]',
      popover: {
        title: $t('tutorials.admin_home.account_menu.title'),
        description: $t('tutorials.admin_home.account_menu.description'),
      },
    },
  ];
});

// The tour no longer starts by itself: the first-login checklist (U13) replaces it for new reps.
// It stays one tap away behind the layout's help button.
const { startTour } = useProductTour({
  tourId: 'admin-welcome-v1',
  steps: () => tourSteps.value,
});

provideTour(startTour);

// A reminder's answer buttons open Pradžia with the window already on the right flow (U21).
onMounted(() => {
  const launch = props.actionWindowLaunch;

  if (!launch) {
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

const recordMeetingFor = (institution: InstitutionActivityInsight) => {
  actionWindow.open({ flow: 'meeting.create', institution: { id: institution.id, name: institution.name } });
};

</script>
