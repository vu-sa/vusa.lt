<template>
  <div v-if="institutions.length > 0" :class="{ 'h-full flex flex-col': height === '100%' }">
    <div :class="{ 'flex-1 min-h-0': height === '100%' }">
      <MeetingsGantt
        :meetings
        :gaps
        :institutions
        :institution-names
        :tenant-names
        :institution-has-public-meetings
        :institution-has-activity
        :institution-periodicity
        :show-only-with-activity
        :show-only-with-public-meetings
        :hide-internal-institutions
        :duty-members
        :inactive-periods
        :show-duty-members
        :show-activity-status
        :activity-requests
        :show-activity-requests
        :height
        :fullscreen-active
        :loading-range
        :meetings-loading
        @create-meeting="$emit('create-meeting', $event)"
        @create-check-in="$emit('create-check-in', $event)"
        @fullscreen="$emit('fullscreen')"
        @show-legend-modal="showLegendModal = true"
        @range-changed="(min: Date, max: Date) => $emit('range-changed', min, max)"
      />
    </div>

    <GanttLegendModal :is-open="showLegendModal" @update:is-open="showLegendModal = $event" />
  </div>
  <p v-else class="text-sm text-muted-foreground">
    {{ emptyMessage }}
  </p>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import type {
  GanttMeeting,
  GanttInstitution,
  AtstovavimasGap,
  GanttDutyMember,
  GanttActivityRequest,
  InactivePeriod,
} from '../types';

import MeetingsGantt from '@/Components/Graphs/MeetingsGantt.vue';
import GanttLegendModal from '@/Components/Graphs/GanttLegendModal.vue';

defineProps<{
  institutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimasGap[];
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings?: boolean;
  /** Hide VU SA's own bodies. Off by default; the chart draws everything unless asked. */
  hideInternalInstitutions?: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  /** "Has any past/planned activity", independent of which meeting windows are loaded */
  institutionHasActivity?: Record<string, boolean>;
  emptyMessage: string;
  /** '100%' in focus mode; otherwise the chart fits its rows up to a cap */
  height?: string;
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  showDutyMembers?: boolean;
  /** Activity rings on members (padaliniai view only) */
  showActivityStatus?: boolean;
  institutionPeriodicity?: Record<string | number, number>;
  loadingRange?: { from: Date; until: Date } | null;
  /** Whether meetings are being fetched (delayed ~300ms by the caller) */
  meetingsLoading?: boolean;
  fullscreenActive?: boolean;
  activityRequests?: GanttActivityRequest[];
  showActivityRequests?: boolean;
}>();

defineEmits<{
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
  'fullscreen': [];
  'range-changed': [min: Date, max: Date];
}>();

const showLegendModal = ref(false);
</script>
