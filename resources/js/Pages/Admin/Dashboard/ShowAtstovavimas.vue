<template>
  <AdminContentPage>
    <InertiaHead :title="$t('Atstovavimas')" />

    <!-- Hero section with greeting and overview -->
    <PageHero :title="$t('Atstovavimas')" :subtitle="$t('Susitikimų, tikslų ir atstovavimo veiklų stebėjimas')" />

    <Tabs v-model="activeTab" class="mt-6 mb-32">
      <TabsList class="gap-2">
        <TabsTrigger value="user">
          {{ props.user.name }}
        </TabsTrigger>
        <TabsTrigger value="tenant" :disabled="props.availableTenants.length === 0">
          {{ currentTenant?.shortname || $t('Padalinys') }}
        </TabsTrigger>
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

        <!-- User timeline section -->
        <UserTimelineSection :institutions="atstovavimosData.institutions.value"
          :meetings="atstovavimosData.allUserMeetings.value" :gaps="atstovavimosData.userGaps.value"
          :available-tenants-user="timelineFilters.availableTenantsUser.value"
          :tenant-filter="timelineFilters.userTenantFilter.value"
          :show-only-with-activity="timelineFilters.showOnlyWithActivityUser.value"
          :show-only-with-public-meetings="timelineFilters.showOnlyWithPublicMeetingsUser.value"
          :institution-names="userInstitutionNames" :tenant-names :institution-tenant="userInstitutionTenant"
          :institution-has-public-meetings="userInstitutionHasPublicMeetings"
          :institution-periodicity="userInstitutionPeriodicity"
          :duty-members="userDutyMembers" :inactive-periods="userInactivePeriods"
          :show-duty-members="timelineFilters.showDutyMembersUser.value"
          @update:tenant-filter="timelineFilters.userTenantFilter.value = $event"
          @update:show-only-with-activity="timelineFilters.showOnlyWithActivityUser.value = $event"
          @update:show-only-with-public-meetings="timelineFilters.showOnlyWithPublicMeetingsUser.value = $event"
          @update:show-duty-members="timelineFilters.showDutyMembersUser.value = $event"
          @create-meeting="actions.onGapCreateMeeting" @fullscreen="actions.onGanttFullscreen('user')"
          @reset-filters="timelineFilters.resetUserFilters" />
      </TabsContent>

      <TabsContent value="tenant" class="mt-6">
        <TenantTimelineSection :available-tenants="props.availableTenants"
          :tenant-institutions="ganttData.formattedTenantInstitutions.value" :meetings="ganttData.tenantMeetings.value"
          :gaps="ganttData.tenantGaps.value" :selected-tenant-id="timelineFilters.selectedTenantForGantt.value"
          :current-tenant="timelineFilters.currentTenant.value"
          :show-only-with-activity="timelineFilters.showOnlyWithActivityTenant.value"
          :show-only-with-public-meetings="timelineFilters.showOnlyWithPublicMeetingsTenant.value"
          :institution-names="tenantInstitutionNames" :tenant-names :institution-tenant="tenantInstitutionTenant"
          :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
          :institution-periodicity="tenantInstitutionPeriodicity"
          :duty-members="ganttData.tenantDutyMembers.value" :inactive-periods="ganttData.tenantInactivePeriods.value"
          :show-duty-members="timelineFilters.showDutyMembersTenant.value"
          :is-hidden="actions.showFullscreenGantt.value"
          @update:selected-tenant-id="timelineFilters.setSelectedTenants"
          @update:show-only-with-activity="timelineFilters.showOnlyWithActivityTenant.value = $event"
          @update:show-only-with-public-meetings="timelineFilters.showOnlyWithPublicMeetingsTenant.value = $event"
          @update:show-duty-members="timelineFilters.showDutyMembersTenant.value = $event"
          @create-meeting="actions.onGapCreateMeeting" @fullscreen="actions.onGanttFullscreen('tenant')"
          @reset-filters="timelineFilters.resetTenantFilters" />
      </TabsContent>
    </Tabs>

    <!-- Modals - FullscreenGanttModal first so modals opened from within it appear on top -->
    <FullscreenGanttModal :is-open="actions.showFullscreenGantt.value" :gantt-type="actions.fullscreenGanttType.value"
      :current-tenant="timelineFilters.currentTenant.value" :available-tenants="props.availableTenants"
      :user-institutions="formatInstitutionsForUser"
      :user-meetings="atstovavimosData.allUserMeetings.value" :user-gaps="atstovavimosData.userGaps.value"
      :user-tenant-filter="timelineFilters.userTenantFilter.value"
      :show-only-with-activity-user="timelineFilters.showOnlyWithActivityUser.value"
      :show-only-with-public-meetings-user="timelineFilters.showOnlyWithPublicMeetingsUser.value"
      :user-institution-names
      :user-institution-tenant :user-institution-has-public-meetings="userInstitutionHasPublicMeetings"
      :user-institution-periodicity="userInstitutionPeriodicity"
      :user-duty-members="userDutyMembers" :user-inactive-periods="userInactivePeriods"
      :show-duty-members-user="timelineFilters.showDutyMembersUser.value"
      :tenant-institutions="ganttData.formattedTenantInstitutions.value"
      :tenant-meetings="ganttData.tenantMeetings.value" :tenant-gaps="ganttData.tenantGaps.value"
      :tenant-filter="timelineFilters.selectedTenantForGantt.value"
      :show-only-with-activity-tenant="timelineFilters.showOnlyWithActivityTenant.value"
      :show-only-with-public-meetings-tenant="timelineFilters.showOnlyWithPublicMeetingsTenant.value"
      :tenant-institution-names
      :tenant-institution-tenant :tenant-institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
      :tenant-institution-periodicity="tenantInstitutionPeriodicity"
      :tenant-duty-members="ganttData.tenantDutyMembers.value" :tenant-inactive-periods="ganttData.tenantInactivePeriods.value"
      :show-duty-members-tenant="timelineFilters.showDutyMembersTenant.value"
      :tenant-names @update:is-open="actions.showFullscreenGantt.value = $event"
      @update:tenant-filter="timelineFilters.setSelectedTenants"
      @update:show-only-with-activity-tenant="timelineFilters.showOnlyWithActivityTenant.value = $event"
      @update:show-only-with-public-meetings-tenant="timelineFilters.showOnlyWithPublicMeetingsTenant.value = $event"
      @update:show-duty-members-tenant="timelineFilters.showDutyMembersTenant.value = $event"
      @update:show-only-with-activity-user="timelineFilters.showOnlyWithActivityUser.value = $event"
      @update:show-only-with-public-meetings-user="timelineFilters.showOnlyWithPublicMeetingsUser.value = $event"
      @update:show-duty-members-user="timelineFilters.showDutyMembersUser.value = $event"
      @create-meeting="actions.onGapCreateMeeting" />

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

// Layout and base components

// UI components

// Extracted components
import PersonalOverviewSection from './Components/PersonalOverviewSection.vue';
import UserTimelineSection from './Components/UserTimelineSection.vue';
import TenantTimelineSection from './Components/TenantTimelineSection.vue';
import InstitutionDataTable from './Components/InstitutionDataTable.vue';
import MeetingDataTable from './Components/MeetingDataTable.vue';
import FullscreenGanttModal from './Components/FullscreenGanttModal.vue';
// Composables
import { useAtstovavimosData } from './Composables/useAtstovavimasData';
import { useTimelineFilters } from './Composables/useTimelineFilters';
import { useAtstovavimosActions } from './Composables/useAtstovavimasActions';
import { useGanttChartData } from './Composables/useGanttChartData';
import { provideGanttSettings } from './Composables/useGanttSettings';
// Icons and utils
import type { AtstovavimosUser, AtstovavimosTenant } from './types';

import Icons from "@/Types/Icons/filled";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
// Types
import TabsContent from '@/Components/ui/tabs/TabsContent.vue'
import TabsTrigger from '@/Components/ui/tabs/TabsTrigger.vue'
import TabsList from '@/Components/ui/tabs/TabsList.vue'
import Tabs from '@/Components/ui/tabs/Tabs.vue'
import AddCheckInDialog from "@/Components/CheckIns/AddCheckInDialog.vue";
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';
import PageHero from '@/Components/Hero/PageHero.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';

// Setup breadcrumbs
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('Atstovavimas'), undefined, Icons.MEETING)
]);

const props = defineProps<{
  user: AtstovavimosUser;
  accessibleInstitutions: App.Entities.Institution[];
  availableTenants: AtstovavimosTenant[];
  recentMeetings?: Array<{ id: string; title: string; start_time: string; institution_name: string; agenda_items: { title: string }[] }>;
}>();

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

// Sync tab state to URL without page reload
watch(activeTab, (newTab) => {
  const url = new URL(window.location.href);
  if (newTab === 'user') {
    url.searchParams.delete('tab');
  } else {
    url.searchParams.set('tab', newTab);
  }
  window.history.replaceState({}, '', url.toString());
});

// Computed admin check
const isAdmin = computed(() => {
  const roles = (props.user as any)?.roles?.map((r: any) => r.name) ?? [];
  return roles.includes('Super Admin') || roles.includes('Administratorius') ||
    roles.includes('Resource Manager') || roles.includes('Communication Coordinator');
});

// Initialize composables
const atstovavimosData = useAtstovavimosData(props.user);
const timelineFilters = useTimelineFilters(atstovavimosData.institutions.value, props.availableTenants);
const actions = useAtstovavimosActions(props.accessibleInstitutions);
const ganttData = useGanttChartData(props.accessibleInstitutions, props.availableTenants, timelineFilters.selectedTenantForGantt);

// Provide Gantt settings to child components (eliminates prop drilling for dayWidthPx, etc.)
provideGanttSettings();

// Helper functions for Gantt data formatting
const formatInstitutionsForUser = computed(() => {
  return ganttData.formatInstitutionsForGantt(atstovavimosData.institutions.value);
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
  return ganttData.getInstitutionPeriodicity(ganttData.tenantInstitutions.value);
});
</script>
