<template>
  <section class="space-y-6" aria-labelledby="user-timeline-heading">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 id="user-timeline-heading" class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
        {{ $t('Tavo institucijos') }} — {{ $t('laiko juosta') }}
      </h2>
      <div class="flex items-center gap-2">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button size="sm" variant="outline" class="border-gray-300 hover:border-gray-400 focus:border-gray-500">
              {{ $t('Filtrai') }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>{{ $t('Padaliniai') }}</DropdownMenuLabel>
            <div class="px-2 py-1 max-h-64 overflow-y-auto space-y-1">
              <DropdownMenuCheckboxItem v-for="t in availableTenantsUser" :key="t.id"
                :model-value="tenantFilter.includes(String(t.id))"
                @update:model-value="(val: boolean) => toggleTenantFilter(String(t.id), val)" @select.prevent>
                {{ t.shortname ?? t.id }}
              </DropdownMenuCheckboxItem>
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem :model-value="showOnlyWithActivity"
              @update:model-value="(val: boolean) => $emit('update:showOnlyWithActivity', val)" @select.prevent>
              {{ $t('Rodyti tik aktyvius') }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <TimelineGanttChart :institutions="formattedInstitutions" :meetings :gaps :tenant-filter :show-only-with-activity
      :institution-names :tenant-names :institution-tenant
      :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')" @create-meeting="$emit('create-meeting', $event)"
      @fullscreen="$emit('fullscreen')" />
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  AtstovavimosInstitution,
  GanttMeeting,
  AtstovavimosGap,
  AtstovavimosTenant
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';

import { Button } from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import Icons from '@/Types/Icons/filled';


interface Props {
  institutions: AtstovavimosInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  availableTenantsUser: AtstovavimosTenant[];
  tenantFilter: string[];
  showOnlyWithActivity: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:tenantFilter': [value: string[]];
  'update:showOnlyWithActivity': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'fullscreen': [];
}>();

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  return props.institutions.map(i => ({
    id: i.id,
    name: String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id),
    tenant_id: i.tenant?.id
  }));
});

function toggleTenantFilter(id: string, checked: boolean) {
  const set = new Set(props.tenantFilter);
  if (checked) set.add(id);
  else set.delete(id);
  emit('update:tenantFilter', Array.from(set));
}
</script>
