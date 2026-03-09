<template>
  <component
    :is="onClick ? 'button' : 'div'"
    :class="[
      // Base structure
      'relative flex flex-col overflow-hidden rounded-xl border',
      'transition-all duration-300',
      // Mobile: minimum width for horizontal scroll
      'min-w-[140px] sm:min-w-0 snap-start',
      // Gradient background
      'bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-900 dark:to-zinc-950',
      // Shadow
      'shadow-sm dark:shadow-zinc-950/50',
      // Dynamic urgency border
      urgencyBorderClasses,
      // Interactive states (only when clickable)
      onClick && [
        'cursor-pointer',
        'hover:scale-[1.02] hover:shadow-md',
        'focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2',
        'dark:focus:ring-offset-zinc-900',
        'active:scale-[0.98]',
      ],
    ]"
    @click="onClick"
  >
    <!-- Status indicator corner accent -->
    <div
      :class="[
        'absolute top-0 right-0 w-14 h-14 -mr-7 -mt-7 rotate-45',
        'transition-colors duration-300',
        urgencyStatusClasses,
      ]"
      aria-hidden="true"
    />

    <!-- Content -->
    <div class="p-3 sm:p-4 flex-1 relative z-10">
      <!-- Header with icon and label -->
      <div class="flex items-center gap-2 sm:gap-2.5 mb-2 sm:mb-3">
        <div
          :class="[
            'flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg',
            'bg-zinc-100 dark:bg-zinc-800/60',
            'transition-colors duration-300',
          ]"
        >
          <component
            :is="icon"
            :class="['h-4 w-4 sm:h-4.5 sm:w-4.5', urgencyIconClasses]"
          />
        </div>
        <span class="text-xs sm:text-sm font-medium text-zinc-600 dark:text-zinc-400 leading-tight line-clamp-2">
          {{ label }}
        </span>
      </div>

      <!-- Value -->
      <div
        :class="[
          'text-2xl sm:text-3xl font-bold tracking-tight',
          'transition-colors duration-300',
          urgencyValueClasses,
        ]"
      >
        {{ formattedValue }}
      </div>

      <!-- Subtitle -->
      <div
        v-if="subtitle"
        :class="[
          'text-[10px] sm:text-xs mt-1 sm:mt-1.5 line-clamp-2',
          'transition-colors duration-300',
          urgencySubtitleClasses,
        ]"
      >
        {{ subtitle }}
      </div>
    </div>

    <!-- Optional footer slot -->
    <div
      v-if="$slots.footer"
      :class="[
        'px-4 py-2.5 border-t',
        'bg-zinc-50/60 dark:bg-zinc-900/60',
        'border-zinc-200 dark:border-zinc-800',
      ]"
    >
      <slot name="footer" />
    </div>

    <!-- Click indicator (arrow) when interactive -->
    <div
      v-if="onClick"
      :class="[
        'absolute bottom-3 right-3',
        'opacity-0 transform translate-x-1',
        'transition-all duration-300',
        'group-hover:opacity-100 group-hover:translate-x-0',
      ]"
    >
      <ChevronRight class="h-4 w-4 text-zinc-400" />
    </div>
  </component>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import {
  urgencyPalette,
  type UrgencyLevel
} from '@/Composables/useDashboardCardStyles'

interface Props {
  label: string
  value: string | number
  icon: Component
  urgency?: UrgencyLevel
  subtitle?: string
  onClick?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  urgency: 'neutral',
})

// Format numeric values with locale
const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString()
  }
  return props.value
})

// Urgency-based styling classes
const urgencyBorderClasses = computed(() => {
  return urgencyPalette.border[props.urgency]
})

const urgencyStatusClasses = computed(() => {
  return urgencyPalette.statusIndicator[props.urgency]
})

const urgencyIconClasses = computed(() => {
  return urgencyPalette.icon[props.urgency]
})

const urgencyValueClasses = computed(() => {
  // Values use a more prominent color for urgency states
  const classes: Record<UrgencyLevel, string> = {
    success: 'text-emerald-700 dark:text-emerald-400',
    warning: 'text-amber-700 dark:text-amber-400',
    danger: 'text-zinc-900 dark:text-zinc-100',
    neutral: 'text-zinc-900 dark:text-zinc-100'
  }
  return classes[props.urgency]
})

const urgencySubtitleClasses = computed(() => {
  return urgencyPalette.subtext[props.urgency]
})
</script>
