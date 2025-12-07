<template>
  <div class="space-y-2" :class="maxHeight ? `max-h-[${maxHeight}] overflow-y-auto` : ''">
    <template v-if="options.length > 0">
      <label 
        v-for="option in options" 
        :key="option.value" 
        :class="[
          'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200',
          'hover:bg-accent/50 hover:border-accent-foreground/20',
          selectedValues.includes(option.value) 
            ? 'bg-accent border-accent-foreground/20 shadow-sm' 
            : 'hover:shadow-sm'
        ]"
      >
        <Checkbox 
          :model-value="selectedValues.includes(option.value)"
          class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
          @update:model-value="emit('toggle', option.value)" 
        />
        <div class="flex items-center justify-between flex-1 min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <!-- Optional icon/image slot -->
            <slot name="option-prefix" :option="option" />
            <span class="font-medium text-sm text-foreground truncate">
              {{ option.label }}
            </span>
          </div>
          <Badge v-if="showCounts && option.count !== undefined" variant="outline" class="text-xs font-medium shrink-0 ml-2">
            {{ formatCount(option.count) }}
          </Badge>
        </div>
      </label>
    </template>
    <div v-else class="text-sm text-muted-foreground p-3 text-center italic">
      {{ emptyText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import type { FilterOption } from './types'

interface Props {
  options: FilterOption[]
  selectedValues: (string | number)[]
  showCounts?: boolean
  emptyText?: string
  maxHeight?: string
}

withDefaults(defineProps<Props>(), {
  showCounts: true,
  emptyText: 'No options available',
  maxHeight: ''
})

const emit = defineEmits<{
  toggle: [value: string | number]
}>()

const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  }
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}
</script>
