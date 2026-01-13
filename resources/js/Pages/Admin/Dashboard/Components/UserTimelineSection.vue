<template>
  <section data-tour="timeline-section" class="space-y-6" aria-labelledby="user-timeline-heading">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 id="user-timeline-heading" class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ $t('Tavo institucijos') }} — {{ $t('laiko juosta') }}
      </h2>
      <div class="flex items-center gap-2">
        <GanttFilterDropdown
          :tenants="filters.availableTenantsUser.value"
          :selected-tenants="filters.userTenantFilter.value"
          :show-only-with-activity="filters.showOnlyWithActivityUser.value"
          :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
          :show-duty-members="filters.showDutyMembersUser.value"
          :show-tenant-headers="ganttSettings.showTenantHeaders.value"
          :show-related-institutions="filters.showRelatedInstitutionsUser.value"
          :has-related-institutions="hasRelatedInstitutions"
          @update:selected-tenants="(val: string[]) => filters.userTenantFilter.value = val"
          @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityUser.value = val"
          @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsUser.value = val"
          @update:show-duty-members="(val: boolean) => filters.showDutyMembersUser.value = val"
          @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
          @update:show-related-institutions="(val: boolean) => filters.showRelatedInstitutionsUser.value = val"
          @reset="filters.resetUserFilters()"
        />
      </div>
    </div>

    <!-- Deferred Gantt chart rendering for better initial load performance -->
    <TimelineGanttSkeleton v-if="!isReady" />
    <TimelineGanttChart v-else :institutions="formattedInstitutions" :meetings="allMeetings" :gaps 
      :tenant-filter="filters.userTenantFilter.value" 
      :show-only-with-activity="filters.showOnlyWithActivityUser.value"
      :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
      :institution-names="allInstitutionNames" :tenant-names :institution-tenant="allInstitutionTenant" :institution-has-public-meetings="allInstitutionHasPublicMeetings"
      :institution-periodicity="allInstitutionPeriodicity"
      :duty-members :inactive-periods :show-duty-members="filters.showDutyMembersUser.value" :day-width="dayWidthPx"
      :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')" @create-meeting="$emit('create-meeting', $event)"
      @fullscreen="$emit('fullscreen')" @update:day-width="emit('update:dayWidth', $event)" />
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, toRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  AtstovavimosInstitution,
  GanttMeeting,
  AtstovavimosGap,
  GanttDutyMember,
  InactivePeriod
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';
import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';
import { useUserTimelineData } from '../Composables/useUserTimelineData';


interface Props {
  institutions: AtstovavimosInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  dayWidthPx?: number;
  inactivePeriods?: InactivePeriod[];
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
  // Related institutions
  relatedInstitutions?: AtstovavimosInstitution[];
  // Flag to show related institutions filter even when data is lazy-loaded
  mayHaveRelatedInstitutions?: boolean;
}

const props = defineProps<Props>();

// Get gantt settings for showTenantHeaders toggle
const ganttSettings = useGanttSettings();
// Get shared filter state
const filters = useTimelineFilters();

// Deferred rendering - wait for next frame after mount to render heavy Gantt chart
const isReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    isReady.value = true;
  });
  
  // If filter was persisted as ON, trigger lazy load on mount
  if (filters.showRelatedInstitutionsUser.value && 
      !filters.relatedInstitutionsLoaded.value && 
      (props.relatedInstitutions?.length ?? 0) === 0) {
    filters.loadRelatedInstitutions();
  }
});

const emit = defineEmits<{
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'update:dayWidth': [value: number];
  'fullscreen': [];
}>();

// Check if we have any related institutions (or might have when lazy-loaded)
const hasRelatedInstitutions = computed(() => {
  // Show filter if we have loaded related institutions OR if backend says we might have some
  return (props.relatedInstitutions?.length ?? 0) > 0 || props.mayHaveRelatedInstitutions === true;
});

// When "show related institutions" filter is toggled on, trigger lazy load if not already loaded
watch(() => filters.showRelatedInstitutionsUser.value, (newValue) => {
  // Load if: filter is ON, data hasn't been loaded yet, and no data exists yet
  if (newValue && !filters.relatedInstitutionsLoaded.value && (props.relatedInstitutions?.length ?? 0) === 0) {
    filters.loadRelatedInstitutions();
  }
});

// Use composable for merged timeline data (handles related institutions merging)
const relatedInstitutionsRef = computed(() => props.relatedInstitutions ?? []);
const institutionsRef = toRef(props, 'institutions');
const meetingsRef = toRef(props, 'meetings');
const baseDutyMembersRef = computed(() => props.dutyMembers ?? []);
const baseInactivePeriodsRef = computed(() => props.inactivePeriods ?? []);
const baseInstitutionNamesRef = toRef(props, 'institutionNames');
const baseInstitutionTenantRef = toRef(props, 'institutionTenant');
const baseInstitutionHasPublicMeetingsRef = computed(() => props.institutionHasPublicMeetings ?? {});
const baseInstitutionPeriodicityRef = computed(() => props.institutionPeriodicity ?? {});

const {
  mergedInstitutions: formattedInstitutions,
  mergedMeetings: allMeetings,
  mergedDutyMembers: dutyMembers,
  mergedInactivePeriods: inactivePeriods,
  mergedInstitutionNames: allInstitutionNames,
  mergedInstitutionTenant: allInstitutionTenant,
  mergedInstitutionHasPublicMeetings: allInstitutionHasPublicMeetings,
  mergedInstitutionPeriodicity: allInstitutionPeriodicity,
} = useUserTimelineData({
  institutions: institutionsRef,
  meetings: meetingsRef,
  relatedInstitutions: relatedInstitutionsRef,
  showRelatedInstitutions: filters.showRelatedInstitutionsUser,
  baseDutyMembers: baseDutyMembersRef,
  baseInactivePeriods: baseInactivePeriodsRef,
  baseInstitutionNames: baseInstitutionNamesRef,
  baseInstitutionTenant: baseInstitutionTenantRef,
  baseInstitutionHasPublicMeetings: baseInstitutionHasPublicMeetingsRef,
  baseInstitutionPeriodicity: baseInstitutionPeriodicityRef,
});
</script>
