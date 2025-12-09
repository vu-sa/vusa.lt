<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button size="sm" variant="outline">
        {{ triggerLabel }}
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-64">
      <!-- Tenant selection (optional) -->
      <template v-if="tenants && tenants.length > 0">
        <DropdownMenuLabel>{{ $t('Padaliniai') }} ({{ selectedTenants.length }}/{{ tenants.length }})</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <div class="max-h-48 overflow-y-auto">
          <DropdownMenuCheckboxItem 
            v-for="t in tenants" 
            :key="t.id"
            :model-value="selectedTenants.includes(String(t.id))" 
            @update:model-value="(checked: boolean) => toggleTenant(String(t.id), checked)"
            @select.prevent
          >
            {{ t.shortname ?? t.id }}
          </DropdownMenuCheckboxItem>
        </div>
        <DropdownMenuSeparator />
      </template>
      
      <!-- Display settings label when no tenants -->
      <template v-else>
        <DropdownMenuLabel>{{ $t('Rodymo nustatymai') }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
      </template>

      <!-- Filter options -->
      <DropdownMenuCheckboxItem 
        :model-value="showOnlyWithActivity"
        @update:model-value="(val: boolean) => $emit('update:showOnlyWithActivity', val)" 
        @select.prevent
      >
        {{ $t('Rodyti tik aktyvius') }}
      </DropdownMenuCheckboxItem>
      <DropdownMenuCheckboxItem 
        :model-value="showOnlyWithPublicMeetings"
        @update:model-value="(val: boolean) => $emit('update:showOnlyWithPublicMeetings', val)" 
        @select.prevent
      >
        {{ $t('Rodyti tik viešas institucijas') }}
      </DropdownMenuCheckboxItem>
      
      <DropdownMenuSeparator />
      
      <DropdownMenuCheckboxItem 
        :model-value="showDutyMembers"
        @update:model-value="(val: boolean) => $emit('update:showDutyMembers', val)" 
        @select.prevent
      >
        {{ $t('Rodyti narius') }}
      </DropdownMenuCheckboxItem>

      <!-- Reset button -->
      <template v-if="showReset">
        <DropdownMenuSeparator />
        <div class="p-1">
          <Button size="sm" variant="ghost" class="w-full justify-start text-muted-foreground" @click="$emit('reset')">
            <IFluentArrowReset20Regular class="mr-2 h-4 w-4" />
            {{ $t('Atstatyti filtrus') }}
          </Button>
        </div>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import IFluentArrowReset20Regular from '~icons/fluent/arrow-reset-20-regular';

interface Tenant {
  id: string | number;
  shortname?: string;
}

interface Props {
  // Tenant selection
  tenants?: Tenant[];
  selectedTenants?: string[];
  // Filter states
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings: boolean;
  showDutyMembers: boolean;
  // UI options
  showReset?: boolean;
  triggerLabelOverride?: string;
}

const props = withDefaults(defineProps<Props>(), {
  tenants: undefined,
  selectedTenants: () => [],
  showReset: true,
});

const emit = defineEmits<{
  'update:selectedTenants': [value: string[]];
  'update:showOnlyWithActivity': [value: boolean];
  'update:showOnlyWithPublicMeetings': [value: boolean];
  'update:showDutyMembers': [value: boolean];
  'reset': [];
}>();

// Computed trigger label
const triggerLabel = computed(() => {
  if (props.triggerLabelOverride) return props.triggerLabelOverride;
  
  if (!props.tenants || props.tenants.length === 0) {
    return $t('Filtrai');
  }
  
  if (props.selectedTenants.length === 0) {
    return $t('Visi padaliniai');
  }
  
  if (props.selectedTenants.length === 1) {
    const tenant = props.tenants.find(t => String(t.id) === props.selectedTenants[0]);
    return tenant?.shortname || $t('Padalinys');
  }
  
  return `${props.selectedTenants.length} ${$t('padaliniai')}`;
});

// Toggle tenant selection
function toggleTenant(tenantId: string, checked: boolean) {
  const newSelection = [...props.selectedTenants];
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
  emit('update:selectedTenants', newSelection);
}
</script>
