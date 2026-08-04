<template>
  <TooltipProvider>
    <Tooltip :delay-duration="500">
      <TooltipTrigger as-child>
        <Button v-if="!compact" variant="ghost" size="icon-xs">
          <IFluentInfo24Regular />
        </Button>
        <button
          v-else
          type="button"
          :class="[
            'inline-flex size-4 shrink-0 items-center justify-center rounded-full',
            'text-zinc-400 transition-colors hover:text-zinc-600',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950/10',
            'dark:text-zinc-500 dark:hover:text-zinc-300 dark:focus-visible:ring-zinc-50/10',
          ]"
        >
          <IFluentInfo24Regular class="size-3" />
        </button>
      </TooltipTrigger>
      <TooltipContent class="max-w-[600px]">
        <slot />
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="tsx">
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

defineProps<{
  color?: string;
  /** Shrink the trigger so it sits inline next to small body text. */
  compact?: boolean;
}>();
</script>
