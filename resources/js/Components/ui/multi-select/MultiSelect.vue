<template>
  <Combobox v-model="selectedItems" multiple :filter-function="filterFunction">
    <ComboboxAnchor
      class="relative flex min-h-10 w-full flex-wrap items-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:outline-none focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
      :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
      <!-- Selected tags -->
      <Badge
        v-for="item in selectedItems"
        :key="getItemValue(item)"
        variant="secondary"
        class="flex shrink-0 items-center gap-1 pr-1"
      >
        <span class="max-w-[150px] truncate">{{ getItemLabel(item) }}</span>
        <button
          v-if="!disabled"
          type="button"
          class="ml-0.5 rounded-full p-0.5 outline-none hover:bg-muted-foreground/20"
          @click.prevent.stop="removeItem(item)"
        >
          <XIcon class="h-3 w-3" />
          <span class="sr-only">{{ $t('Remove') }} {{ getItemLabel(item) }}</span>
        </button>
      </Badge>
      
      <!-- Search input -->
      <ComboboxInput
        v-if="!disabled"
        class="min-w-[80px] flex-1 bg-transparent text-sm placeholder:text-muted-foreground focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        :placeholder="selectedItems.length === 0 ? placeholder : ''"
        :disabled="disabled"
      />
      
      <!-- Trigger button - inline with tags/input, pushed to end -->
      <ComboboxTrigger v-if="!disabled" class="ml-auto shrink-0 text-muted-foreground hover:text-foreground focus:outline-none">
        <ChevronDownIcon class="h-4 w-4" />
      </ComboboxTrigger>
    </ComboboxAnchor>
    
    <!-- Dropdown list using ComboboxList which wraps Portal + Content -->
    <ComboboxList class="w-[--reka-combobox-trigger-width]">
      <ComboboxViewport class="max-h-60 p-1">
        <ComboboxEmpty class="flex flex-col items-center justify-center py-6 text-center text-sm text-muted-foreground">
          <p>{{ emptyText }}</p>
        </ComboboxEmpty>
        <ComboboxItem
          v-for="item in options"
          :key="getItemValue(item)"
          :value="item"
          class="relative flex cursor-default select-none items-center rounded-sm py-2 pl-3 pr-8 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
        >
          <slot name="option" :item="item">
            {{ getItemLabel(item) }}
          </slot>
          <ComboboxItemIndicator class="absolute right-2 flex h-4 w-4 items-center justify-center">
            <CheckIcon class="h-4 w-4" />
          </ComboboxItemIndicator>
        </ComboboxItem>
      </ComboboxViewport>
    </ComboboxList>
  </Combobox>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, watch, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { XIcon, ChevronDownIcon, CheckIcon } from 'lucide-vue-next';
import { ComboboxInput } from 'reka-ui';
import { 
  Combobox,
  ComboboxAnchor,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
  ComboboxEmpty,
} from '@/Components/ui/combobox';
import { Badge } from '@/Components/ui/badge';

const props = withDefaults(defineProps<{
  /** The v-model value - array of selected items */
  modelValue: T[];
  /** Available options to select from */
  options: T[];
  /** Property to use as the display label */
  labelField?: string;
  /** Property to use as the unique value/key */
  valueField?: string;
  /** Placeholder text when nothing is selected */
  placeholder?: string;
  /** Text to show when no options match the filter */
  emptyText?: string;
  /** Whether the component is disabled */
  disabled?: boolean;
}>(), {
  labelField: 'label',
  valueField: 'id',
  placeholder: 'Select items...',
  emptyText: 'No items found.',
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: T[]): void;
}>();

// Internal state for selected items
const selectedItems = ref<T[]>([...props.modelValue]) as { value: T[] };

// Get the label of an item
const getItemLabel = (item: T): string => {
  return String(item[props.labelField] ?? item);
};

// Get the value/key of an item
const getItemValue = (item: T): string | number => {
  return item[props.valueField] ?? item;
};

// Filter function for combobox search
const filterFunction = (options: T[], searchTerm: string): T[] => {
  if (!searchTerm) return options;
  const term = searchTerm.toLowerCase();
  return options.filter(item => 
    getItemLabel(item).toLowerCase().includes(term)
  );
};

// Remove an item from selection
const removeItem = (itemToRemove: T) => {
  selectedItems.value = selectedItems.value.filter(
    item => getItemValue(item) !== getItemValue(itemToRemove)
  );
};

// Sync internal state to parent
watch(selectedItems, (newItems) => {
  emit('update:modelValue', newItems);
}, { deep: true });

// Sync parent value to internal state
watch(() => props.modelValue, (newValue) => {
  // Only update if the values actually differ to avoid infinite loops
  const currentValues = selectedItems.value.map(getItemValue);
  const newValues = newValue.map(getItemValue);
  
  if (JSON.stringify(currentValues) !== JSON.stringify(newValues)) {
    selectedItems.value = [...newValue];
  }
}, { deep: true });
</script>
