<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[95vw] w-full h-[95vh] !flex flex-col">
      <DialogHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <DialogTitle class="flex items-center gap-2">
            <component :is="Icons.MEETING" class="h-5 w-5" />
            {{ 
              ganttType === 'user' 
                ? $t('Tavo institucijos — laiko juosta') 
                : `${currentTenant?.shortname || $t('Padalinys')} — ${$t('laiko juosta')}` 
            }}
          </DialogTitle>
          <DialogDescription>
            {{ 
              ganttType === 'user' 
                ? $t('Peržiūrėkite visas savo institucijas ir jų veiklą laiko juostoje') 
                : $t('Peržiūrėkite padalinio institucijas ir jų veiklą laiko juostoje') 
            }}
          </DialogDescription>
        </div>
        <!-- Filter dropdown for both views -->
        <div class="flex items-center gap-2">
          <GanttFilterDropdown
            v-if="ganttType === 'tenant'"
            :tenants="availableTenants"
            :selected-tenants="filters.selectedTenantForGantt.value"
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :show-tenant-headers="ganttSettings.showTenantHeaders.value"
            :show-reset="false"
            @update:selected-tenants="(val: string[]) => filters.selectedTenantForGantt.value = val"
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityTenant.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsTenant.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersTenant.value = val"
            @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
          />
          <GanttFilterDropdown
            v-else-if="ganttType === 'user'"
            :tenants="filters.availableTenantsUser.value"
            :selected-tenants="filters.userTenantFilter.value"
            :show-only-with-activity="filters.showOnlyWithActivityUser.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
            :show-duty-members="filters.showDutyMembersUser.value"
            :show-tenant-headers="ganttSettings.showTenantHeaders.value"
            :show-related-institutions="filters.showRelatedInstitutionsUser.value"
            :has-related-institutions="hasRelatedInstitutions"
            :show-reset="false"
            @update:selected-tenants="(val: string[]) => filters.userTenantFilter.value = val"
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityUser.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsUser.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersUser.value = val"
            @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
            @update:show-related-institutions="(val: boolean) => filters.showRelatedInstitutionsUser.value = val"
          />
        </div>
      </DialogHeader>
      
      <div class="flex-1 min-h-0 mt-4">
        <div v-if="ganttType === 'user' && userInstitutions.length > 0" class="h-full">
          <TimelineGanttChart
            :institutions="mergedUserInstitutions"
            :meetings="mergedUserMeetings"
            :gaps="userGaps"
            :tenant-filter="filters.userTenantFilter.value"
            :show-only-with-activity="filters.showOnlyWithActivityUser.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
            :institution-names="mergedUserInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="mergedUserInstitutionTenant"
            :institution-has-public-meetings="userInstitutionHasPublicMeetings"
            :institution-periodicity="userInstitutionPeriodicity"
            :duty-members="userDutyMembers"
            :inactive-periods="userInactivePeriods"
            :show-duty-members="filters.showDutyMembersUser.value"
            :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')"
            height="100%"
            :hide-fullscreen-button="true"
            @create-meeting="$emit('create-meeting', $event)"
          />
        </div>
        
        <div v-else-if="ganttType === 'tenant'" class="h-full">
          <TimelineGanttChart
            :institutions="tenantInstitutions"
            :meetings="tenantMeetings"
            :gaps="tenantGaps"
            :tenant-filter="filters.selectedTenantForGantt.value"
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :institution-names="tenantInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="tenantInstitutionTenant"
            :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
            :institution-periodicity="tenantInstitutionPeriodicity"
            :duty-members="tenantDutyMembers"
            :inactive-periods="tenantInactivePeriods"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :empty-message="$t('Šiame padalinyje nėra institucijų')"
            height="100%"
            :hide-fullscreen-button="true"
            @create-meeting="$emit('create-meeting', $event)"
          />
        </div>
        
        <div v-else-if="ganttType === 'user'" class="text-center py-8">
          <p class="text-muted-foreground">{{ $t('Neturi tiesiogiai priskirtų institucijų') }}</p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import Icons from "@/Types/Icons/filled";

import GanttFilterDropdown from './GanttFilterDropdown.vue';
import TimelineGanttChart from './TimelineGanttChart.vue';
import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';
import type { 
  GanttMeeting, 
  GanttInstitution, 
  AtstovavimosGap,
  AtstovavimosTenant,
  GanttDutyMember,
  InactivePeriod,
  AtstovavimosInstitution 
} from '../types';

interface Props {
  isOpen: boolean;
  ganttType: 'user' | 'tenant';
  availableTenants: AtstovavimosTenant[];
  
  // User data
  userInstitutions: GanttInstitution[];
  userMeetings: GanttMeeting[];
  userGaps: AtstovavimosGap[];
  userInstitutionNames: Record<string, string>;
  userInstitutionTenant: Record<string, string>;
  userInstitutionHasPublicMeetings?: Record<string, boolean>;
  userInstitutionPeriodicity?: Record<string | number, number>;
  userDutyMembers?: GanttDutyMember[];
  userInactivePeriods?: InactivePeriod[];
  userRelatedInstitutions?: GanttInstitution[];
  // Full related institutions data with meetings (for extracting agenda_items)
  userRelatedInstitutionsFull?: AtstovavimosInstitution[];
  // Flag to show related institutions filter even when data is lazy-loaded
  mayHaveRelatedInstitutions?: boolean;
  
  // Tenant data
  tenantInstitutions: GanttInstitution[];
  tenantMeetings: GanttMeeting[];
  tenantGaps: AtstovavimosGap[];
  tenantInstitutionNames: Record<string, string>;
  tenantInstitutionTenant: Record<string, string>;
  tenantInstitutionHasPublicMeetings?: Record<string, boolean>;
  tenantInstitutionPeriodicity?: Record<string | number, number>;
  tenantDutyMembers?: GanttDutyMember[];
  tenantInactivePeriods?: InactivePeriod[];
  
  // Shared
  tenantNames: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
}>();

// Get shared settings and filter state from providers
const ganttSettings = useGanttSettings();
const filters = useTimelineFilters();

// Computed: check if we have related institutions for user view (or might have when lazy-loaded)
const hasRelatedInstitutions = computed(() => {
  return (props.userRelatedInstitutions?.length ?? 0) > 0 || props.mayHaveRelatedInstitutions === true;
});

// Computed: current tenant for display
const currentTenant = computed(() => filters.currentTenant.value);

// Computed: merged institutions including related when filter is enabled
const mergedUserInstitutions = computed(() => {
  if (!filters.showRelatedInstitutionsUser.value || !props.userRelatedInstitutions?.length) {
    return props.userInstitutions;
  }
  return [...props.userInstitutions, ...props.userRelatedInstitutions];
});

// Computed: merged meetings including related institution meetings
const mergedUserMeetings = computed(() => {
  if (!filters.showRelatedInstitutionsUser.value || !props.userRelatedInstitutionsFull?.length) {
    return props.userMeetings;
  }

  // Add meetings from related institutions (same logic as UserTimelineSection)
  const relatedMeetings: GanttMeeting[] = props.userRelatedInstitutionsFull.flatMap(inst => 
    (inst.meetings ?? []).map((m: any) => {
      // Extract agenda items for tooltip (limit to first 4) - only if authorized
      const isAuthorized = (inst as any).authorized !== false
      const agendaItems = isAuthorized 
        ? (m.agenda_items ?? []).slice(0, 4).map((item: any) => ({
            id: String(item.id),
            title: String(item.title ?? ''),
            student_vote: item.student_vote ?? null,
            decision: item.decision ?? null,
          }))
        : []
      const totalAgendaCount = isAuthorized ? (m.agenda_items ?? []).length : 0

      return {
        id: m.id,
        start_time: new Date(m.start_time),
        institution_id: inst.id,
        institution: String((inst as any)?.name?.lt ?? (inst as any)?.name?.en ?? (inst as any)?.name ?? inst.id),
        completion_status: m.completion_status,
        agenda_items: agendaItems,
        agenda_items_count: totalAgendaCount,
        authorized: isAuthorized,
      };
    })
  );

  return [...props.userMeetings, ...relatedMeetings];
});

// Computed: merged institution names including related
const mergedUserInstitutionNames = computed(() => {
  if (!filters.showRelatedInstitutionsUser.value || !props.userRelatedInstitutions?.length) {
    return props.userInstitutionNames;
  }
  const result = { ...props.userInstitutionNames };
  props.userRelatedInstitutions.forEach(inst => {
    result[String(inst.id)] = inst.name;
  });
  return result;
});

// Computed: merged institution tenant mapping
const mergedUserInstitutionTenant = computed(() => {
  if (!filters.showRelatedInstitutionsUser.value || !props.userRelatedInstitutions?.length) {
    return props.userInstitutionTenant;
  }
  const result = { ...props.userInstitutionTenant };
  props.userRelatedInstitutions.forEach(inst => {
    if (inst.tenant_id) {
      result[String(inst.id)] = String(inst.tenant_id);
    }
  });
  return result;
});
</script>
