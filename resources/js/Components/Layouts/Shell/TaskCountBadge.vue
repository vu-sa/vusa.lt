<template>
  <span
    v-if="badge"
    :key="pops"
    data-slot="task-count-badge"
    :data-status-role="badge.role"
    :class="[
      'inline-flex shrink-0 items-center justify-center border font-semibold leading-none tabular-nums',
      compact ? 'h-4 min-w-4 px-0.5 text-[11px]' : 'h-5 min-w-5 px-1 text-xs',
      badge.role === 'danger'
        ? 'border-status-danger-border bg-status-danger-surface text-status-danger'
        : 'border-status-attention-border bg-status-attention-surface text-status-attention',
      pops > 0 && 'animate-badge-pop',
    ]"
  >
    <span aria-hidden="true">{{ badge.count > 99 ? '99+' : badge.count }}</span>
    <span class="sr-only">{{ badge.label }}</span>
  </span>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import { useTaskBadge } from '@/Composables/useTaskBadge';

defineProps<{
  /** Sized to sit on an icon in the phone bottom bar. */
  compact?: boolean;
}>();

const badge = useTaskBadge();

// Re-keyed only on a rise, so the badge pops for a new task but not on every page it reappears on.
const pops = ref(0);

watch(() => badge.value?.count ?? 0, (next, previous) => {
  if (next > previous) {
    pops.value++;
  }
});
</script>
