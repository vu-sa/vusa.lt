<template>
  <CellTooltip :text="tooltipText" :enabled="isTruncated">
    <span
      ref="el"
      :class="[
        lineClass,
        props.class,
      ]"
    >
      {{ displayText }}
    </span>
  </CellTooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import CellTooltip from './CellTooltip.vue';

import { useIsTruncated } from '@/Composables/useIsTruncated';

const props = withDefaults(defineProps<{
  text?: string | null;
  lines?: 1 | 2 | 3;
  class?: string;
}>(), {
  lines: 1,
});

const displayText = computed(() => props.text ?? '—');
const tooltipText = computed(() => props.text ?? undefined);

const { el, isTruncated } = useIsTruncated();

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
