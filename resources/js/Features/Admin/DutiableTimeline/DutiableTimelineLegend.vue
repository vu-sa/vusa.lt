<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button type="button" size="xs" variant="ghost" :aria-label="$t('dutiables.timeline.legend.title')">
        <Info class="size-3.5" />
      </Button>
    </PopoverTrigger>

    <PopoverContent align="end" class="w-56 space-y-2 p-3">
      <p class="text-xs font-semibold">
        {{ $t('dutiables.timeline.legend.title') }}
      </p>

      <ul class="space-y-1.5">
        <li v-for="entry in entries" :key="entry.key" class="flex items-center gap-2 text-[11px]">
          <span
            class="h-2.5 w-5 shrink-0 rounded-sm"
            :style="{
              backgroundColor: entry.fill,
              border: entry.stroke ? `1.5px ${entry.dashed ? 'dashed' : 'solid'} ${entry.stroke}` : undefined,
            }"
          />
          <span class="text-muted-foreground">{{ $t(`dutiables.timeline.legend.${entry.key}`) }}</span>
        </li>
      </ul>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Info } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

import type { TimelineColors } from './timelineColors';

const props = defineProps<{
  colors: TimelineColors;
}>();

/**
 * `former` sits second because it outranks ex-officio: an ended seat is muted whether or
 * not it was mirrored, and the dashed edge is what still says ex-officio.
 */
const entries = computed(() => [
  { key: 'active', fill: props.colors.active, stroke: undefined, dashed: false },
  { key: 'former', fill: props.colors.former, stroke: undefined, dashed: false },
  { key: 'derived', fill: props.colors.derived, stroke: props.colors.derived, dashed: true },
  { key: 'staged', fill: props.colors.staged, stroke: undefined, dashed: false },
  { key: 'cross_tenant', fill: props.colors.active, stroke: props.colors.crossTenantStroke, dashed: false },
]);
</script>
