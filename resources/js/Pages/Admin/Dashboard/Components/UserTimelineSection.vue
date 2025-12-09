<template>
  <section class="space-y-6" aria-labelledby="user-timeline-heading">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 id="user-timeline-heading" class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ $t('Tavo institucijos') }} — {{ $t('laiko juosta') }}
      </h2>
      <div class="flex items-center gap-2">
        <GanttFilterDropdown
          :tenants="availableTenantsUser"
          :selected-tenants="tenantFilter"
          :show-only-with-activity="showOnlyWithActivity"
          :show-only-with-public-meetings="showOnlyWithPublicMeetings"
          :show-duty-members="showDutyMembers"
          @update:selected-tenants="(val: string[]) => emit('update:tenantFilter', val)"
          @update:show-only-with-activity="(val: boolean) => emit('update:showOnlyWithActivity', val)"
          @update:show-only-with-public-meetings="(val: boolean) => emit('update:showOnlyWithPublicMeetings', val)"
          @update:show-duty-members="(val: boolean) => emit('update:showDutyMembers', val)"
          @reset="emit('reset-filters')"
        />
      </div>
    </div>

    <!-- Deferred Gantt chart rendering for better initial load performance -->
    <TimelineGanttSkeleton v-if="!isReady" />
    <TimelineGanttChart v-else :institutions="formattedInstitutions" :meetings :gaps :tenant-filter :show-only-with-activity
      :show-only-with-public-meetings
      :institution-names :tenant-names :institution-tenant :institution-has-public-meetings="institutionHasPublicMeetings"
      :duty-members :inactive-periods :show-duty-members :day-width="dayWidthPx"
      :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')" @create-meeting="$emit('create-meeting', $event)"
      @fullscreen="$emit('fullscreen')" @update:day-width="emit('update:dayWidth', $event)" />
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  AtstovavimosInstitution,
  GanttMeeting,
  AtstovavimosGap,
  AtstovavimosTenant,
  GanttDutyMember,
  InactivePeriod
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';


interface Props {
  institutions: AtstovavimosInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  availableTenantsUser: AtstovavimosTenant[];
  tenantFilter: string[];
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  dayWidthPx?: number;
  inactivePeriods?: InactivePeriod[];
  showDutyMembers?: boolean;
}

const props = defineProps<Props>();

// Deferred rendering - wait for next frame after mount to render heavy Gantt chart
const isReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    isReady.value = true;
  });
});

const emit = defineEmits<{
  'update:tenantFilter': [value: string[]];
  'update:showOnlyWithActivity': [value: boolean];
  'update:showOnlyWithPublicMeetings': [value: boolean];
  'update:showDutyMembers': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'update:dayWidth': [value: number];
  'fullscreen': [];
  'reset-filters': [];
}>();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  return props.institutions.map(i => ({
    id: i.id,
    name: String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id),
    tenant_id: i.tenant?.id
  }));
});
</script>
