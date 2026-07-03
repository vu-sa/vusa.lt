<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <span
          :class="[
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

// Static class map — Tailwind only generates classes it can find as literals in source.
// `block` is only set for single-line truncation: line-clamp needs its own
// `display: -webkit-box` and a later `block` utility would override it.
const lineClasses = {
  1: 'block truncate',
  2: 'line-clamp-2 break-words',
  3: 'line-clamp-3 break-words',
} as const;

const lineClass = computed(() => lineClasses[props.lines]);
</script>
