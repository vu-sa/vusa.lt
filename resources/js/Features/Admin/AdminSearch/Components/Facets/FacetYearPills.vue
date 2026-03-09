<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="item in sortedValues"
      :key="item.value"
      type="button"
      :class="[
        'px-3 py-1.5 text-sm font-medium rounded-full transition-all',
        'border',
        isSelected(item.value)
          ? 'bg-primary text-primary-foreground border-primary'
          : 'bg-background text-foreground border-border hover:bg-accent hover:border-accent-foreground/20',
        item.count === 0 && !isSelected(item.value) ? 'opacity-50' : '',
      ]"
      @click="$emit('toggle', parseInt(item.value, 10))"
    >
      {{ item.value }}
      <span
        :class="[
          'ml-1 text-xs',
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
      class="px-3 py-1.5 text-sm font-medium rounded-full border border-dashed border-border text-muted-foreground hover:bg-accent hover:text-foreground transition-all"
      @click="showAll = true"
    >
      +{{ hiddenCount }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

import type { AdminFacetValue } from '../../Types/AdminSearchTypes'

interface Props {
  values: AdminFacetValue[]
  selectedValues: number[]
  maxVisible?: number
}

interface Emits {
  (e: 'toggle', year: number): void
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 6,
})

defineEmits<Emits>()

// Local state
const showAll = ref(false)

// Sort values by year descending
const allSortedValues = computed(() => {
  return [...props.values].sort((a, b) => {
    const yearA = parseInt(a.value, 10)
    const yearB = parseInt(b.value, 10)
    return yearB - yearA
  })
})

// Check if we have more years than max
const hasMoreYears = computed(() => {
  return props.values.length > props.maxVisible
})

// Hidden count
const hiddenCount = computed(() => {
  return props.values.length - props.maxVisible
})

// Values to display
const sortedValues = computed(() => {
  if (showAll.value || !hasMoreYears.value) {
    return allSortedValues.value
  }

  // Ensure selected years are always visible
  const selected = allSortedValues.value.filter(v =>
    props.selectedValues.includes(parseInt(v.value, 10))
  )
  const unselected = allSortedValues.value.filter(v =>
    !props.selectedValues.includes(parseInt(v.value, 10))
  )

  // Show selected + recent years up to max
  const remaining = props.maxVisible - selected.length
  return [...selected, ...unselected.slice(0, Math.max(0, remaining))].sort((a, b) => {
    const yearA = parseInt(a.value, 10)
    const yearB = parseInt(b.value, 10)
    return yearB - yearA
  })
})

// Check if a year is selected
const isSelected = (value: string): boolean => {
  return props.selectedValues.includes(parseInt(value, 10))
}
</script>
