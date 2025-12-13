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
import { computed, ref, onMounted, watch } from 'vue';
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
import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';


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

// Format institutions for Gantt component - merge with related when enabled
const formattedInstitutions = computed(() => {
  const userInstitutions = props.institutions.map(i => ({
    id: i.id,
    name: String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id),
    tenant_id: i.tenant?.id,
    is_related: false
  }));

  // Add related institutions if filter is enabled
  if (filters.showRelatedInstitutionsUser.value && props.relatedInstitutions?.length) {
    const relatedFormatted = props.relatedInstitutions.map(i => ({
      id: i.id,
      name: String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id),
      tenant_id: i.tenant?.id,
      is_related: true,
      relationship_direction: i.relationship_direction,
      source_institution_id: i.source_institution_id
    }));
    return [...userInstitutions, ...relatedFormatted];
  }

  return userInstitutions;
});

// All meetings including related institutions
const allMeetings = computed(() => {
  if (!filters.showRelatedInstitutionsUser.value || !props.relatedInstitutions?.length) {
    return props.meetings;
  }

  // Add meetings from related institutions
  const relatedMeetings: GanttMeeting[] = props.relatedInstitutions.flatMap(inst => 
    (inst.meetings ?? []).map((m: any) => ({
      id: m.id,
      start_time: new Date(m.start_time),
      institution_id: inst.id,
      institution: String((inst as any)?.name?.lt ?? (inst as any)?.name?.en ?? (inst as any)?.name ?? inst.id),
      completion_status: m.completion_status
    }))
  );

  return [...props.meetings, ...relatedMeetings];
});

// Extract duty members from related institutions (same logic as useGanttChartData)
const extractDutyMembers = (institutions: AtstovavimosInstitution[]): GanttDutyMember[] => {
  const members: GanttDutyMember[] = [];
  
  for (const institution of institutions) {
    const inst = institution as any;
    for (const duty of (inst.duties ?? [])) {
      // Use users (all members including historical) for Gantt timeline display
      for (const user of (duty.users ?? [])) {
        const pivot = user.pivot ?? {};
        if (!pivot.start_date) continue;
        
        members.push({
          institution_id: String(institution.id),
          duty_id: String(duty.id),
          user: {
            id: String(user.id),
            name: String(user.name ?? ''),
            profile_photo_path: user.profile_photo_path ?? null
          },
          start_date: new Date(pivot.start_date),
          end_date: pivot.end_date ? new Date(pivot.end_date) : null
        });
      }
    }
  }
  
  return members;
};

// Calculate inactive periods from institutions (same logic as useGanttChartData)
const calculateInactivePeriods = (institutions: AtstovavimosInstitution[], members: GanttDutyMember[]): InactivePeriod[] => {
  const periods: InactivePeriod[] = [];
  
  for (const institution of institutions) {
    const instId = String(institution.id);
    const instMembers = members.filter(m => m.institution_id === instId);
    
    if (instMembers.length === 0) continue;
    
    const sortedMembers = [...instMembers].sort((a, b) => 
      a.start_date.getTime() - b.start_date.getTime()
    );
    
    const activePeriods: Array<{ from: Date; until: Date }> = [];
    
    for (const member of sortedMembers) {
      const from = member.start_date;
      const until = member.end_date ?? new Date();
      
      if (activePeriods.length === 0) {
        activePeriods.push({ from, until });
      } else {
        const last = activePeriods[activePeriods.length - 1]!;
        if (from <= last.until) {
          if (until > last.until) {
            last.until = until;
          }
        } else {
          periods.push({
            institution_id: instId,
            from: last.until,
            until: from
          });
          activePeriods.push({ from, until });
        }
      }
    }
  }
  
  return periods;
};

// Merge duty members with related institutions
const dutyMembers = computed(() => {
  const baseDutyMembers = props.dutyMembers ?? [];
  
  if (!filters.showRelatedInstitutionsUser.value || !props.relatedInstitutions?.length) {
    return baseDutyMembers;
  }
  
  const relatedDutyMembers = extractDutyMembers(props.relatedInstitutions);
  return [...baseDutyMembers, ...relatedDutyMembers];
});

// Merge inactive periods with related institutions
const inactivePeriods = computed(() => {
  const baseInactivePeriods = props.inactivePeriods ?? [];
  
  if (!filters.showRelatedInstitutionsUser.value || !props.relatedInstitutions?.length) {
    return baseInactivePeriods;
  }
  
  const relatedDutyMembers = extractDutyMembers(props.relatedInstitutions);
  const relatedInactivePeriods = calculateInactivePeriods(props.relatedInstitutions, relatedDutyMembers);
  return [...baseInactivePeriods, ...relatedInactivePeriods];
});

// Merge institution lookup maps with related institutions
const allInstitutionNames = computed(() => {
  const names = { ...props.institutionNames };
  if (filters.showRelatedInstitutionsUser.value && props.relatedInstitutions?.length) {
    for (const inst of props.relatedInstitutions) {
      names[inst.id] = String((inst as any)?.name?.lt ?? (inst as any)?.name?.en ?? (inst as any)?.name ?? inst.id);
    }
  }
  return names;
});

const allInstitutionTenant = computed(() => {
  const tenants = { ...props.institutionTenant };
  if (filters.showRelatedInstitutionsUser.value && props.relatedInstitutions?.length) {
    for (const inst of props.relatedInstitutions) {
      tenants[inst.id] = String(inst.tenant?.id ?? '');
    }
  }
  return tenants;
});

const allInstitutionHasPublicMeetings = computed(() => {
  const map = { ...props.institutionHasPublicMeetings };
  if (filters.showRelatedInstitutionsUser.value && props.relatedInstitutions?.length) {
    for (const inst of props.relatedInstitutions) {
      map[inst.id] = Boolean(inst.has_public_meetings);
    }
  }
  return map;
});

const allInstitutionPeriodicity = computed(() => {
  const map = { ...props.institutionPeriodicity };
  if (filters.showRelatedInstitutionsUser.value && props.relatedInstitutions?.length) {
    for (const inst of props.relatedInstitutions) {
      map[inst.id] = (inst as any).meeting_periodicity_days ?? 30;
    }
  }
  return map;
});
</script>
