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
        <!-- Tenant filter dropdown for tenant view -->
        <div v-if="ganttType === 'tenant' && availableTenants.length > 0" class="flex items-center gap-2">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button size="sm" variant="outline">
                {{
                  tenantFilter.length === 0 ? $t('Visi padaliniai') :
                    tenantFilter.length === 1 ? availableTenants.find(t => String(t.id) === tenantFilter[0])?.shortname || $t('Padalinys') :
                      `${tenantFilter.length} ${$t('padaliniai')}`
                }}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-64">
              <DropdownMenuLabel>{{ $t('Padaliniai') }} ({{ tenantFilter.length }}/{{ availableTenants.length }})
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <div class="max-h-48 overflow-y-auto">
                <DropdownMenuCheckboxItem v-for="t in availableTenants" :key="t.id"
                  :model-value="tenantFilter.includes(String(t.id))" @update:model-value="(checked: boolean) => {
                    toggleTenantFilter(String(t.id), checked);
                  }" @select.prevent>
                  {{ t.shortname }}
                </DropdownMenuCheckboxItem>
              </div>
              <DropdownMenuSeparator />
              <DropdownMenuCheckboxItem :model-value="showOnlyWithActivityTenant"
                @update:model-value="(val: boolean) => $emit('update:showOnlyWithActivityTenant', val)" @select.prevent>
                {{ $t('Rodyti tik aktyvius') }}
              </DropdownMenuCheckboxItem>
              <DropdownMenuCheckboxItem :model-value="showOnlyWithPublicMeetingsTenant"
                @update:model-value="(val: boolean) => $emit('update:showOnlyWithPublicMeetingsTenant', val)" @select.prevent>
                {{ $t('Rodyti tik viešas institucijas') }}
              </DropdownMenuCheckboxItem>
            </DropdownMenuContent>
          </DropdownMenu>
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
import { ref } from 'vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
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
  
  // Shared
  tenantNames: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'update:tenantFilter': [value: string[]];
  'update:showOnlyWithActivityTenant': [value: boolean];
  'update:showOnlyWithPublicMeetingsTenant': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
}>();

// Local state for tenant filter
const tenantFilter = ref<string[]>(props.tenantFilter);

// Toggle tenant selection
function toggleTenantFilter(tenantId: string, checked: boolean) {
  const newSelection = [...tenantFilter.value];
  if (checked) {
    if (!newSelection.includes(tenantId)) {
      newSelection.push(tenantId);
    }
  } else {
    const index = newSelection.indexOf(tenantId);
    if (index > -1) {
      newSelection.splice(index, 1);
    }
  }
  tenantFilter.value = newSelection;
  emit('update:tenantFilter', newSelection);
}
</script>
