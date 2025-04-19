<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<{
  show?: boolean;
  size?: 'sm' | 'default' | 'lg';
  class?: string;
}>()

const sizeClasses = {
  sm: 'h-4 w-4',
  default: 'h-6 w-6',
  lg: 'h-8 w-8'
}
</script>

<template>
  <div class="relative">
    <!-- Spinner overlay that shows only when needed -->
    <div v-if="show" 
      class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm">
      <div class="text-center">
        <svg
          :class="cn('animate-spin text-vusa-red', sizeClasses[size || 'default'])"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>
      <div v-if="$slots.description" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
        <slot name="description"></slot>
      </div>
    </div>
    
    <!-- Content is always rendered, but may be visually hidden when loading -->
    <div :class="[
      'transition-opacity duration-300', 
      show ? 'opacity-0 pointer-events-none' : 'opacity-100'
    ]">
      <slot></slot>
    </div>
  </div>
</template>