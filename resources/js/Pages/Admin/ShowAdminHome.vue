<template>
  <PageContent>

    <Head :title="$t('Mano VU SA')" />

    <div class="space-y-6">
      <!-- Simple greeting -->
      <section
        data-tour="greeting-section"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/8 via-primary/4 to-background border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm dark:shadow-zinc-950/50 dark:from-primary/6 dark:via-primary/3">
        <div class="absolute inset-0 bg-grid-pattern opacity-[0.03] dark:opacity-[0.015]" />
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
              {{ greeting }}, <span class="text-primary dark:text-primary/85">{{ userNameAddress }}</span>!
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
              {{ currentDate }}
            </p>
          </div>
        </div>
      </section>

      <!-- Main content grid - responsive layout -->
      <div :class="[
        'grid gap-6',
        hasAtstovavimas ? 'lg:grid-cols-2' : 'lg:grid-cols-1'
      ]">
        <!-- Upcoming Meetings Card (first for atstovavimas users) -->
        <UpcomingMeetingsCard v-if="hasAtstovavimas" :upcoming-meetings="formattedMeetings"
          :institutions-insights="emptyInsights"
          data-tour="meetings-card"
          @show-all-meetings="() => router.visit(route('dashboard.atstovavimas'))"
          @create-meeting="showMeetingModal = true" />

        <!-- Tasks Card -->
        <TasksCard :task-stats :upcoming-tasks :class="{ 'lg:max-w-2xl': !hasAtstovavimas }" data-tour="tasks-card" />
      </div>

      <!-- Calendar Events Section -->
      <CalendarEventsCard v-if="upcomingCalendarEvents.length > 0" :events-list="upcomingCalendarEvents" />

      <!-- Latest News Section -->
      <NewsListCard v-if="latestNews.length > 0" :news-list="latestNews" />
    </div>

    <!-- New Meeting Modal -->
    <NewMeetingDialog :show-modal="showMeetingModal" @close="showMeetingModal = false" />
  </PageContent>
</template>

<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, onMounted, defineAsyncComponent } from "vue";
import { format } from "date-fns";
import { lt, enUS } from "date-fns/locale";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TasksCard from "@/Pages/Admin/Dashboard/Components/TasksCard.vue";
import UpcomingMeetingsCard from "@/Pages/Admin/Dashboard/Components/UpcomingMeetingsCard.vue";
// Lazy load modal - only needed when user clicks "Create meeting"
const NewMeetingDialog = defineAsyncComponent(() => import("@/Components/Dialogs/NewMeetingDialog.vue"));
import CalendarEventsCard from "@/Pages/Admin/Dashboard/Components/CalendarEventsCard.vue";
import NewsListCard from "@/Pages/Admin/Dashboard/Components/NewsListCard.vue";
import { addressivize } from "@/Utils/String";
import { useProductTour } from "@/Composables/useProductTour";
import { provideTour } from "@/Composables/useTourProvider";
import { useSidebar } from "@/Components/ui/sidebar/utils";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

// Types
interface TaskStats {
  total: number;
  overdue: number;
  dueSoon: number;
}

interface UpcomingTask {
  id: string;
  name: string;
  due_date: string | null;
  is_overdue: boolean;
  taskable_type: string;
  taskable_id: string;
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  can_be_manually_completed?: boolean;
}

interface UpcomingMeeting {
  id: string;
  title: string;
  start_time: string;
  institution_name: string;
}

// Props - use full entity types for Calendar and News to enable component reuse
const props = defineProps<{
  unreadNotificationsCount: number;
  hasNotifications: boolean;
  taskStats: TaskStats;
  upcomingTasks: UpcomingTask[];
  upcomingMeetings: UpcomingMeeting[];
  institutionsNeedingAttention: any[];
  upcomingCalendarEvents: App.Entities.Calendar[];
  latestNews: App.Entities.News[];
}>();

// Meeting modal state
const showMeetingModal = ref(false);

// Check if user has atstovavimas permissions (can create meetings)
const hasAtstovavimas = computed(() => usePage().props.auth?.can?.create?.meeting);

// Check if user can access administration
const canAccessAdministration = computed(() => usePage().props.auth?.can?.accessAdministration);

// Get sidebar controls for expanding during tour
const { setOpen, setOpenMobile, isMobile } = useSidebar();

// Expand sidebar when highlighting sidebar elements
const expandSidebar = () => {
  if (isMobile.value) {
    setOpenMobile(true);
  } else {
    setOpen(true);
  }
};

// Build conditional tour steps
const tourSteps = computed(() => {
  const steps = [];
  
  // 1. Welcome step (always)
  steps.push({
    popover: {
      title: $t('tutorials.admin_home.welcome.title'),
      description: $t('tutorials.admin_home.welcome.description'),
    },
  });
  
  // 2. Upcoming meetings card (if atstovavimas)
  if (hasAtstovavimas.value) {
    steps.push({
      element: '[data-tour="meetings-card"]',
      popover: {
        title: $t('tutorials.admin_home.meetings_card.title'),
        description: $t('tutorials.admin_home.meetings_card.description'),
      },
    });
  }
  
  // 3. ViSAK in sidebar (if atstovavimas)
  if (hasAtstovavimas.value) {
    steps.push({
      element: '[data-tour="nav-visak"]',
      popover: {
        title: $t('tutorials.admin_home.nav_visak.title'),
        description: $t('tutorials.admin_home.nav_visak.description'),
      },
      onHighlightStarted: expandSidebar,
    });
  }
  
  // 4. Administravimas button (if can manage administration)
  if (canAccessAdministration.value) {
    steps.push({
      element: '[data-tour="nav-administravimas"]',
      popover: {
        title: $t('tutorials.admin_home.nav_administravimas.title'),
        description: $t('tutorials.admin_home.nav_administravimas.description'),
      },
      onHighlightStarted: expandSidebar,
    });
  }
  
  // 5. Quick actions section
  steps.push({
    element: '[data-tour="quick-actions"]',
    popover: {
      title: $t('tutorials.admin_home.quick_actions.title'),
      description: $t('tutorials.admin_home.quick_actions.description'),
    },
    onHighlightStarted: expandSidebar,
  });
  
  // 6. Tasks card
  steps.push({
    element: '[data-tour="tasks-card"]',
    popover: {
      title: $t('tutorials.admin_home.tasks_card.title'),
      description: $t('tutorials.admin_home.tasks_card.description'),
    },
  });
  
  // 7. Tasks indicator in top bar
  steps.push({
    element: '[data-tour="tasks-indicator"]',
    popover: {
      title: $t('tutorials.admin_home.tasks_indicator.title'),
      description: $t('tutorials.admin_home.tasks_indicator.description'),
    },
  });
  
  // 8. Notifications indicator in top bar
  steps.push({
    element: '[data-tour="notifications-indicator"]',
    popover: {
      title: $t('tutorials.admin_home.notifications_indicator.title'),
      description: $t('tutorials.admin_home.notifications_indicator.description'),
    },
  });
  
  // 9. Help button in top bar
  steps.push({
    element: '[data-tour="help-button"]',
    popover: {
      title: $t('tutorials.admin_home.help_button.title'),
      description: $t('tutorials.admin_home.help_button.description'),
    },
  });
  
  // 10. Dokumentacija in sidebar
  steps.push({
    element: '[data-tour="nav-dokumentacija"]',
    popover: {
      title: $t('tutorials.admin_home.nav_dokumentacija.title'),
      description: $t('tutorials.admin_home.nav_dokumentacija.description'),
    },
    onHighlightStarted: expandSidebar,
  });
  
  // 11. User menu (settings) in sidebar
  steps.push({
    element: '[data-tour="user-menu"]',
    popover: {
      title: $t('tutorials.admin_home.user_menu.title'),
      description: $t('tutorials.admin_home.user_menu.description'),
    },
    onHighlightStarted: expandSidebar,
  });
  
  // 12. Leave feedback in sidebar (final step)
  steps.push({
    element: '[data-tour="nav-feedback"]',
    popover: {
      title: $t('tutorials.admin_home.nav_feedback.title'),
      description: $t('tutorials.admin_home.nav_feedback.description'),
    },
    onHighlightStarted: expandSidebar,
  });
  
  return steps;
});

// Setup product tour
const { startTour, startTourIfNew } = useProductTour({
  tourId: 'admin-home-v1',
  // Use function to defer translation evaluation until tour starts
  steps: () => tourSteps.value,
});

// Register tour with the layout's help button
provideTour(startTour);

// Auto-start tour for first-time users after component mounts
onMounted(() => {
  // Wait 1.5 seconds to ensure DOM is ready
  setTimeout(() => {
    startTourIfNew();
  }, 1500);
});

// Empty insights for the UpcomingMeetingsCard (we don't have this data on the home page)
const emptyInsights = {
  withoutMeetings: [],
  withOldMeetings: []
};

// Locale for date formatting
const dateLocale = computed(() => usePage().props.app.locale === 'lt' ? lt : enUS);

// User name with addressivization for Lithuanian
const userNameAddress = computed(() => {
  const name = usePage().props.auth?.user?.name;
  const split = name?.split(" ");
  if (!split) return "";
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

// Current date formatted
const currentDate = computed(() => {
  return format(new Date(), 'EEEE, MMMM d', { locale: dateLocale.value });
});

// Format meetings to match UpcomingMeetingsCard expected structure
const formattedMeetings = computed(() => {
  return props.upcomingMeetings.map(meeting => ({
    id: meeting.id,
    start_time: meeting.start_time,
    institutions: meeting.institution_name ? [{
      id: '0',
      name: meeting.institution_name,
      has_public_meetings: false
    }] : []
  }));
});
</script>

<style scoped>
.bg-grid-pattern {
  background-image: radial-gradient(circle, currentColor 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
