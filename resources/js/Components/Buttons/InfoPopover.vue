<template>
  <TooltipProvider>
    <Tooltip :delay-duration="500">
      <TooltipTrigger as-child>
        <Button variant="ghost" size="icon-xs">
          <IFluentInfo24Regular />
        </Button>
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
}>();
</script>
