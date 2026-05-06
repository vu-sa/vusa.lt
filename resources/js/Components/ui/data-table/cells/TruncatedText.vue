<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <span
          :class="[
            'block',
            lineClass,
            props.class,
          ]"
        >
          {{ displayText }}
        </span>
      </TooltipTrigger>
      <TooltipContent v-if="tooltipText" side="top" align="start" class="max-w-md">
        <p>{{ tooltipText }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

const props = withDefaults(defineProps<{
  text?: string | null;
  lines?: 1 | 2 | 3;
  class?: string;
}>(), {
  lines: 1,
});

const displayText = computed(() => props.text ?? '—');
const tooltipText = computed(() => props.text ?? undefined);

const lineClass = computed(() => {
  if (props.lines === 1) {
    return 'truncate';
  }

  return `line-clamp-${props.lines}`;
});
</script>
