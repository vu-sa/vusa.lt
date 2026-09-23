<template>
  <span
    :class="cn(
      'inline-flex w-fit items-center gap-1.5 border px-2 py-1 text-[11px] font-bold uppercase tracking-wide leading-none',
      roleClasses[status.role],
      props.class,
    )"
    data-slot="status-badge"
    :data-status-role="status.role"
  >
    <component :is="status.icon" class="size-3.5 shrink-0" aria-hidden="true" />
    <span>{{ $t(status.label) }}</span>
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import type { HTMLAttributes } from 'vue';

import type { StatusPresentation, StatusRole } from '@/Constants/statuses';
import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  status: StatusPresentation;
  class?: HTMLAttributes['class'];
}>();

const roleClasses: Record<StatusRole, string> = {
  neutral: 'border-status-neutral-border bg-status-neutral-surface text-status-neutral',
  info: 'border-status-info-border bg-status-info-surface text-status-info',
  progress: 'border-status-progress-border bg-status-progress-surface text-status-progress',
  attention: 'border-status-attention-border bg-status-attention-surface text-status-attention',
  success: 'border-status-success-border bg-status-success-surface text-status-success',
  danger: 'border-status-danger-border bg-status-danger-surface text-status-danger',
};
</script>
