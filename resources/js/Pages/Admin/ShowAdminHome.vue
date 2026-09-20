<template>
  <OverviewPage :title="$t('Mano VU SA')">
    <template #heading>
      <h1 class="text-2xl font-semibold tracking-tight" data-tour="greeting-section">
        {{ greeting }}, {{ userNameAddress }}!
      </h1>
    </template>

    <template #attention>
      <AttentionQueue :tasks="upcomingTasks" :stats="taskStats" :more-href="route('userTasks')" />
    </template>

    <CreateShortcuts />

    <UpcomingMeetingsList
      v-if="hasAtstovavimas"
      :meetings="upcomingMeetings"
      :href="route('dashboard.atstovavimas')"
    />

    <!-- Everything below is deferred: the queue above is what a rep came for (U19). -->
    <Deferred :data="deferredProps">
      <template #fallback>
        <CollectionSkeleton :rows="3" />
      </template>

      <InstitutionsNeedingAttention
        v-if="hasAtstovavimas"
        :institutions="institutionsNeedingAttention ?? []"
        @record="recordMeetingFor"
      />

      <div class="grid gap-8 lg:grid-cols-2">
        <CoordinatorCard :coordinator="coordinator ?? null" />
        <RecentlyEditedList :records="recentlyEdited ?? []" />
      </div>

      <SiteContentLists
        :events="calendarEvents"
        :news="newsItems"
      />
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';
import type { DriveStep } from 'driver.js';

import AttentionQueue from '@/Components/Home/AttentionQueue.vue';
import CoordinatorCard from '@/Components/Home/CoordinatorCard.vue';
import CreateShortcuts from '@/Components/Home/CreateShortcuts.vue';
import InstitutionsNeedingAttention from '@/Components/Home/InstitutionsNeedingAttention.vue';
import RecentlyEditedList from '@/Components/Home/RecentlyEditedList.vue';
import SiteContentLists from '@/Components/Home/SiteContentLists.vue';
import UpcomingMeetingsList from '@/Components/Home/UpcomingMeetingsList.vue';
import type {
  HomeContentItem,
  HomeCoordinator,
  HomeMeeting,
  HomeRecentRecord,
  HomeTask,
  InstitutionActivityInsight,
} from '@/Components/Home/types';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { CollectionSkeleton } from '@/Components/Patterns';
import { addressivize } from '@/Utils/String';
import { useProductTour } from '@/Composables/useProductTour';
import { useIsMobile } from '@/Composables/useIsMobile';
import { provideTour } from '@/Composables/useTourProvider';
import { useActionWindow } from '@/Composables/useActionWindow';

interface TaskStats {
  total: number;
  overdue: number;
  dueSoon: number;
}

// The first response carries the queue and upcoming meetings; the rest arrives as one deferred group.
const props = defineProps<{
  unreadNotificationsCount: number;
  hasNotifications: boolean;
  taskStats: TaskStats;
  upcomingTasks: HomeTask[];
  upcomingMeetings: HomeMeeting[];
  institutionsNeedingAttention?: InstitutionActivityInsight[];
  upcomingCalendarEvents?: App.Entities.Calendar[];
  latestNews?: App.Entities.News[];
  recentlyEdited?: HomeRecentRecord[];
  coordinator?: HomeCoordinator | null;
}>();

const deferredProps = ['institutionsNeedingAttention', 'upcomingCalendarEvents', 'latestNews', 'recentlyEdited', 'coordinator'];

// Check if user has atstovavimas permissions (meetings exist or can create/index meetings)
const hasAtstovavimas = computed(() => Boolean(
  props.upcomingMeetings?.length
  || usePage().props.auth?.can?.create?.meeting
  || usePage().props.auth?.can?.index?.meeting,
));

const actionWindow = useActionWindow();

const isMobile = useIsMobile();

// Build welcome tour for the new admin shell (Phase 4.8), responsive to viewport
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

// Setup product tour
const { startTour, startTourIfNew } = useProductTour({
  tourId: 'admin-welcome-v1',
  steps: () => tourSteps.value,
});

// Register tour with the layout's help button
provideTour(startTour);

// Auto-start tour for first-time users after component mounts
onMounted(() => {
  // Wait 1.5 seconds to ensure DOM is ready
  setTimeout(() => {
    // A first-time user who reaches for the action window inside that window gets a
    // tour popup over an open modal, and the two fight for the same click.
    if (actionWindow.isOpen.value) {
      return;
    }

    startTourIfNew();
  }, 1500);
});

// User name with addressivization for Lithuanian
const userNameAddress = computed(() => {
  const name = usePage().props.auth?.user?.name;
  const split = name?.split(' ');
  if (!split) return '';
  const firstName = split[0];
  return usePage().props.app.locale === 'lt' ? addressivize(firstName) : firstName;
});

// Time-based greeting (simplified - no "Geros nakties")
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return $t('Labas rytas');
  if (hour < 18) return $t('Laba diena');
  return $t('Labas vakaras');
});

const recordMeetingFor = (institution: InstitutionActivityInsight) => {
  actionWindow.open({ flow: 'meeting.create', institution: { id: institution.id, name: institution.name } });
};

// The calendar and news payloads are full models; the home page only needs a title and a date.
const calendarEvents = computed<HomeContentItem[]>(() =>
  (props.upcomingCalendarEvents ?? []).map(event => ({
    id: String(event.id),
    title: String(event.title),
    date: event.date ? String(event.date) : null,
  })),
);

const newsItems = computed<HomeContentItem[]>(() =>
  (props.latestNews ?? []).map(item => ({
    id: String(item.id),
    title: String(item.title),
    date: item.publish_time ? String(item.publish_time) : null,
  })),
);
</script>
