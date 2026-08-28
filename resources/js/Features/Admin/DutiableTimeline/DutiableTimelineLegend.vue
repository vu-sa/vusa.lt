<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button type="button" size="xs" variant="ghost" :aria-label="$t('dutiables.timeline.legend.title')">
        <Info class="size-3.5" />
      </Button>
    </PopoverTrigger>

    <PopoverContent align="end" class="w-72 space-y-3 p-3">
      <div class="space-y-2">
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
      </div>

      <!--
        The drag rules used to sit as a paragraph under the chart, where they cost a line of
        height on every visit to say something you need exactly once. Same words, asked for.
      -->
      <div class="space-y-2 border-t border-border pt-3">
        <p class="text-xs font-semibold">
          {{ $t('dutiables.timeline.help.title') }}
        </p>

        <ul class="space-y-1 text-[11px] text-muted-foreground">
          <li v-for="key in HELP_KEYS" :key="key">
            {{ $t(`dutiables.timeline.help.${key}`) }}
          </li>
        </ul>
      </div>
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

const HELP_KEYS = ['drag_body', 'drag_edges', 'precise', 'cancel'] as const;

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
