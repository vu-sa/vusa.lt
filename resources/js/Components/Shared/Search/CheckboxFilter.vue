<template>
  <div class="space-y-2" :class="containerClass">
    <!-- No options state -->
    <div v-if="options.length === 0" class="text-sm text-muted-foreground p-3 text-center italic">
      {{ emptyText }}
    </div>

    <!-- Options list -->
    <template v-else>
      <label
        v-for="option in displayedOptions"
        :key="option.value"
        :class="[
          'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200',
          'hover:bg-accent/50 hover:border-accent-foreground/20',
          option.isSelected
            ? 'bg-accent border-accent-foreground/20 shadow-sm'
            : 'hover:shadow-sm',
          option.count === 0 && !option.isSelected ? 'opacity-50' : '',
        ]"
      >
        <Checkbox
          :model-value="option.isSelected"
          class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
          @update:model-value="emit('toggle', option.value)"
        />
        <div class="flex items-center justify-between flex-1 min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <!-- Optional icon/image slot -->
            <slot name="option-prefix" :option />
            <span
              :class="[
                'font-medium text-sm truncate',
                option.isSelected ? 'text-foreground' : 'text-foreground',
              ]"
            >
              {{ option.label }}
            </span>
          </div>
          <Badge
            v-if="showCounts && option.count !== undefined"
            :variant="option.count > 0 ? 'outline' : 'outline'"
            class="text-xs font-medium shrink-0 ml-2"
          >
            {{ formatCount(option.count) }}
          </Badge>
        </div>
      </label>

      <!-- Show More Button -->
      <Button
        v-if="hasMoreOptions"
        variant="ghost"
        size="sm"
        class="w-full text-xs mt-1"
        @click="showAll = !showAll"
      >
        <ChevronDown :class="['size-3 mr-1 transition-transform', showAll ? 'rotate-180' : '']" />
        {{ showAll ? $t('Rodyti mažiau') : $t('Rodyti daugiau (:count)', { count: String(hiddenCount) }) }}
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';

import type { FilterOption, FacetValue } from './types';

import { Checkbox } from '@/Components/ui/checkbox';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

interface Props {
  /**
   * Options to display. Can be FilterOption[] with labels or FacetValue[] from search engine.
   */
  options: FilterOption[] | FacetValue[];
  /**
   * Currently selected values
   */
  selectedValues: (string | number)[];
  /**
   * Maximum number of options visible before "Show more" (0 = show all)
   */
  maxVisible?: number;
  /**
   * Whether to show counts next to options
   */
  showCounts?: boolean;
  /**
   * Text to show when no options available
   */
  emptyText?: string;
  /**
   * Optional formatter for option labels (when using FacetValue[])
   */
  labelFormatter?: (value: string) => string;
  /**
   * Additional CSS classes for container
   */
  containerClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 8,
  showCounts: true,
  emptyText: 'Nėra galimų parinkčių',
  labelFormatter: (value: string) => value,
  containerClass: '',
});

const emit = defineEmits<{
  toggle: [value: string | number];
}>();

// Local state for show more
const showAll = ref(false);

// Normalize options to have consistent shape with selection state
const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    // Check if it's a FacetValue (has no label property)
    const isFacetValue = !('label' in opt);
    const { value } = opt;
    const label = isFacetValue ? props.labelFormatter(String(opt.value)) : opt.label;
    const count = opt.count ?? 0;
    const isSelected = props.selectedValues.includes(value);

    return { value, label, count, isSelected };
  });
});

// Check if we have more options than maxVisible
const hasMoreOptions = computed(() => {
  return props.maxVisible > 0 && props.options.length > props.maxVisible;
});

// Count of hidden options
const hiddenCount = computed(() => {
  return props.options.length - props.maxVisible;
});

// Options to display (limited or all)
const displayedOptions = computed(() => {
  if (props.maxVisible === 0 || showAll.value || !hasMoreOptions.value) {
    return normalizedOptions.value;
  }

  // Always show selected options + fill remaining with unselected
  const selected = normalizedOptions.value.filter(v => v.isSelected);
  const unselected = normalizedOptions.value.filter(v => !v.isSelected);

  // If selected count is already at or above max, show all selected
  if (selected.length >= props.maxVisible) {
    return selected;
  }

  // Fill remaining slots with unselected options
  const remaining = props.maxVisible - selected.length;
  return [...selected, ...unselected.slice(0, remaining)];
});

// Format count for display
const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`;
  }
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`;
  }
  return count.toString();
};
</script>
