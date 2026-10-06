<template>
  <!--
    Beside the chart rather than under it: the dock took a fixed 16rem off every chart and
    left selection, suggestions and save competing for the bottom of the screen.
  -->
  <Sheet v-if="isCompact" :open @update:open="emit('update:open', $event)">
    <SheetContent side="bottom" class="flex max-h-[80dvh] flex-col gap-0 p-0" data-slot="dutiable-timeline-side-panel">
      <SheetHeader class="sr-only">
        <SheetTitle>{{ $t('dutiables.timeline.dock.open_panel') }}</SheetTitle>
        <SheetDescription>{{ $t('dutiables.timeline.help.title') }}</SheetDescription>
      </SheetHeader>
      <div class="min-h-0 flex-1 divide-y divide-border overflow-y-auto">
        <slot name="selection" />
        <slot name="suggestions" />
      </div>
    </SheetContent>
  </Sheet>

  <aside
    v-else
    data-slot="dutiable-timeline-side-panel"
    class="flex min-h-0 flex-col divide-y divide-border border border-border"
  >
    <div class="max-h-[60%] shrink-0 overflow-y-auto">
      <slot name="selection" />
    </div>
    <div class="min-h-0 flex-1">
      <slot name="suggestions" />
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';

import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';

defineProps<{
  /** Only the phone sheet opens and closes; the desktop column is always there. */
  open: boolean;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

// Asked as max-width so a runner without matchMedia gets the desktop column.
const isCompact = useMediaQuery('(max-width: 1023.98px)');

defineExpose({ isCompact });
</script>
