<template>
  <Card v-if="institutions.length > 0" class="overflow-hidden" :class="{ 'h-full flex flex-col': height === '100%' }">
    <CardContent class="p-4" :class="{ 'flex-1 min-h-0': height === '100%' }">
      <MeetingsGantt
        :meetings="meetings"
        :gaps="gaps"
        :days-before="30"
        :days-after="60"
        :label-width="240"
  v-model:detailsExpanded="detailsExpanded"
  :expanded-row-height="56"
        :institutions="formattedInstitutions"
        :institution-names="institutionNames"
        :tenant-names="tenantNames"
        :institution-tenant="institutionTenant"
        :tenant-filter="tenantFilter"
        :show-legend="true"
        :show-today-line="true"
        :interactive="true"
        :show-only-with-activity="showOnlyWithActivity"
  :height="effectiveHeight"
        @create-meeting="$emit('create-meeting', $event)"
        @fullscreen="$emit('fullscreen')"
      />
    </CardContent>
  </Card>
  <p v-else class="text-sm text-muted-foreground">
    {{ emptyMessage }}
  </p>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import MeetingsGantt from "@/Components/Graphs/MeetingsGantt.vue";
import { Card, CardContent } from "@/Components/ui/card";

import type { 
  GanttMeeting, 
  GanttInstitution, 
  AtstovavimosGap 
} from '../types';

interface Props {
  institutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  tenantFilter: string[];
  showOnlyWithActivity: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
  emptyMessage: string;
  height?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'fullscreen': [];
}>();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  let institutions = props.institutions.map(i => ({
    id: i.id,
    name: i.name,
    tenant_id: i.tenant_id
  }));

  // Filter to show only institutions with meetings if showOnlyWithActivity is true
  if (props.showOnlyWithActivity) {
    const institutionsWithMeetings = new Set(
      props.meetings.map(meeting => meeting.institution_id)
    );
    
    institutions = institutions.filter(institution => 
      institutionsWithMeetings.has(institution.id)
    );
  }

  return institutions;
});

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
  const AXIS_TOP = 22;   // axis/header spacer in MeetingsGantt
  const MARGIN_BOTTOM = 6;
  const EXPANDED_ROW_HEIGHT = 56; // keep in sync with MeetingsGantt default

  // Base institution ids = explicit institutions ∪ referenced by meetings/gaps
  let idsArr: Array<string|number> = Array.from(new Set< string | number >([
    ...formattedInstitutions.value.map(i => i.id),
    ...props.meetings.map(m => m.institution_id),
    ...props.gaps.map(g => g.institution_id)
  ]))

  // Apply tenant filtering if provided
  if (props.tenantFilter?.length && props.institutionTenant) {
    const filter = new Set(props.tenantFilter.map(v => String(v)))
    idsArr = idsArr.filter(id => filter.has(String((props.institutionTenant as any)[id as any])))
  }

  // Apply showOnlyWithActivity if enabled
  if (props.showOnlyWithActivity) {
    const active = new Set<string|number>([
      ...props.meetings.map(m => m.institution_id),
      ...props.gaps.map(g => g.institution_id)
    ])
    idsArr = idsArr.filter(id => active.has(id))
  }

  // Tenant header rows (if grouping enabled via provided mappings)
  let tenantHeaderCount = 0;
  if (props.institutionTenant && props.tenantNames) {
    const tenantIds = new Set<string|number>();
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
