<template>
  <section v-if="availableTenants.length > 0" data-tour="tenant-gantt-section" class="space-y-4">
    <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
      <h2 class="text-xl font-semibold tracking-tight">
        {{
          selectedTenantId.length === 0 ? $t('Visi padaliniai') :
            selectedTenantId.length === 1 ? (availableTenants.find(t => String(t.id) === selectedTenantId[0])?.shortname ||
              $t('Padalinys')) :
              $t('Pasirinkti padaliniai')
        }} — {{ $t('laiko juosta') }}
      </h2>
      <div data-tour="gantt-filters" class="flex flex-wrap items-center gap-2 ml-auto">
        <GanttFilterDropdown
          :tenants="availableTenants"
          :selected-tenants="selectedTenantId"
          :show-only-with-activity="showOnlyWithActivity"
          :show-only-with-public-meetings="showOnlyWithPublicMeetings"
          :show-duty-members="showDutyMembers"
          :show-tenant-headers="ganttSettings.showTenantHeaders.value"
          @update:selected-tenants="(val: string[]) => emit('update:selectedTenantId', val)"
          @update:show-only-with-activity="(val: boolean) => emit('update:showOnlyWithActivity', val)"
          @update:show-only-with-public-meetings="(val: boolean) => emit('update:showOnlyWithPublicMeetings', val)"
          @update:show-duty-members="(val: boolean) => emit('update:showDutyMembers', val)"
          @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
          @reset="emit('reset-filters')"
        />
      </div>
    </div>

    <!-- Deferred Gantt chart rendering for better initial load performance -->
    <!-- When fullscreen modal is open, hide this to save rendering resources -->
    <TimelineGanttSkeleton v-if="!isReady || isHidden" />
    <div v-else-if="!isHidden" data-tour="gantt-chart">
      <TimelineGanttChart :institutions="formattedInstitutions" :meetings :gaps :tenant-filter="selectedTenantId"
        :show-only-with-activity :show-only-with-public-meetings :institution-names :tenant-names :institution-tenant :institution-has-public-meetings="institutionHasPublicMeetings"
        :institution-periodicity="institutionPeriodicity"
        :duty-members :inactive-periods :show-duty-members
        :empty-message="$t('Šiame padalinyje nėra institucijų')" @create-meeting="$emit('create-meeting', $event)"
        @fullscreen="$emit('fullscreen')" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted } from 'vue';
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


interface Props {
  availableTenants: AtstovavimosTenant[];
  tenantInstitutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  selectedTenantId: string[]; // Changed to array
  currentTenant: AtstovavimosTenant | undefined;
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  showDutyMembers?: boolean;
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

const emit = defineEmits<{
  'update:selectedTenantId': [value: string[]]; // Changed to array
  'update:showOnlyWithActivity': [value: boolean];
  'update:showOnlyWithPublicMeetings': [value: boolean];
  'update:showDutyMembers': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'fullscreen': [];
  'reset-filters': [];
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

// Debug checkbox states
const debugCheckboxStates = computed(() => {
  return props.availableTenants.map(t => ({
    id: t.id,
    shortname: t.shortname,
    stringId: String(t.id),
    isSelected: props.selectedTenantId.includes(String(t.id)),
    selectedArray: [...props.selectedTenantId]
  }));
});

// Watch for changes in selected tenants
watch(
  () => props.selectedTenantId,
  (newValue, oldValue) => {
    // Tenant selection changed
  },
  { deep: true, immediate: true }
);
</script>
