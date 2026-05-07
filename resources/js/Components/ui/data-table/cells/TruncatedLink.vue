<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Link
          v-if="!external"
          :href
          :class="[
            'block font-medium hover:underline',
            lineClass,
            props.class,
          ]"
        >
          {{ displayText }}
        </Link>
        <a
          v-else
          :href
          target="_blank"
          rel="noopener noreferrer"
          :class="[
            'block font-medium hover:underline',
            lineClass,
            props.class,
          ]"
        >
          {{ displayText }}
        </a>
      </TooltipTrigger>
      <TooltipContent v-if="tooltipText" side="top" align="start" class="max-w-md">
        <p>{{ tooltipText }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

const props = withDefaults(defineProps<{
  href: string;
  text?: string | null;
  lines?: 1 | 2 | 3;
  external?: boolean;
  class?: string;
}>(), {
  lines: 1,
  external: false,
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
