<template>
  <span
    v-if="badge"
    data-slot="task-count-badge"
    :data-status-role="badge.role"
    :class="[
      'inline-flex h-5 min-w-5 shrink-0 items-center justify-center gap-0.5 border px-1 text-xs font-semibold leading-none tabular-nums',
      badge.role === 'danger'
        ? 'border-status-danger-border bg-status-danger-surface text-status-danger'
        : 'border-status-attention-border bg-status-attention-surface text-status-attention',
    ]"
  >
    <Clock v-if="badge.role === 'danger'" class="size-3" aria-hidden="true" />
    <span aria-hidden="true">{{ badge.count > 99 ? '99+' : badge.count }}</span>
    <span class="sr-only">{{ badge.label }}</span>
  </span>
</template>

<script setup lang="ts">
import { Clock } from 'lucide-vue-next';

import { useTaskBadge } from '@/Composables/useTaskBadge';

const badge = useTaskBadge();
</script>
