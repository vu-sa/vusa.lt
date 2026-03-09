<template>
  <!-- Mobile version (simple styling) -->
  <AccordionItem v-if="variant === 'mobile'" :value="value">
    <AccordionTrigger class="text-sm font-medium">
      <div class="flex items-center gap-2">
        <component :is="icon" class="w-4 h-4 text-muted-foreground" />
        <span>{{ label }}</span>
        <Badge v-if="badgeCount > 0" variant="secondary" class="ml-auto mr-2">
          {{ badgeCount }}
        </Badge>
      </div>
    </AccordionTrigger>
    <AccordionContent class="pt-2">
      <slot />
    </AccordionContent>
  </AccordionItem>

  <!-- Desktop version (enhanced styling) -->
  <AccordionItem 
    v-else
    :value="value"
    class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
  >
    <AccordionTrigger class="px-5 py-4 hover:no-underline group">
      <div class="flex items-center gap-3 flex-1">
        <div 
          class="p-1.5 rounded-lg transition-colors"
          :class="iconContainerClass"
        >
          <component :is="icon" class="w-4 h-4" />
        </div>
        <div class="flex-1 text-left">
          <span class="font-semibold text-foreground text-base">{{ label }}</span>
          <p v-if="description" class="text-xs text-muted-foreground mt-0.5">
            {{ description }}
          </p>
        </div>
        <Badge v-if="badgeCount > 0" variant="default" class="font-medium text-xs px-2 py-1">
          {{ badgeCount }}
        </Badge>
      </div>
    </AccordionTrigger>
    <AccordionContent class="px-5 pb-4 pt-1">
      <div v-if="isLoading" class="space-y-1">
        <div v-for="i in skeletonCount" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
      </div>
      <slot v-else />
    </AccordionContent>
  </AccordionItem>
</template>

<script setup lang="ts">
import type { Component } from 'vue'
import { Badge } from '@/Components/ui/badge'
import {
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/ui/accordion'

interface Props {
  /** Unique value for accordion state */
  value: string
  /** Filter label */
  label: string
  /** Optional description (desktop only) */
  description?: string
  /** Icon component */
  icon: Component
  /** Number to show in badge (0 = hidden) */
  badgeCount?: number
  /** Whether content is loading */
  isLoading?: boolean
  /** Number of skeleton items to show when loading */
  skeletonCount?: number
  /** Variant: 'mobile' for simple, 'desktop' for enhanced */
  variant?: 'mobile' | 'desktop'
  /** Icon container color classes (desktop only) */
  iconContainerClass?: string
}

withDefaults(defineProps<Props>(), {
  badgeCount: 0,
  isLoading: false,
  skeletonCount: 3,
  variant: 'desktop',
  iconContainerClass: 'bg-primary/10 text-primary group-hover:bg-primary/15'
})
</script>
