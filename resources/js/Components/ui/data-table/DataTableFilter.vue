<template>
  <DropdownMenu>
    <DropdownMenuTrigger asChild>
      <Button variant="outline" size="sm" :class="{'border-primary': isActive}" data-filter-button>
        <slot />
        <!-- Add filter count badge -->
        <div 
          v-if="isActive" 
          class="ml-1.5 flex items-center justify-center h-5 min-w-5 rounded-full bg-primary text-primary-foreground text-xs font-medium"
        >
          {{ filterCount }}
        </div>
        <ChevronDownIcon class="ml-2 h-4 w-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="start" class="w-[200px] p-2 max-h-[350px]">
      <!-- Multi-select mode -->
      <div v-if="multiple" class="space-y-2 max-h-[300px] overflow-auto pb-12"> <!-- Add padding-bottom to prevent overlap -->
        <CheckboxGroupRoot
          v-model="selectedValues"
          class="flex flex-col gap-2.5"
        >
          <div v-for="option in options" :key="option.value" class="flex items-center gap-2">
            <Checkbox
              :id="`filter-${option.value}`"
              :value="option.value"
            />
            <Label :for="`filter-${option.value}`">{{ option.label }}</Label>
          </div>
        </CheckboxGroupRoot>
        <!-- Clear and Apply buttons for multi-select -->
        <!-- Position absolutely at the bottom -->
        <div v-if="multiple" class="absolute bottom-0 left-0 right-0 flex justify-between p-2 border-t border-border bg-background">
          <Button
            variant="ghost"
            size="sm"
            @click="clearSelection"
            :disabled="!hasSelection"
          >
            {{ $t('Clear') }}
          </Button>
          <Button
            variant="default"
            size="sm"
            @click="applyFilters"
            :disabled="!hasChanges"
          >
            {{ $t('Apply') }}
          </Button>
        </div>
      </div>

      <!-- Single-select mode -->
      <div v-else class="space-y-1">
        <DropdownMenuItem
          v-for="option in options"
          :key="option.value"
          @click="handleSingleSelect(option.value)"
          :class="{ 'bg-accent text-accent-foreground': value === option.value }"
        >
          {{ option.label }}
        </DropdownMenuItem>

        <DropdownMenuSeparator v-if="options.length > 0" class="my-1" />

        <DropdownMenuItem
          @click="clearSelection"
          :disabled="!hasSelection"
        >
          {{ $t('Clear Selection') }}
        </DropdownMenuItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDownIcon } from 'lucide-vue-next';
import { CheckboxGroupRoot } from 'reka-ui';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from '@/Components/ui/dropdown-menu';

export interface FilterOption {
  label: string;
  value: any;
}

const props = defineProps<{
  value: any;
  options: FilterOption[];
  multiple?: boolean;
  applyImmediately?: boolean;
  filterKey?: string; // Optional key to identify the filter
}>();

const emit = defineEmits<{
  'update:value': [value: any];
  'apply': [value: any, key?: string]; // Emit when filters are applied
  'clear': [key?: string]; // Emit when filters are cleared
}>();

// Keep a working copy of the selection for multi-select mode
const selectedValues = ref(Array.isArray(props.value) ? [...props.value] : []);

// Update the internal selection when the external value changes
watch(() => props.value, (newValue) => {
  if (props.multiple) {
    selectedValues.value = Array.isArray(newValue) ? [...newValue] : [];
  }
}, { deep: true });

// Check if there is any selection
const hasSelection = computed(() => {
  if (props.multiple) {
    return selectedValues.value.length > 0;
  }
  return props.value !== null && props.value !== undefined;
});

// Check if the current selection is different from the value prop
const hasChanges = computed(() => {
  if (props.multiple) {
    if (selectedValues.value.length !== props.value.length) return true;
    return selectedValues.value.some(v => !props.value.includes(v));
  }
  return false;
});

// Check if the filter is active (has applied values)
const isActive = computed(() => {
  if (props.multiple) {
    return Array.isArray(props.value) && props.value.length > 0;
  }
  return props.value !== null && props.value !== undefined;
});

// Compute the count of active filters
const filterCount = computed(() => {
  if (props.multiple) {
    return selectedValues.value.length;
  }
  return props.value !== null && props.value !== undefined ? 1 : 0;
});

// Handle single-select option click
const handleSingleSelect = (optionValue: any) => {
  // If already selected, toggle off (clear selection)
  if (props.value === optionValue) {
    clearSelection();
  } else {
    emit('update:value', optionValue);
    emit('apply', optionValue, props.filterKey);
  }
};

// Apply the current selection
const applyFilters = () => {
  emit('update:value', [...selectedValues.value]);
  emit('apply', selectedValues.value, props.filterKey);
};

// Clear the selection
const clearSelection = () => {
  if (props.multiple) {
    selectedValues.value = [];
    emit('update:value', []);
  } else {
    emit('update:value', null);
  }
  emit('clear', props.filterKey);
};
</script>

<style scoped>
/* Add scrollbar styling for filters with many options */
:deep(.overflow-auto) {
  scrollbar-width: thin;
}

:deep(.overflow-auto::-webkit-scrollbar) {
  width: 6px;
}

:deep(.overflow-auto::-webkit-scrollbar-track) {
  background: transparent;
}

:deep(.overflow-auto::-webkit-scrollbar-thumb) {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}
</style>