<template>
  <CellTooltip :text="tooltipText" :enabled="isTruncated">
    <Badge ref="el" :variant :class="['truncate max-w-full inline-block', badgeClass]">
      {{ displayText }}
    </Badge>
  </CellTooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import CellTooltip from './CellTooltip.vue';

import { Badge } from '@/Components/ui/badge';
import { useIsTruncated } from '@/Composables/useIsTruncated';
import type { BadgeVariants } from '@/Components/ui/badge';

const props = withDefaults(defineProps<{
  text?: string | null;
  variant?: BadgeVariants['variant'];
  class?: string;
}>(), {
  variant: 'secondary',
});

const displayText = computed(() => props.text ?? '—');
const tooltipText = computed(() => props.text ?? undefined);
const badgeClass = computed(() => props.class);

const { el, isTruncated } = useIsTruncated();
</script>
