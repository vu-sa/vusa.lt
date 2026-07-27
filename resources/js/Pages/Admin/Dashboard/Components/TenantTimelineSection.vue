<template>
  <section v-if="availableTenants.length > 0" data-tour="tenant-gantt-section" class="space-y-8">
    <!-- Representative Activity Section -->
    <RepresentativeActivitySection
      v-if="representativeActivity"
      :stats="representativeActivity.stats"
      :users="representativeActivity.preview_users"
      :tenant-ids="filters.selectedTenantForGantt.value"
      :loading="filters.tenantInstitutionsLoading.value"
    />

    <!-- Gantt Chart Section -->
    <div class="space-y-4">
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
            :tenants="showTenantSelector ? availableTenants : undefined"
            :selected-tenants="filters.selectedTenantForGantt.value"
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :show-activity-status="filters.showActivityStatusTenant.value"
            :show-activity-status-option="!!representativeActivity"
            :show-tenant-headers="ganttSettings.showTenantHeaders.value"
            :require-tenant-selection="showTenantSelector"
            @update:selected-tenants="filters.setSelectedTenants"
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityTenant.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsTenant.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersTenant.value = val"
            @update:show-activity-status="(val: boolean) => filters.showActivityStatusTenant.value = val"
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
          :tenant-filter="[]"
          :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
          :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
          :institution-names :tenant-names :institution-tenant :institution-has-public-meetings
          :institution-has-activity
          :institution-periodicity
          :duty-members="enrichedDutyMembers" :inactive-periods :show-duty-members="filters.showDutyMembersTenant.value"
          :show-activity-status="filters.showActivityStatusTenant.value"
          :loading-range :meetings-loading
          :empty-message="$t('Šiame padalinyje nėra institucijų')" @create-meeting="$emit('create-meeting', $event)"
          @create-check-in="$emit('create-check-in', $event)"
          @fullscreen="$emit('fullscreen')"
          @range-changed="(min: Date, max: Date) => $emit('range-changed', min, max)" />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  GanttMeeting,
  AtstovavimasGap,
  AtstovavimasTenant,
  GanttInstitution,
  GanttDutyMember,
  InactivePeriod,
  RepresentativeActivityData,
} from '../types';
import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';
import RepresentativeActivitySection from './RepresentativeActivitySection.vue';

interface Props {
  availableTenants: AtstovavimasTenant[];
  tenantInstitutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimasGap[];
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Server-derived "has any activity" flags for the activity filter
  institutionHasActivity?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  // When true, hide the Gantt chart to save rendering resources (e.g., when fullscreen modal is open)
  isHidden?: boolean;
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
  // Date range currently being loaded (rendered as a shimmer band in the Gantt)
  loadingRange?: { from: Date; until: Date } | null;
  // Whether meetings are currently being fetched (delayed ~300ms by the caller)
  meetingsLoading?: boolean;
  // Representative activity data for stats and user list
  representativeActivity?: RepresentativeActivityData;
  showTenantSelector?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showTenantSelector: true,
});

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

const hasData = computed(() => props.tenantInstitutions?.length > 0);

const emit = defineEmits<{
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
  'fullscreen': [];
  'range-changed': [min: Date, max: Date];
}>();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  return props.tenantInstitutions.map(institution => ({ ...institution }));
});

const enrichedDutyMembers = computed(() => {
  return props.dutyMembers ?? [];
});
</script>
