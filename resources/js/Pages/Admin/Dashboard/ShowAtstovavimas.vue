<template>
  <AdminContentPage>
    <InertiaHead title="ViSAK" />

    <!-- Hero section with ViSAK branding -->
    <PageHero :subtitle="$t('Susitikimų, tikslų ir atstovavimo veiklų stebėjimas')">
      <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-foreground flex items-center gap-3">
        <span
          class="font-black tracking-tight bg-gradient-to-r from-zinc-900 via-zinc-800 to-vusa-red dark:from-zinc-100 dark:via-zinc-200 dark:to-vusa-red bg-clip-text text-transparent transition-all duration-1000"
          :class="{ 'drop-shadow-[0_0_8px_rgba(189,24,48,0.3)]': showGlow }">
          ViSAK
        </span>
        <button class="text-muted-foreground hover:text-foreground transition-colors" :aria-label="$t('Kas yra ViSAK?')"
          @click="showVisakInfo = true">
          <Info class="h-5 w-5" />
        </button>
      </h1>
    </PageHero>

    <!-- ViSAK Info Modal -->
    <VisakInfoModal :open="showVisakInfo" @close="showVisakInfo = false" @start-tour="startContextTour" />

    <Tabs v-model="activeTab" class="mt-6 mb-32">
      <TabsList class="gap-2">
        <TabsTrigger value="user">
          {{ props.user.name }}
        </TabsTrigger>
        <div class="relative">
          <SpotlightPopover v-if="props.availableTenants.length > 0" :title="$t('tutorials.tenant_tab_spotlight.title')"
            :description="$t('tutorials.tenant_tab_spotlight.description')"
            :is-dismissed="tenantSpotlight.isDismissed.value" position="right" @dismiss="tenantSpotlight.dismiss">
            <TabsTrigger value="tenant" :disabled="props.availableTenants.length === 0" data-spotlight="tenant-tab">
              {{ currentTenant?.shortname || $t('Padalinys') }}
            </TabsTrigger>
          </SpotlightPopover>
          <TabsTrigger v-else value="tenant" disabled data-spotlight="tenant-tab">
            {{ $t('Padalinys') }}
          </TabsTrigger>
        </div>
      </TabsList>

      <TabsContent value="user" class="mt-6 space-y-8">
        <!-- Personal Overview Section -->
        <PersonalOverviewSection :institutions="atstovavimosData.institutions.value"
          :upcoming-meetings="atstovavimosData.upcomingMeetings.value"
          :institutions-insights="atstovavimosData.institutionsInsights.value" :is-admin
          :current-user-id="Number(props.user.id)" @show-all-institutions="actions.showAllInstitutionModal.value = true"
          @show-all-meetings="actions.showAllMeetingModal.value = true"
          @create-meeting="actions.showMeetingModal.value = true" @schedule-meeting="actions.handleScheduleMeeting"
          @show-institution-details="actions.handleShowInstitutionDetails" @refresh="actions.handleRefresh" />

        <!-- User timeline section - deferred to prevent view transition lag -->
        <UserTimelineSection v-if="deferredContentReady" :institutions="atstovavimosData.institutions.value"
          :meetings="atstovavimosData.allUserMeetings.value" :gaps="atstovavimosData.userGaps.value"
          :institution-names="userInstitutionNames" :tenant-names :institution-tenant="userInstitutionTenant"
          :institution-has-public-meetings="userInstitutionHasPublicMeetings"
          :institution-periodicity="userInstitutionPeriodicity" :duty-members="userDutyMembers"
          :inactive-periods="userInactivePeriods" :related-institutions
          :may-have-related-institutions="props.mayHaveRelatedInstitutions" @create-meeting="actions.onGapCreateMeeting"
          @fullscreen="actions.onGanttFullscreen('user')" />
        <!-- Timeline loading skeleton -->
        <div v-else class="space-y-4">
          <Skeleton class="h-8 w-48" />
          <Skeleton class="h-64 w-full rounded-lg" />
        </div>
      </TabsContent>

      <TabsContent value="tenant" class="mt-6">
        <TenantTimelineSection v-if="deferredContentReady" :available-tenants="props.availableTenants"
          :tenant-institutions="ganttData.formattedTenantInstitutions.value" :meetings="ganttData.tenantMeetings.value"
          :gaps="ganttData.tenantGaps.value" :institution-names="tenantInstitutionNames" :tenant-names
          :institution-tenant="tenantInstitutionTenant"
          :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
          :institution-periodicity="tenantInstitutionPeriodicity" :duty-members="ganttData.tenantDutyMembers.value"
          :inactive-periods="ganttData.tenantInactivePeriods.value" :is-hidden="actions.showFullscreenGantt.value"
          @create-meeting="actions.onGapCreateMeeting" @fullscreen="actions.onGanttFullscreen('tenant')" />
        <!-- Timeline loading skeleton -->
        <div v-else class="space-y-4">
          <Skeleton class="h-8 w-48" />
          <Skeleton class="h-64 w-full rounded-lg" />
        </div>
      </TabsContent>
    </Tabs>

    <!-- Modals - FullscreenGanttModal first so modals opened from within it appear on top -->
    <FullscreenGanttModal :is-open="actions.showFullscreenGantt.value" :gantt-type="actions.fullscreenGanttType.value"
      :available-tenants="props.availableTenants" :user-institutions="formatInstitutionsForUser"
      :user-meetings="atstovavimosData.allUserMeetings.value" :user-gaps="atstovavimosData.userGaps.value"
      :user-institution-names :user-institution-tenant :user-institution-has-public-meetings
      :user-institution-periodicity :user-duty-members :user-inactive-periods
      :user-related-institutions="formatRelatedInstitutions" :user-related-institutions-full="relatedInstitutions"
      :may-have-related-institutions="props.mayHaveRelatedInstitutions"
      :tenant-institutions="ganttData.formattedTenantInstitutions.value"
      :tenant-meetings="ganttData.tenantMeetings.value" :tenant-gaps="ganttData.tenantGaps.value"
      :tenant-institution-names :tenant-institution-tenant :tenant-institution-has-public-meetings
      :tenant-institution-periodicity :tenant-duty-members="ganttData.tenantDutyMembers.value"
      :tenant-inactive-periods="ganttData.tenantInactivePeriods.value" :tenant-names
      @update:is-open="actions.showFullscreenGantt.value = $event" @create-meeting="actions.onGapCreateMeeting" />

    <!-- These modals can be opened from FullscreenGanttModal, so they must come after it in DOM order -->
    <NewMeetingModal :show-modal="actions.showMeetingModal.value" :institution="actions.selectedInstitution.value"
      :suggested-at="actions.selectedSuggestedAt.value" :recent-meetings="props.recentMeetings"
      @close="actions.onCloseMeetingModal" />

    <AddCheckInDialog v-if="actions.showCreateCheckIn.value" :open="!!actions.showCreateCheckIn.value"
      :institution-id="actions.showCreateCheckIn.value.institutionId!" @close="actions.showCreateCheckIn.value = null"
      @created="actions.handleRefresh" />

    <InstitutionDataTable :institutions="atstovavimosData.institutions.value"
      :is-open="actions.showAllInstitutionModal.value" :on-schedule-meeting="actions.handleScheduleMeeting"
      :on-add-check-in="actions.handleAddCheckIn" @update:is-open="actions.showAllInstitutionModal.value = $event" />

    <MeetingDataTable :meetings="atstovavimosData.sortedMeetings.value" :is-open="actions.showAllMeetingModal.value"
      @update:is-open="actions.showAllMeetingModal.value = $event" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { Head as InertiaHead, router } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted } from 'vue';
import { trans as $t } from "laravel-vue-i18n";

// Deferred rendering for heavy timeline sections
// This prevents lag during view transitions by rendering timelines after transition completes
const deferredContentReady = ref(false);

onMounted(() => {
  // Wait for view transition to complete before rendering heavy content
  // Using requestAnimationFrame + setTimeout ensures we're past the transition
  requestAnimationFrame(() => {
    setTimeout(() => {
      deferredContentReady.value = true;
    }, 100);
  });
});

// Layout and base components

// UI components

// Extracted components
import { Info } from 'lucide-vue-next';

import PersonalOverviewSection from './Components/PersonalOverviewSection.vue';
import UserTimelineSection from './Components/UserTimelineSection.vue';
import TenantTimelineSection from './Components/TenantTimelineSection.vue';
import InstitutionDataTable from './Components/InstitutionDataTable.vue';
import MeetingDataTable from './Components/MeetingDataTable.vue';
import FullscreenGanttModal from './Components/FullscreenGanttModal.vue';

// Composables
import { useAtstovavimosData } from './Composables/useAtstovavimasData';
import { provideTimelineFilters } from './Composables/useTimelineFilters';
import { useAtstovavimosActions } from './Composables/useAtstovavimasActions';
import { useGanttChartData } from './Composables/useGanttChartData';
import { provideGanttSettings } from './Composables/useGanttSettings';
import type { AtstovavimosUser, AtstovavimosTenant, AtstovavimosInstitution } from './types';

import VisakInfoModal from '@/Components/Modals/VisakInfoModal.vue';
import { useProductTour } from '@/Composables/useProductTour';
import { provideTour } from '@/Composables/useTourProvider';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
// Icons and utils

import Icons from "@/Types/Icons/filled";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
// Types
import TabsContent from '@/Components/ui/tabs/TabsContent.vue'
import TabsTrigger from '@/Components/ui/tabs/TabsTrigger.vue'
import TabsList from '@/Components/ui/tabs/TabsList.vue'
import Tabs from '@/Components/ui/tabs/Tabs.vue'
import { Skeleton } from '@/Components/ui/skeleton'
import AddCheckInDialog from "@/Components/CheckIns/AddCheckInDialog.vue";
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';
import PageHero from '@/Components/Hero/PageHero.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';

// Setup breadcrumbs
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem('ViSAK', undefined, Icons.MEETING)
]);

// Setup product tour
const { startTour, startTourIfNew } = useProductTour({
  tourId: 'atstovavimas-overview-v1',
  // Use function to defer translation evaluation until tour starts
  steps: () => [
    // Welcome step - no element, centered popover
    {
      popover: {
        title: $t('tutorials.atstovavimas_overview.welcome.title'),
        description: $t('tutorials.atstovavimas_overview.welcome.description'),
      },
    },
    {
      element: '[data-tour="institution-card"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.institutions_card.title'),
        description: $t('tutorials.atstovavimas_overview.institutions_card.description'),
      },
    },
    {
      element: '[data-tour="institution-item"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.institution_item.title'),
        description: $t('tutorials.atstovavimas_overview.institution_item.description'),
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
      element: '[data-tour="create-meeting"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.create_meeting.title'),
        description: $t('tutorials.atstovavimas_overview.create_meeting.description'),
      },
    },
    {
      element: '[data-tour="all-meetings"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.all_meetings.title'),
        description: $t('tutorials.atstovavimas_overview.all_meetings.description'),
      },
    },
    {
      element: '[data-tour="timeline-section"]',
      popover: {
        title: $t('tutorials.atstovavimas_overview.timeline.title'),
        description: $t('tutorials.atstovavimas_overview.timeline.description'),
      },
    },
  ],
});

// Setup tour for tenant Gantt chart
const { startTour: startGanttTour, startTourIfNew: startGanttTourIfNew, hasCompleted: hasGanttTourCompleted } = useProductTour({
  tourId: 'gantt-chart-tour-v1',
  // Use function to defer translation evaluation until tour starts
  steps: () => [
    // 1. Chart Overview
    {
      element: '[data-tour="gantt-chart"]',
      popover: {
        title: $t('tutorials.gantt_tour.chart_overview.title'),
        description: $t('tutorials.gantt_tour.chart_overview.description'),
      },
    },
    // 1.1. Institution row (clickable title)
    {
      element: '[data-tour="gantt-institution-row"]',
      popover: {
        title: $t('tutorials.gantt_tour.institution_row.title'),
        description: $t('tutorials.gantt_tour.institution_row.description'),
      },
    },
    // 1.1.2. Meeting icons are clickable
    {
      element: '[data-tour="gantt-chart"]',
      popover: {
        title: $t('tutorials.gantt_tour.meeting_icons.title'),
        description: $t('tutorials.gantt_tour.meeting_icons.description'),
      },
    },
    // 1.1.3. Safety bands (periodicity zones)
    {
      element: '[data-tour="gantt-chart"]',
      popover: {
        title: $t('tutorials.gantt_tour.safety_bands.title'),
        description: $t('tutorials.gantt_tour.safety_bands.description'),
      },
    },
    // 1.2. Filters (click to open)
    {
      element: '[data-tour="gantt-filter-trigger"]',
      popover: {
        title: $t('tutorials.gantt_tour.filters.title'),
        description: $t('tutorials.gantt_tour.filters.description'),
      },
    },
    // 2. Fullscreen
    {
      element: '[data-tour="gantt-fullscreen"]',
      popover: {
        title: $t('tutorials.gantt_tour.fullscreen.title'),
        description: $t('tutorials.gantt_tour.fullscreen.description'),
      },
    },
    // 3. Year navigation
    {
      element: '[data-tour="gantt-date"]',
      popover: {
        title: $t('tutorials.gantt_tour.date_navigation.title'),
        description: $t('tutorials.gantt_tour.date_navigation.description'),
      },
    },
    // 4. Scale slider
    {
      element: '[data-tour="gantt-scale"]',
      popover: {
        title: $t('tutorials.gantt_tour.scale.title'),
        description: $t('tutorials.gantt_tour.scale.description'),
      },
    },
    // 5. Legend (last step)
    {
      element: '[data-tour="gantt-legend"]',
      popover: {
        title: $t('tutorials.gantt_tour.legend.title'),
        description: $t('tutorials.gantt_tour.legend.description'),
      },
    },
  ],
});

// Context-aware tour start - runs different tour based on active tab
// When user clicks the button, it's voluntary (they can close anytime)
function startContextTour() {
  if (activeTab.value === 'tenant') {
    startGanttTour(true); // voluntary = true
  } else {
    startTour(true); // voluntary = true
  }
}

// Register tour with the layout's help button
provideTour(startContextTour);

// Auto-start tour for first-time users after component mounts
onMounted(() => {
  // Wait 2 seconds to ensure DOM and Gantt chart are ready
  setTimeout(() => {
    startTourIfNew();
  }, 2000);
});

// Setup spotlight for tenant tab (for users who can see the tenant view)
const tenantSpotlight = useFeatureSpotlight('tenant-tab-spotlight-v1');

const props = defineProps<{
  user: AtstovavimosUser;
  userInstitutions: App.Entities.Institution[];
  tenantInstitutions?: App.Entities.Institution[];
  relatedInstitutions?: AtstovavimosInstitution[];
  mayHaveRelatedInstitutions?: boolean;
  availableTenants: AtstovavimosTenant[];
  recentMeetings?: Array<{ id: string; title: string; start_time: string; institution_name: string; agenda_items: { title: string }[] }>;
}>();

// Reactive computed for tenant institutions (from lazy-loaded props)
const tenantInstitutionsData = computed(() => props.tenantInstitutions ?? []);

// Related institutions computed (for passing to UserTimelineSection)
const relatedInstitutions = computed<AtstovavimosInstitution[]>(() => {
  return (props.relatedInstitutions ?? []).map(inst => ({
    ...inst,
    id: String(inst.id),
  }));
});

// Tab state with URL persistence
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab === 'tenant' && props.availableTenants.length > 0) return 'tenant';
  }
  return 'user';
};

const activeTab = ref(getInitialTab());

// ViSAK info modal and glow effect
const showVisakInfo = ref(false);
const showGlow = ref(false);

// Subtle glow animation after 5 seconds
onMounted(() => {
  setTimeout(() => {
    showGlow.value = true;
    setTimeout(() => {
      showGlow.value = false;
    }, 2000);
  }, 5000);
});

// Sync tab state to URL without page reload
watch(activeTab, (newTab) => {
  const url = new URL(window.location.href);
  if (newTab === 'user') {
    url.searchParams.delete('tab');
  } else {
    url.searchParams.set('tab', newTab);
  }
  window.history.replaceState({}, '', url.toString());

  // Auto-dismiss spotlight when tenant tab is clicked
  if (newTab === 'tenant' && !tenantSpotlight.isDismissed.value) {
    tenantSpotlight.dismiss();
  }

  // Auto-start Gantt tour when tenant tab is opened for the first time
  if (newTab === 'tenant' && !hasGanttTourCompleted.value) {
    // Wait for DOM to render the Gantt chart
    setTimeout(() => {
      startGanttTourIfNew();
    }, 1500);
  }
});

// Computed admin check
const isAdmin = computed(() => {
  const roles = (props.user as any)?.roles?.map((r: any) => r.name) ?? [];
  return roles.includes('Super Admin') || roles.includes('Administratorius') ||
    roles.includes('Resource Manager') || roles.includes('Communication Coordinator');
});

// Initialize composables
const atstovavimosData = useAtstovavimosData(props.user);
const timelineFilters = provideTimelineFilters(atstovavimosData.institutions.value, props.availableTenants);
const actions = useAtstovavimosActions(props.userInstitutions);
const ganttData = useGanttChartData(tenantInstitutionsData, props.availableTenants);

// Provide Gantt settings to child components (eliminates prop drilling for dayWidthPx, etc.)
provideGanttSettings();

// Load tenant institutions when tenant tab is opened
watch(activeTab, (newTab) => {
  if (newTab === 'tenant' && props.availableTenants.length > 0) {
    timelineFilters.loadTenantInstitutions();
  }
});

// Reload tenant institutions when selected tenants change
watch(() => timelineFilters.selectedTenantForGantt.value, (newTenants) => {
  // Only reload if on tenant tab and we have tenants selected
  if (activeTab.value === 'tenant' && newTenants.length > 0) {
    timelineFilters.loadTenantInstitutions(newTenants);
  }
}, { deep: true });

// Load on mount if already on tenant tab
onMounted(() => {
  if (activeTab.value === 'tenant' && props.availableTenants.length > 0) {
    timelineFilters.loadTenantInstitutions();
  }
});

// Helper functions for Gantt data formatting
const formatInstitutionsForUser = computed(() => {
  return ganttData.formatInstitutionsForGantt(atstovavimosData.institutions.value);
});

const formatRelatedInstitutions = computed(() => {
  return ganttData.formatInstitutionsForGantt(relatedInstitutions.value);
});

const userInstitutionNames = computed(() => {
  return ganttData.getInstitutionNames(atstovavimosData.institutions.value);
});

const userInstitutionTenant = computed(() => {
  return ganttData.getInstitutionTenant(atstovavimosData.institutions.value);
});

const userInstitutionHasPublicMeetings = computed(() => {
  return ganttData.getInstitutionHasPublicMeetings(atstovavimosData.institutions.value);
});

const userInstitutionPeriodicity = computed(() => {
  return ganttData.getInstitutionPeriodicity(atstovavimosData.institutions.value);
});

// User duty members and inactive periods
const userDutyMembers = computed(() => {
  return ganttData.getDutyMembersFromInstitutions(atstovavimosData.institutions.value);
});

const userInactivePeriods = computed(() => {
  return ganttData.getInactivePeriodsFromInstitutions(atstovavimosData.institutions.value);
});

const tenantNames = computed(() => {
  return ganttData.getTenantNames();
});

const currentTenant = computed(() => {
  return timelineFilters.currentTenant.value;
});

// Memoized tenant institution lookups - only recompute when tenantInstitutions changes
const tenantInstitutionNames = computed(() => {
  const institutions = ganttData.tenantInstitutions.value;
  const result: Record<string, string> = {};
  for (const i of institutions) {
    result[i.id as string] = String(i.name ?? '');
  }
  return result;
});

const tenantInstitutionTenant = computed(() => {
  const institutions = ganttData.tenantInstitutions.value;
  const result: Record<string, string> = {};
  for (const i of institutions) {
    result[i.id as string] = String(i.tenant_id ?? '');
  }
  return result;
});

const tenantInstitutionHasPublicMeetings = computed(() => {
  const institutions = ganttData.tenantInstitutions.value;
  const result: Record<string, boolean> = {};
  for (const i of institutions) {
    result[i.id as string] = Boolean((i as any).has_public_meetings);
  }
  return result;
});

const tenantInstitutionPeriodicity = computed(() => {
  return ganttData.getInstitutionPeriodicity(ganttData.tenantInstitutions.value as unknown as AtstovavimosInstitution[]);
});
</script>
