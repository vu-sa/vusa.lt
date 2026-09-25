<template>
  <Combobox
    v-model="selectedItem"
    v-model:open="open"
    :by
    open-on-focus
    :ignore-filter="shouldVirtualize"
    :disabled
  >
    <ComboboxAnchor
      data-slot="select-trigger"
      :data-size="size"
      :class="cn(
        singleSelectTriggerVariants({ variant, size }),
        disabled && 'opacity-50 cursor-not-allowed',
        props.class,
      )"
      @click.self="open = true"
    >
      <slot name="prefix" :selected="selectedItem" />
      <ComboboxInput
        v-if="!disabled"
        :display-value="getDisplayValue"
        class="flex-1 min-w-0 bg-transparent placeholder:text-muted-foreground focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        :placeholder="!selectedItem ? placeholder : ''"
        @update:model-value="onInputUpdate"
      />
      <span
        v-else
        class="flex-1 truncate text-muted-foreground"
      >
        {{ selectedItem ? getItemLabel(selectedItem) : placeholder }}
      </span>
      <ComboboxTrigger
        v-if="!disabled"
        class="ml-auto shrink-0 p-1 -mr-1 text-muted-foreground opacity-50 hover:text-foreground hover:opacity-100 focus:outline-none"
      >
        <ChevronDownIcon class="h-4 w-4" />
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxList :class="['min-w-[var(--reka-popper-anchor-width)] w-full', props.contentClass]">
      <ComboboxViewport class="max-h-60 overflow-y-auto p-1 w-full">
        <ComboboxEmpty class="flex flex-col items-center justify-center py-6 text-center text-sm text-muted-foreground">
          <p>{{ emptyText }}</p>
        </ComboboxEmpty>

        <!-- Virtualized rendering for large lists -->
        <template v-if="shouldVirtualize">
          <ComboboxVirtualizer
            v-slot="{ option }"
            :options="filteredOptions"
            :estimate-size
            :text-content="(opt: T) => getItemLabel(opt)"
          >
            <ComboboxItem
              :value="option"
              :disabled="(option as any).disabled"
              :class="['w-full', props.itemClass]"
            >
              <slot name="option" :item="option">
                {{ getItemLabel(option) }}
              </slot>
            </ComboboxItem>
          </ComboboxVirtualizer>
        </template>

        <!-- Standard rendering for small lists -->
        <template v-else>
          <ComboboxItem
            v-for="item in filteredOptions"
            :key="getItemValue(item)"
            :value="item"
            :disabled="(item as any).disabled"
            :class="['w-full', props.itemClass]"
          >
            <slot name="option" :item>
              {{ getItemLabel(item) }}
            </slot>
          </ComboboxItem>
        </template>
      </ComboboxViewport>
    </ComboboxList>
  </Combobox>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
import type { HTMLAttributes } from 'vue';
import { ref, watch, computed } from 'vue';
import { cva } from 'class-variance-authority';
import { ChevronDownIcon } from 'lucide-vue-next';
import { ComboboxInput, ComboboxVirtualizer } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxItem,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
} from '@/Components/ui/combobox';

const singleSelectTriggerVariants = cva(
  'relative flex w-full items-center gap-2 border transition-colors outline-none disabled:cursor-not-allowed disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'border-border bg-background focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/40',
        surface: 'border-border bg-secondary/50 focus-within:bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
      },
      size: {
        default: 'h-11 px-3.5 text-sm',
        sm: 'h-9 px-3 text-xs md:text-sm',
        xs: 'h-8 px-2.5 text-xs',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

const props = withDefaults(defineProps<{
  /** The v-model value - single selected item or null */
  modelValue: T | null;
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
  /** Threshold for enabling virtualization (default: 50) */
  virtualizationThreshold?: number;
  /** Estimated size of each item for virtualization (default: 40) */
  estimateSize?: number;
  /** Additional classes for the dropdown content */
  contentClass?: string;
  /** Additional classes for each dropdown item (both virtualized and standard rendering) */
  itemClass?: string;
  /** Visual variant of the trigger */
  variant?: 'default' | 'surface';
  /** Sizing of the trigger control */
  size?: 'default' | 'sm' | 'xs';
  class?: HTMLAttributes['class'];
}>(), {
  labelField: 'label',
  valueField: 'id',
  placeholder: 'Select an item...',
  emptyText: 'No items found.',
  virtualizationThreshold: 50,
  estimateSize: 40,
  contentClass: undefined,
  itemClass: undefined,
  variant: 'default',
  size: 'default',
  class: undefined,
});

const emit = defineEmits<(e: 'update:modelValue', value: T | null) => void>();

// Get the label of an item
const getItemLabel = (item: T): string => {
  return String(item[props.labelField] ?? item);
};

// Get the value/key of an item
const getItemValue = (item: T | null | undefined): string | number | null => {
  if (!item) return null;
  return item[props.valueField] ?? item;
};

// Helper to compute display text from a value
const getDisplayValue = (val: unknown): string => {
  if (!val) {
    return '';
  }
  return getItemLabel(val as T);
};

// Internal state
const selectedItem = ref<T | null>(props.modelValue);
const open = ref(false);
const searchTerm = ref('');

// Determine if virtualization should be used
const shouldVirtualize = computed(() => props.options.length > props.virtualizationThreshold);

// Compare objects by valueField for reka-ui selection tracking
const by = computed(() => props.valueField);

// Filtered options — pre-filtered for virtualizer, or for non-virtualized rendering
const filteredOptions = computed(() => {
  if (!searchTerm.value) {
    return props.options;
  }
  const term = searchTerm.value.toLowerCase();
  return props.options.filter(item =>
    getItemLabel(item).toLowerCase().includes(term),
  );
});

const onInputUpdate = (value: string) => {
  if (open.value) {
    searchTerm.value = value;
  }
};

const reset = () => {
  selectedItem.value = null;
  searchTerm.value = '';
  open.value = false;
};

// Clear the filter when the dropdown opens so all options are shown.
// Reka-ui's resetSearchTermOnBlur + :display-value handle the input's visible
// text on close and on selection — no manual sync needed here.
watch(open, (isOpen) => {
  if (isOpen) {
    searchTerm.value = '';
  }
});

watch(selectedItem, (item) => {
  emit('update:modelValue', item);
});

// Sync parent value to internal state
watch(() => props.modelValue, (newValue) => {
  if (getItemValue(newValue as T) !== getItemValue(selectedItem.value as T)) {
    selectedItem.value = newValue;
  }
});

// Expose internal helpers for testing
defineExpose({ reset, getItemLabel, getItemValue, displayValue: getDisplayValue, shouldVirtualize });
</script>
