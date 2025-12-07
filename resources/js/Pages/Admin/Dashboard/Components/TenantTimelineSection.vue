<template>
  <section v-if="availableTenants.length > 0" class="space-y-4">
    <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
      <h2 class="text-xl font-semibold tracking-tight">
        {{
          selectedTenantId.length === 0 ? $t('Visi padaliniai') :
            selectedTenantId.length === 1 ? (availableTenants.find(t => String(t.id) === selectedTenantId[0])?.shortname ||
              $t('Padalinys')) :
              $t('Pasirinkti padaliniai')
        }} — {{ $t('laiko juosta') }}
      </h2>
      <div class="flex flex-wrap items-center gap-2 ml-auto">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button size="sm" variant="outline">
              {{
                selectedTenantId.length === 0 ? $t('Visi padaliniai') :
                  selectedTenantId.length === 1 ? availableTenants.find(t => String(t.id) ===
                    selectedTenantId[0])?.shortname || $t('Padalinys') :
                    `${selectedTenantId.length} ${$t('padaliniai')}`
              }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>{{ $t('Padaliniai') }} ({{ selectedTenantId.length }}/{{ availableTenants.length }})
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div class="max-h-48 overflow-y-auto">
              <DropdownMenuCheckboxItem v-for="t in availableTenants" :key="t.id"
                :model-value="selectedTenantId.includes(String(t.id))" @update:model-value="(checked: boolean) => {
                  toggleTenant(String(t.id), checked);
                }" @select.prevent>
                {{ t.shortname }}
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

    <!-- Deferred Gantt chart rendering for better initial load performance -->
    <TimelineGanttSkeleton v-if="!isReady" />
    <TimelineGanttChart v-else :institutions="formattedInstitutions" :meetings :gaps :tenant-filter="selectedTenantId"
      :show-only-with-activity :institution-names :tenant-names :institution-tenant
      :empty-message="$t('Šiame padalinyje nėra institucijų')" @create-meeting="$emit('create-meeting', $event)"
      @fullscreen="$emit('fullscreen')" />
  </section>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type {
  GanttMeeting,
  AtstovavimosGap,
  AtstovavimosTenant,
  GanttInstitution
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';
import TimelineGanttSkeleton from './TimelineGanttSkeleton.vue';

import { Button } from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";


interface Props {
  availableTenants: AtstovavimosTenant[];
  tenantInstitutions: GanttInstitution[];
  meetings: GanttMeeting[];
  gaps: AtstovavimosGap[];
  selectedTenantId: string[]; // Changed to array
  currentTenant: AtstovavimosTenant | undefined;
  showOnlyWithActivity: boolean;
  institutionNames: Record<string, string>;
  tenantNames: Record<string, string>;
  institutionTenant: Record<string, string>;
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
  'update:selectedTenantId': [value: string[]]; // Changed to array
  'update:showOnlyWithActivity': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number, suggestedAt: Date }];
  'fullscreen': [];
}>();

// Toggle tenant selection
function toggleTenant(tenantId: string, checked: boolean) {
  const newSelection = [...props.selectedTenantId];
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
  emit('update:selectedTenantId', newSelection);
}

// Format institutions for Gantt component
const formattedInstitutions = computed(() => {
  const formatted = props.tenantInstitutions.map(i => ({
    id: i.id,
    name: i.name,
    tenant_id: i.tenant_id
  }));
  return formatted;
});

// Debug checkbox states
const debugCheckboxStates = computed(() => {
  return props.availableTenants.map(t => ({
    id: t.id,
    shortname: t.shortname,
    stringId: String(t.id),
    isSelected: props.selectedTenantId.includes(String(t.id)),
    selectedArray: [...props.selectedTenantId]
  }));
});

// Watch for changes in selected tenants
watch(
  () => props.selectedTenantId,
  (newValue, oldValue) => {
    // Tenant selection changed
  },
  { deep: true, immediate: true }
);
</script>
