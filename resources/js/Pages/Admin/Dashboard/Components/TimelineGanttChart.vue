<template>
  <Card v-if="institutions.length > 0" :class="{ 'h-full flex flex-col': height === '100%' }">
    <CardContent class="p-4" :class="{ 'flex-1 min-h-0': height === '100%' }">
      <MeetingsGantt
        v-model:details-expanded="detailsExpanded"
        :meetings
        :gaps
        :days-before="60"
        :days-after="60"
        :label-width="240"
        :expanded-row-height="56"
        :institutions="formattedInstitutions"
        :institution-names
        :tenant-names
        :institution-tenant
        :institution-has-public-meetings
        :institution-has-activity
        :institution-periodicity
        :tenant-filter
        :show-legend="true"
        :show-today-line="true"
        :interactive="true"
        :show-only-with-activity
        :show-only-with-public-meetings
        :hide-internal-institutions
        :duty-members
        :inactive-periods
        :show-duty-members
        :show-activity-status
        :height="effectiveHeight"
        :hide-fullscreen-button
        :loading-range :meetings-loading
        @create-meeting="$emit('create-meeting', $event)"
        @create-check-in="$emit('create-check-in', $event)"
        @fullscreen="$emit('fullscreen')"
        @show-legend-modal="showLegendModal = true"
        @range-changed="(min: Date, max: Date) => $emit('range-changed', min, max)"
      />
    </CardContent>

    <!-- Legend Modal -->
    <GanttLegendModal :is-open="showLegendModal" @update:is-open="showLegendModal = $event" />
  </Card>
  <p v-else class="text-sm text-muted-foreground">
    {{ emptyMessage }}
  </p>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import { useGanttSettings } from '../Composables/useGanttSettings';
import type {
  GanttMeeting,
  GanttInstitution,
  AtstovavimasGap,
  GanttDutyMember,
  InactivePeriod,
} from '../types';

import MeetingsGantt from '@/Components/Graphs/MeetingsGantt.vue';
import GanttLegendModal from '@/Components/Graphs/GanttLegendModal.vue';
import { Card, CardContent } from '@/Components/ui/card';

interface Props {
  institutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimasGap[];
  tenantFilter: string[];
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings?: boolean;
  /** Hide VU SA's own bodies. Off by default; the chart draws everything unless asked. */
  hideInternalInstitutions?: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  institutionHasPublicMeetings?: Record<string, boolean>;
  // Institutions with any past/planned activity (drives "show only with activity"
  // independent of which meeting windows are loaded)
  institutionHasActivity?: Record<string, boolean>;
  emptyMessage: string;
  height?: string;
  // Duty members display
  dutyMembers?: GanttDutyMember[];
  inactivePeriods?: InactivePeriod[];
  showDutyMembers?: boolean;
  // Activity status rings for duty members (only in tenant view)
  showActivityStatus?: boolean;
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
  // Date range currently being loaded (rendered as a shimmer band in the Gantt)
  loadingRange?: { from: Date; until: Date } | null;
  // Whether meetings are currently being fetched (delayed ~300ms by the caller)
  meetingsLoading?: boolean;
  // Hide fullscreen button (when already in fullscreen modal)
  hideFullscreenButton?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
  'fullscreen': [];
  'range-changed': [min: Date, max: Date];
}>();

// Legend modal state
const showLegendModal = ref(false);

// Get gantt settings for showTenantHeaders
const ganttSettings = useGanttSettings();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  let institutions = props.institutions.map(i => ({
    id: i.id,
    name: i.name,
    tenant_id: i.tenant_id,
    is_related: i.is_related,
    relationship_direction: i.relationship_direction,
    source_institution_id: i.source_institution_id,
    authorized: i.authorized,
  }));

  // Filter to show only institutions with activity if showOnlyWithActivity is true
  if (props.showOnlyWithActivity) {
    institutions = institutions.filter(institution => hasActivity(institution.id));
  }

  return institutions;
});

/**
 * Whether an institution has any past/planned activity. Prefers the
 * server-derived map (independent of loaded meeting windows); falls back to
 * the loaded meetings/gaps when no map was provided.
 */
function hasActivity(id: string | number): boolean {
  if (props.institutionHasActivity) {
    return Boolean(props.institutionHasActivity[id as string] ?? props.institutionHasActivity[String(id)]);
  }

  return props.meetings.some(meeting => String(meeting.institution_id) === String(id))
    || props.gaps.some(gap => String(gap.institution_id) === String(id));
}

// Detailed row state (single expanded institution)
const detailsExpanded = ref(false);

// Compute effective height: shrink for few rows; grow up to a cap when many rows
const effectiveHeight = computed(() => {
  // If height prop is provided (fullscreen mode), use it directly
  if (props.height) {
    return props.height;
  }

  // Otherwise, calculate based on content with a reasonable cap
  const ROW_HEIGHT = 28; // keep in sync with MeetingsGantt default
  const AXIS_TOP = 22; // axis/header spacer in MeetingsGantt
  const MARGIN_BOTTOM = 6;
  const EXPANDED_ROW_HEIGHT = 56; // keep in sync with MeetingsGantt default

  // Base institution ids = explicit institutions ∪ referenced by meetings/gaps
  let idsArr: Array<string | number> = Array.from(new Set<string | number>([
    ...formattedInstitutions.value.map(i => i.id),
    ...props.meetings.map(m => m.institution_id),
    ...props.gaps.map(g => g.institution_id),
  ]));

  // Apply tenant filtering if provided
  if (props.tenantFilter?.length && props.institutionTenant) {
    const filter = new Set(props.tenantFilter.map(v => String(v)));
    idsArr = idsArr.filter(id => filter.has(String((props.institutionTenant as any)[id as any])));
  }

  // Apply showOnlyWithActivity if enabled
  if (props.showOnlyWithActivity) {
    idsArr = idsArr.filter(id => hasActivity(id));
  }

  // Apply showOnlyWithPublicMeetings if enabled
  if (props.showOnlyWithPublicMeetings && props.institutionHasPublicMeetings) {
    const pubMap = props.institutionHasPublicMeetings;
    idsArr = idsArr.filter(id => pubMap[id] || pubMap[String(id)]);
  }

  // Mirrors useGanttFiltering; this copy exists only to size the chart's own container.
  if (props.hideInternalInstitutions) {
    const internal = new Set(props.institutions.filter(i => i.is_internal).map(i => String(i.id)));
    idsArr = idsArr.filter(id => !internal.has(String(id)));
  }

  // Tenant header rows (only if showTenantHeaders is enabled and grouping data available)
  let tenantHeaderCount = 0;
  if (ganttSettings.showTenantHeaders.value && props.institutionTenant && props.tenantNames) {
    const tenantIds = new Set<string | number>();
    for (const id of idsArr) {
      const t = (props.institutionTenant as any)[id as any];
      if (t != null) tenantIds.add(t);
    }
    tenantHeaderCount = tenantIds.size;
  }

  // Total rows = institutions + tenant headers (when present)
  const rowsCount = idsArr.length + tenantHeaderCount;
  // If detailsExpanded, all institution rows are taller
  const rowsHeight = rowsCount * (detailsExpanded.value ? EXPANDED_ROW_HEIGHT : ROW_HEIGHT);
  const contentHeightPx = Math.max(60, rowsHeight + AXIS_TOP + MARGIN_BOTTOM);

  // Grow up to a sensible cap when there are many rows; shrink when few
  const MAX_CAP = 720; // px
  const finalPx = Math.min(contentHeightPx, MAX_CAP);
  return `${finalPx}px`;
});
</script>
