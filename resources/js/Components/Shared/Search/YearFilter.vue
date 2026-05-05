<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="item in displayedValues"
      :key="item.value"
      type="button"
      :class="[
        'px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200',
        'border',
        isSelected(item.value)
          ? 'bg-primary text-primary-foreground border-primary shadow-sm'
          : 'bg-background text-foreground border-border hover:bg-accent hover:border-accent-foreground/20 hover:shadow-sm',
        item.count === 0 && !isSelected(item.value) ? 'opacity-50' : '',
      ]"
      @click="emit('toggle', typeof item.value === 'number' ? item.value : parseInt(String(item.value), 10))"
    >
      {{ item.value }}
      <span
        v-if="showCounts"
        :class="[
          'ml-1.5 text-xs',
          isSelected(item.value) ? 'text-primary-foreground/80' : 'text-muted-foreground',
        ]"
      >
        ({{ item.count }})
      </span>
    </button>

    <!-- Show All Toggle -->
    <button
      v-if="hasMoreYears && !showAll"
      type="button"
      class="px-3 py-2 text-sm font-medium rounded-lg border border-dashed border-border text-muted-foreground hover:bg-accent hover:text-foreground transition-all duration-200"
      @click="showAll = true"
    >
      +{{ hiddenCount }} {{ $t('daugiau') }}
    </button>

    <!-- Show Less Toggle -->
    <button
      v-if="showAll && hasMoreYears"
      type="button"
      class="px-3 py-2 text-sm font-medium rounded-lg border border-dashed border-border text-muted-foreground hover:bg-accent hover:text-foreground transition-all duration-200"
      @click="showAll = false"
    >
      {{ $t('Rodyti mažiau') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { FacetValue, FilterOption } from './types';

interface Props {
  /**
   * Year values to display. Can be FacetValue[] or FilterOption[]
   */
  values: FacetValue[] | FilterOption[];
  /**
   * Currently selected years
   */
  selectedValues: number[];
  /**
   * Maximum number of years visible before "Show more" (0 = show all)
   */
  maxVisible?: number;
  /**
   * Whether to show counts next to years
   */
  showCounts?: boolean;
  /**
   * Sort order for years
   */
  sortOrder?: 'asc' | 'desc';
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 6,
  showCounts: true,
  sortOrder: 'desc',
});

const emit = defineEmits<{
  toggle: [year: number];
}>();

// Local state
const showAll = ref(false);

// Normalize and sort values
const allSortedValues = computed(() => {
  const normalized = props.values.map(v => ({
    value: typeof v.value === 'number' ? v.value : parseInt(String(v.value), 10),
    count: v.count ?? 0,
  }));

  return normalized.sort((a, b) => {
    return props.sortOrder === 'desc' ? b.value - a.value : a.value - b.value;
  });
});

// Check if we have more years than max
const hasMoreYears = computed(() => {
  return props.maxVisible > 0 && props.values.length > props.maxVisible;
});

// Hidden count
const hiddenCount = computed(() => {
  return props.values.length - props.maxVisible;
});

// Values to display
const displayedValues = computed(() => {
  if (props.maxVisible === 0 || showAll.value || !hasMoreYears.value) {
    return allSortedValues.value;
  }

  // Ensure selected years are always visible
  const selected = allSortedValues.value.filter(v => props.selectedValues.includes(v.value));
  const unselected = allSortedValues.value.filter(v => !props.selectedValues.includes(v.value));

  // Show selected + recent years up to max
  const remaining = props.maxVisible - selected.length;
  return [...selected, ...unselected.slice(0, Math.max(0, remaining))].sort((a, b) => {
    return props.sortOrder === 'desc' ? b.value - a.value : a.value - b.value;
  });
});

// Check if a year is selected
const isSelected = (value: number): boolean => {
  return props.selectedValues.includes(value);
};
</script>
