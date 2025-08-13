<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[95vw] w-full max-h-[95vh] flex flex-col">
      <DialogHeader>
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
      </DialogHeader>
      
      <div class="flex-1 min-h-0 mt-4">
        <div v-if="ganttType === 'user' && userInstitutions.length > 0" class="h-full">
          <TimelineGanttChart
            :institutions="userInstitutions"
            :meetings="userMeetings"
            :gaps="userGaps"
            :tenant-filter="userTenantFilter"
            :show-only-with-activity="showOnlyWithActivityUser"
            :institution-names="userInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="userInstitutionTenant"
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
            :tenant-filter="[]"
            :show-only-with-activity="showOnlyWithActivityTenant"
            :institution-names="tenantInstitutionNames"
            :tenant-names="tenantNames"
            :institution-tenant="tenantInstitutionTenant"
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

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import Icons from "@/Types/Icons/filled";

import TimelineGanttChart from './TimelineGanttChart.vue';
import type { 
  GanttMeeting, 
  GanttInstitution, 
  AtstovavimosGap,
  AtstovavimosTenant 
} from '../types';

interface Props {
  isOpen: boolean;
  ganttType: 'user' | 'tenant';
  currentTenant?: AtstovavimosTenant;
  
  // User data
  userInstitutions: GanttInstitution[];
  userMeetings: GanttMeeting[];
  userGaps: AtstovavimosGap[];
  userTenantFilter: string[];
  showOnlyWithActivityUser: boolean;
  userInstitutionNames: Record<string, string>;
  userInstitutionTenant: Record<string, string>;
  
  // Tenant data
  tenantInstitutions: GanttInstitution[];
  tenantMeetings: GanttMeeting[];
  tenantGaps: AtstovavimosGap[];
  showOnlyWithActivityTenant: boolean;
  tenantInstitutionNames: Record<string, string>;
  tenantInstitutionTenant: Record<string, string>;
  
  // Shared
  tenantNames: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
}>();
</script>
