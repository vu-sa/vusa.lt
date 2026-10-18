<template>
  <FocusModeFrame v-slot="{ active, toggle }" v-model:active="fullscreen" :label="$t('visak.overview.timeline.title')">
    <OverviewSection
      :class="active && 'h-full'"
      :title="$t('visak.overview.timeline.title')"
      :icon="CalendarRange"
      variant="home"
      data-tour="timeline-section"
    >
      <template #actions>
        <GanttFilterDropdown
          :show-only-with-activity="filters.showOnlyWithActivityUser.value"
          :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
          :hide-internal-institutions="filters.hideInternalInstitutionsUser.value"
          :show-duty-members="filters.showDutyMembersUser.value"
          :show-related-institutions="filters.showRelatedInstitutionsUser.value"
          :has-related-institutions
          :show-activity-requests="filters.showActivityRequestsUser.value"
          :show-activity-requests-option="activityRequests.length > 0"
          @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityUser.value = val"
          @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsUser.value = val"
          @update:hide-internal-institutions="(val: boolean) => filters.hideInternalInstitutionsUser.value = val"
          @update:show-duty-members="(val: boolean) => filters.showDutyMembersUser.value = val"
          @update:show-activity-requests="(val: boolean) => filters.showActivityRequestsUser.value = val"
          @update:show-related-institutions="(val: boolean) => filters.showRelatedInstitutionsUser.value = val"
          @reset="filters.resetUserFilters()"
        />
      </template>

      <TimelineGanttSkeleton v-if="!isReady" />
      <TimelineGanttChart v-else :class="active && 'min-h-0 flex-1'" :height="active ? '100%' : undefined" :fullscreen-active="active"
        :institutions="formattedInstitutions" :meetings="allMeetings" :gaps
        :show-only-with-activity="filters.showOnlyWithActivityUser.value"
        :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
        :hide-internal-institutions="filters.hideInternalInstitutionsUser.value"
        :institution-names="allInstitutionNames" :tenant-names :institution-has-public-meetings="allInstitutionHasPublicMeetings"
        :institution-periodicity="allInstitutionPeriodicity"
        :duty-members="mergedDutyMembers" :inactive-periods="mergedInactivePeriods"
        :show-duty-members="filters.showDutyMembersUser.value"
        :activity-requests :show-activity-requests="filters.showActivityRequestsUser.value"
        :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')" @create-meeting="$emit('create-meeting', $event)"
        @create-check-in="$emit('create-check-in', $event)"
        @fullscreen="toggle" />
    </OverviewSection>
  </FocusModeFrame>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, toRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarRange } from '@lucide/vue';

import type {
  AtstovavimasInstitution,
  GanttMeeting,
  AtstovavimasGap,
  GanttActivityRequest,
  GanttDutyMember,
  InactivePeriod,
} from '../types';
import { useTimelineFilters } from '../Composables/useTimelineFilters';
import { useUserTimelineData } from '../Composables/useUserTimelineData';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';

import FocusModeFrame from '@/Components/Patterns/FocusModeFrame.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

interface Props {
  institutions: AtstovavimasInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimasGap[];
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
  // Related institutions
  relatedInstitutions?: AtstovavimasInstitution[];
  // Flag to show related institutions filter even when data is lazy-loaded
  mayHaveRelatedInstitutions?: boolean;
  activityRequests?: GanttActivityRequest[];
}

const props = withDefaults(defineProps<Props>(), {
  activityRequests: () => [],
});

// Get shared filter state
const filters = useTimelineFilters();

// Deferred rendering - wait for next frame after mount to render heavy Gantt chart
const isReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    isReady.value = true;
  });

  // If filter was persisted as ON, trigger lazy load on mount
  if (filters.showRelatedInstitutionsUser.value
    && !filters.relatedInstitutionsLoaded.value
    && (props.relatedInstitutions?.length ?? 0) === 0) {
    filters.loadRelatedInstitutions();
  }
});

defineEmits<{
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
}>();

// In place rather than a modal, so dialogs opened from the chart stack above it.
const fullscreen = ref(false);

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
const baseInstitutionHasPublicMeetingsRef = computed(() => props.institutionHasPublicMeetings ?? {});
const baseInstitutionPeriodicityRef = computed(() => props.institutionPeriodicity ?? {});

const {
  mergedInstitutions: formattedInstitutions,
  mergedMeetings: allMeetings,
  mergedDutyMembers,
  mergedInactivePeriods,
  mergedInstitutionNames: allInstitutionNames,
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
  baseInstitutionHasPublicMeetings: baseInstitutionHasPublicMeetingsRef,
  baseInstitutionPeriodicity: baseInstitutionPeriodicityRef,
});
</script>
