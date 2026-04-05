<template>
  <!-- Mobile variant (simple styling) -->
  <AccordionItem v-if="variant === 'mobile'" :value>
    <AccordionTrigger class="text-sm font-medium">
      <div class="flex items-center gap-2">
        <component :is="icon" v-if="hasValidIcon" class="w-4 h-4 text-muted-foreground" />
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

  <!-- Desktop variant (enhanced styling with card-like appearance) -->
  <AccordionItem
    v-else
    :value
    class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
  >
    <AccordionTrigger class="px-5 py-4 hover:no-underline group">
      <div class="flex items-center gap-3 flex-1">
        <div v-if="hasValidIcon" class="p-1.5 rounded-lg transition-colors" :class="iconContainerClass">
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
      <div v-if="isLoading" class="space-y-2">
        <div
          v-for="i in skeletonCount"
          :key="i"
          class="h-12 w-full bg-muted/50 animate-pulse rounded-lg"
        />
      </div>
      <slot v-else />
    </AccordionContent>
  </AccordionItem>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';

import { Badge } from '@/Components/ui/badge';
import { AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';

interface Props {
  /** Unique value for accordion state */
  value: string;
  /** Filter section label */
  label: string;
  /** Optional description text (desktop only) */
  description?: string;
  /** Icon component to display (optional) */
  icon?: Component;
  /** Number to show in badge (0 = hidden) */
  badgeCount?: number;
  /** Whether content is loading */
  isLoading?: boolean;
  /** Number of skeleton items to show when loading */
  skeletonCount?: number;
  /** Variant: 'mobile' for simple, 'desktop' for enhanced */
  variant?: 'mobile' | 'desktop';
  /** CSS classes for icon container (desktop only) */
  iconContainerClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  badgeCount: 0,
  isLoading: false,
  skeletonCount: 3,
  variant: 'desktop',
  iconContainerClass: 'bg-primary/10 text-primary group-hover:bg-primary/15',
});

// Check if icon is a valid component (not undefined, null, or empty string)
const hasValidIcon = computed(() => {
  return props.icon != null && typeof props.icon !== 'string';
});
</script>
