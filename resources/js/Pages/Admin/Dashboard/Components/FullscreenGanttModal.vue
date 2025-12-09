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
            :selected-tenants="tenantFilter"
            :show-only-with-activity="showOnlyWithActivityTenant"
            :show-only-with-public-meetings="showOnlyWithPublicMeetingsTenant ?? false"
            :show-duty-members="showDutyMembersTenant ?? true"
            :show-reset="false"
            @update:selected-tenants="handleTenantFilterUpdate"
            @update:show-only-with-activity="(val: boolean) => emit('update:showOnlyWithActivityTenant', val)"
            @update:show-only-with-public-meetings="(val: boolean) => emit('update:showOnlyWithPublicMeetingsTenant', val)"
            @update:show-duty-members="(val: boolean) => emit('update:showDutyMembersTenant', val)"
          />
          <GanttFilterDropdown
            v-else-if="ganttType === 'user'"
            :show-only-with-activity="showOnlyWithActivityUser ?? false"
            :show-only-with-public-meetings="showOnlyWithPublicMeetingsUser ?? false"
            :show-duty-members="showDutyMembersUser ?? true"
            :show-reset="false"
            @update:show-only-with-activity="(val: boolean) => emit('update:showOnlyWithActivityUser', val)"
            @update:show-only-with-public-meetings="(val: boolean) => emit('update:showOnlyWithPublicMeetingsUser', val)"
            @update:show-duty-members="(val: boolean) => emit('update:showDutyMembersUser', val)"
          />
        </div>
      </DialogHeader>
      
      <div class="flex-1 min-h-0 mt-4">
        <div v-if="ganttType === 'user' && userInstitutions.length > 0" class="h-full">
          <TimelineGanttChart
            :institutions="userInstitutions"
            :meetings="userMeetings"
            :gaps="userGaps"
            :tenant-filter="userTenantFilter"
            :show-only-with-activity="showOnlyWithActivityUser"
            :show-only-with-public-meetings="showOnlyWithPublicMeetingsUser"
            :institution-names="userInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="userInstitutionTenant"
            :institution-has-public-meetings="userInstitutionHasPublicMeetings"
            :institution-periodicity="userInstitutionPeriodicity"
            :duty-members="userDutyMembers"
            :inactive-periods="userInactivePeriods"
            :show-duty-members="showDutyMembersUser"
            :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')"
            height="100%"
            @create-meeting="$emit('create-meeting', $event)"
          />
        </div>
        
        <div v-else-if="ganttType === 'tenant'" class="h-full">
          <TimelineGanttChart
            :institutions="tenantInstitutions"
            :meetings="tenantMeetings"
            :gaps="tenantGaps"
            :tenant-filter="tenantFilter"
            :show-only-with-activity="showOnlyWithActivityTenant"
            :show-only-with-public-meetings="showOnlyWithPublicMeetingsTenant"
            :institution-names="tenantInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="tenantInstitutionTenant"
            :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
            :institution-periodicity="tenantInstitutionPeriodicity"
            :duty-members="tenantDutyMembers"
            :inactive-periods="tenantInactivePeriods"
            :show-duty-members="showDutyMembersTenant"
            :empty-message="$t('Šiame padalinyje nėra institucijų')"
            height="100%"
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
import { trans as $t } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

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
import type { 
  GanttMeeting, 
  GanttInstitution, 
  AtstovavimosGap,
  AtstovavimosTenant,
  GanttDutyMember,
  InactivePeriod 
} from '../types';

interface Props {
  isOpen: boolean;
  ganttType: 'user' | 'tenant';
  currentTenant?: AtstovavimosTenant;
  availableTenants: AtstovavimosTenant[];
  
  // User data
  userInstitutions: GanttInstitution[];
  userMeetings: GanttMeeting[];
  userGaps: AtstovavimosGap[];
  userTenantFilter: string[];
  showOnlyWithActivityUser: boolean;
  showOnlyWithPublicMeetingsUser?: boolean;
  userInstitutionNames: Record<string, string>;
  userInstitutionTenant: Record<string, string>;
  userInstitutionHasPublicMeetings?: Record<string, boolean>;
  userInstitutionPeriodicity?: Record<string | number, number>;
  userDutyMembers?: GanttDutyMember[];
  userInactivePeriods?: InactivePeriod[];
  showDutyMembersUser?: boolean;
  
  // Tenant data
  tenantInstitutions: GanttInstitution[];
  tenantMeetings: GanttMeeting[];
  tenantGaps: AtstovavimosGap[];
  tenantFilter: string[];
  showOnlyWithActivityTenant: boolean;
  showOnlyWithPublicMeetingsTenant?: boolean;
  tenantInstitutionNames: Record<string, string>;
  tenantInstitutionTenant: Record<string, string>;
  tenantInstitutionHasPublicMeetings?: Record<string, boolean>;
  tenantInstitutionPeriodicity?: Record<string | number, number>;
  tenantDutyMembers?: GanttDutyMember[];
  tenantInactivePeriods?: InactivePeriod[];
  showDutyMembersTenant?: boolean;
  
  // Shared
  tenantNames: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'update:tenantFilter': [value: string[]];
  'update:showOnlyWithActivityTenant': [value: boolean];
  'update:showOnlyWithPublicMeetingsTenant': [value: boolean];
  'update:showDutyMembersTenant': [value: boolean];
  'update:showOnlyWithActivityUser': [value: boolean];
  'update:showOnlyWithPublicMeetingsUser': [value: boolean];
  'update:showDutyMembersUser': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
}>();

// Local state for tenant filter - synced with props
const tenantFilter = ref<string[]>(props.tenantFilter);

// Sync local state with props when they change (e.g., from parent TenantTimelineSection)
watch(() => props.tenantFilter, (newVal) => {
  tenantFilter.value = [...newVal];
}, { deep: true });

// Handle tenant filter update from GanttFilterDropdown
function handleTenantFilterUpdate(newSelection: string[]) {
  tenantFilter.value = newSelection;
  emit('update:tenantFilter', newSelection);
}
</script>
