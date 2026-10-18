<template>
  <FocusModeFrame
    v-if="availableTenants.length > 0"
    v-slot="{ active, toggle }"
    v-model:active="fullscreen"
    :label="$t('visak.overview.timeline.title')"
  >
    <OverviewSection
      :class="active && 'h-full'"
      :title="$t('visak.overview.timeline.title')"
      :icon="CalendarRange"
      variant="home"
      data-tour="tenant-gantt-section"
    >
      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-2" data-tour="gantt-filters">
          <TenantScopeSelector
            v-if="availableTenants.length > 1"
            compact
            :label="$t('visak.gantt.tenants_label')"
            :tenants="availableTenants"
            :selected-tenants="filters.selectedTenantForGantt.value"
            @update:selected-tenants="filters.setSelectedTenants"
          />
          <GanttFilterDropdown
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :hide-internal-institutions="filters.hideInternalInstitutionsTenant.value"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :show-activity-status="filters.showActivityStatusTenant.value"
            :show-activity-status-option="!!representativeActivity"
            :show-activity-requests="filters.showActivityRequestsTenant.value"
            :show-activity-requests-option
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityTenant.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsTenant.value = val"
            @update:hide-internal-institutions="(val: boolean) => filters.hideInternalInstitutionsTenant.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersTenant.value = val"
            @update:show-activity-status="(val: boolean) => filters.showActivityStatusTenant.value = val"
            @update:show-activity-requests="(val: boolean) => filters.showActivityRequestsTenant.value = val"
            @reset="filters.resetTenantFilters()"
          />
        </div>
      </template>

      <!-- A skeleton only before the first load: a reload keeps the chart, and its scroll position, mounted. -->
      <TimelineGanttSkeleton v-if="!isReady || (!filters.tenantInstitutionsLoaded.value && filters.tenantInstitutionsLoading.value)" />
      <p v-else-if="!filters.tenantInstitutionsLoaded.value && tenantInstitutions.length === 0" class="py-12 text-center text-sm text-muted-foreground">
        {{ $t('Pasirink padalinį norėdami matyti institucijų laiko juostą') }}
      </p>
      <div v-else data-tour="gantt-chart" :class="active && 'min-h-0 flex-1'">
        <TimelineGanttChart :class="active && 'h-full'" :height="active ? '100%' : undefined" :fullscreen-active="active"
          :institutions="tenantInstitutions" :meetings :gaps
          :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
          :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
          :hide-internal-institutions="filters.hideInternalInstitutionsTenant.value"
          :institution-names :tenant-names :institution-has-public-meetings
          :institution-has-activity
          :institution-periodicity
          :duty-members :inactive-periods :show-duty-members="filters.showDutyMembersTenant.value"
          :show-activity-status="filters.showActivityStatusTenant.value"
          :activity-requests :show-activity-requests="filters.showActivityRequestsTenant.value"
          :loading-range :meetings-loading="meetingsLoading || filters.tenantInstitutionsLoading.value"
          :empty-message="$t('Šiame padalinyje nėra institucijų')" @create-meeting="$emit('create-meeting', $event)"
          @create-check-in="$emit('create-check-in', $event)"
          @fullscreen="toggle"
          @range-changed="(min: Date, max: Date) => $emit('range-changed', min, max)" />
      </div>
    </OverviewSection>
  </FocusModeFrame>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarRange } from '@lucide/vue';

import type {
  GanttMeeting,
  AtstovavimasGap,
  AtstovavimasTenant,
  GanttActivityRequest,
  GanttInstitution,
  GanttDutyMember,
  InactivePeriod,
  RepresentativeActivityData,
} from '../types';
import { useTimelineFilters } from '../Composables/useTimelineFilters';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';
import TenantScopeSelector from './TenantScopeSelector.vue';

import FocusModeFrame from '@/Components/Patterns/FocusModeFrame.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

defineProps<{
  availableTenants: AtstovavimasTenant[];
  tenantInstitutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimasGap[];
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  /** Server-derived "has any activity" flags for the activity filter */
  institutionHasActivity?: Record<string, boolean>;
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  institutionPeriodicity?: Record<string | number, number>;
  loadingRange?: { from: Date; until: Date } | null;
  /** Whether meetings are being fetched (delayed ~300ms by the caller) */
  meetingsLoading?: boolean;
  /** Only decides whether the activity-ring option is offered; the figures live on the page. */
  representativeActivity?: RepresentativeActivityData;
  activityRequests?: GanttActivityRequest[];
  showActivityRequestsOption?: boolean;
}>();

defineEmits<{
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
  'range-changed': [min: Date, max: Date];
}>();

// The Gantt is heavy; give the rest of the page its first frame.
const isReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    isReady.value = true;
  });
});

const filters = useTimelineFilters();

// In place rather than a modal, so dialogs opened from the chart stack above it.
const fullscreen = ref(false);
</script>
