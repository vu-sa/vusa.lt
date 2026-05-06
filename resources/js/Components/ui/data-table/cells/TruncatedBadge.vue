<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Badge :variant :class="['truncate max-w-full inline-block', badgeClass]">
          {{ displayText }}
        </Badge>
      </TooltipTrigger>
      <TooltipContent v-if="tooltipText" side="top" align="start" class="max-w-md">
        <p>{{ tooltipText }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { Badge } from '@/Components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';
import type { BadgeVariants } from '@/Components/ui/badge';

const props = withDefaults(defineProps<{
  text?: string | null;
  variant?: BadgeVariants['variant'];
  class?: string;
}>(), {
  variant: 'secondary',
});

const displayText = computed(() => props.text ?? '—');
const tooltipText = computed(() => props.text ?? undefined);
const badgeClass = computed(() => props.class);
</script>
