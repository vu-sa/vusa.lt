<template>
  <TooltipProvider v-if="enabled && text">
    <Tooltip>
      <TooltipTrigger as-child>
        <slot />
      </TooltipTrigger>
      <TooltipContent side="top" align="start" class="max-w-md">
        <p>{{ text }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
  <slot v-else />
</template>

<script setup lang="ts">
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

/**
 * Wraps a cell in a tooltip only when there is something worth revealing.
 *
 * A table renders hundreds of cells; mounting a tooltip around every one of
 * them both spams the reader on hover and costs a component tree per value.
 * When `enabled` is false the slot renders bare, with no tooltip machinery
 * attached at all.
 */
defineProps<{
  /** The hidden value to reveal. No tooltip renders when empty. */
  text?: string | null;
  /** Whether the value is actually hidden — usually a truncation measurement. */
  enabled?: boolean;
}>();
</script>
