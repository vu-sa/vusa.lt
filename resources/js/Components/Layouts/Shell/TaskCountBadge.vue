<template>
  <span
    v-if="badge"
    data-slot="task-count-badge"
    :data-status-role="badge.role"
    :class="[
      'inline-flex shrink-0 items-center justify-center border font-semibold leading-none tabular-nums',
      compact ? 'h-4 min-w-4 px-0.5 text-[11px]' : 'h-5 min-w-5 px-1 text-xs',
      badge.role === 'danger'
        ? 'border-status-danger-border bg-status-danger-surface text-status-danger'
        : 'border-status-attention-border bg-status-attention-surface text-status-attention',
    ]"
  >
    <span aria-hidden="true">{{ badge.count > 99 ? '99+' : badge.count }}</span>
    <span class="sr-only">{{ badge.label }}</span>
  </span>
</template>

<script setup lang="ts">
import { useTaskBadge } from '@/Composables/useTaskBadge';

defineProps<{
  /** Sized to sit on an icon in the phone bottom bar. */
  compact?: boolean;
}>();

const badge = useTaskBadge();
</script>
