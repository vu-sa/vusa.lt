<template>
  <section v-if="availableTenants.length > 0" data-tour="tenant-gantt-section" class="space-y-4">
    <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
      <h2 class="text-xl font-semibold tracking-tight">
        {{
          filters.selectedTenantForGantt.value.length === 0 ? $t('Visi padaliniai') :
            filters.selectedTenantForGantt.value.length === 1 ? (availableTenants.find(t => String(t.id) === filters.selectedTenantForGantt.value[0])?.shortname ||
              $t('Padalinys')) :
              $t('Pasirinkti padaliniai')
        }} — {{ $t('laiko juosta') }}
      </h2>
      <div data-tour="gantt-filters" class="flex flex-wrap items-center gap-2 ml-auto">
        <GanttFilterDropdown
          :tenants="availableTenants"
          :selected-tenants="filters.selectedTenantForGantt.value"
          :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
          :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
          :show-duty-members="filters.showDutyMembersTenant.value"
          :show-tenant-headers="ganttSettings.showTenantHeaders.value"
          @update:selected-tenants="(val: string[]) => filters.selectedTenantForGantt.value = val"
          @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityTenant.value = val"
          @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsTenant.value = val"
          @update:show-duty-members="(val: boolean) => filters.showDutyMembersTenant.value = val"
          @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
          @reset="filters.resetTenantFilters()"
        />
      </div>
    </div>

    <!-- Deferred Gantt chart rendering for better initial load performance -->
    <!-- Show skeleton while loading tenant institutions -->
    <TimelineGanttSkeleton v-if="!isReady || isHidden || filters.tenantInstitutionsLoading.value" />
    <!-- Show empty state if not loaded yet (but not if data was loaded and exists) -->
    <div v-else-if="!filters.tenantInstitutionsLoaded.value && !hasData" class="text-center py-12 text-muted-foreground">
      {{ $t('Pasirinkite padalinį norėdami matyti institucijų laiko juostą') }}
    </div>
    <div v-else-if="!isHidden" data-tour="gantt-chart">
      <TimelineGanttChart :institutions="formattedInstitutions" :meetings :gaps 
        :tenant-filter="filters.selectedTenantForGantt.value"
        :show-only-with-activity="filters.showOnlyWithActivityTenant.value" 
        :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value" 
        :institution-names :tenant-names :institution-tenant :institution-has-public-meetings="institutionHasPublicMeetings"
        :institution-periodicity="institutionPeriodicity"
        :duty-members :inactive-periods :show-duty-members="filters.showDutyMembersTenant.value"
        :empty-message="$t('Šiame padalinyje nėra institucijų')" @create-meeting="$emit('create-meeting', $event)"
        @create-check-in="$emit('create-check-in', $event)"
        @fullscreen="$emit('fullscreen')" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  GanttMeeting,
  AtstovavimosGap,
  AtstovavimosTenant,
  GanttInstitution,
  GanttDutyMember,
  InactivePeriod
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';
import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';


interface Props {
  availableTenants: AtstovavimosTenant[];
  tenantInstitutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  // When true, hide the Gantt chart to save rendering resources (e.g., when fullscreen modal is open)
  isHidden?: boolean;
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
}

const props = defineProps<Props>();

// Deferred rendering - wait for next frame after mount to render heavy Gantt chart
const isReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    isReady.value = true;
  });
});

// Get gantt settings for showTenantHeaders toggle
const ganttSettings = useGanttSettings();
// Get shared filter state
const filters = useTimelineFilters();

// Check if we actually have data (not just the loaded flag)
// This handles the case where an Inertia request resets the lazy-loaded props
const hasData = computed(() => props.tenantInstitutions?.length > 0);

// Watch for data being reset (e.g., by Inertia request) and trigger reload
watch(
  () => props.tenantInstitutions,
  (newValue) => {
    // If we thought data was loaded but now it's empty/undefined, reload
    if (filters.tenantInstitutionsLoaded.value && (!newValue || newValue.length === 0)) {
      // Reset the loaded flag so we can reload
      filters.tenantInstitutionsLoaded.value = false;
      // Trigger reload
      filters.loadTenantInstitutions();
    }
  }
);

const emit = defineEmits<{
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'create-check-in': [payload: { institution_id: string | number, startDate: Date, endDate: Date }];
  'fullscreen': [];
}>();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  const formatted = props.tenantInstitutions.map(i => ({
    id: i.id,
    name: i.name,
    tenant_id: i.tenant_id
  }));
  return formatted;
});
</script>
