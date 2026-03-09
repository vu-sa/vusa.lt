<template>
  <div class="space-y-1">
    <label
      v-for="item in displayedValues"
      :key="item.value"
      :class="[
        'flex items-center gap-2.5 p-2 rounded-md cursor-pointer transition-colors',
        'hover:bg-accent/50',
        item.isSelected ? 'bg-accent' : '',
      ]"
    >
      <Checkbox
        :model-value="item.isSelected"
        @update:model-value="$emit('toggle', item.value)"
      />
      <div class="flex items-center justify-between flex-1 min-w-0">
        <span
          :class="[
            'text-sm truncate',
            item.isSelected ? 'font-medium text-foreground' : 'text-muted-foreground',
            item.count === 0 && !item.isSelected ? 'opacity-50' : '',
          ]"
        >
          {{ labelFormatter(item.value) }}
        </span>
        <Badge
          :variant="item.count > 0 ? 'secondary' : 'outline'"
          class="ml-2 text-xs shrink-0"
        >
          {{ formatCount(item.count) }}
        </Badge>
      </div>
    </label>

    <!-- Show More Button -->
    <Button
      v-if="hasMoreValues"
      variant="ghost"
      size="sm"
      class="w-full text-xs mt-1"
      @click="showAll = !showAll"
    >
      <ChevronDown :class="['size-3 mr-1 transition-transform', showAll ? 'rotate-180' : '']" />
      {{ showAll ? $t('Rodyti mažiau') : $t('Rodyti daugiau (:count)', { count: hiddenCount }) }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { ChevronDown } from 'lucide-vue-next'

import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'

import type { AdminFacetValue } from '../../Types/AdminSearchTypes'

interface Props {
  values: AdminFacetValue[]
  selectedValues: string[]
  maxVisible?: number
  labelFormatter?: (value: string) => string
}

interface Emits {
  (e: 'toggle', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 8,
  labelFormatter: (value: string) => value,
})

defineEmits<Emits>()

// Local state
const showAll = ref(false)

// Add selection state to values
const valuesWithSelection = computed(() => {
  return props.values.map(v => ({
    ...v,
    isSelected: props.selectedValues.includes(v.value),
  }))
})

// Determine if we have more values than max visible
const hasMoreValues = computed(() => {
  return props.values.length > props.maxVisible
})

// Count of hidden values
const hiddenCount = computed(() => {
  return props.values.length - props.maxVisible
})

// Values to display (limited or all)
const displayedValues = computed(() => {
  if (showAll.value || !hasMoreValues.value) {
    return valuesWithSelection.value
  }

  // Show selected values + top values up to maxVisible
  const selected = valuesWithSelection.value.filter(v => v.isSelected)
  const unselected = valuesWithSelection.value.filter(v => !v.isSelected)

  // If selected count is already at or above max, show all selected
  if (selected.length >= props.maxVisible) {
    return selected
  }

  // Fill remaining slots with unselected values
  const remaining = props.maxVisible - selected.length
  return [...selected, ...unselected.slice(0, remaining)]
})

// Format count for display
const formatCount = (count: number): string => {
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}
</script>
