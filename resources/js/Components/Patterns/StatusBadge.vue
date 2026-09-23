<template>
  <span
    :class="cn(
      'inline-flex w-fit items-center gap-1.5 border px-2 py-1 text-[11px] font-bold uppercase tracking-wide leading-none',
      statusRoleClasses[status.role],
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

import { statusRoleClasses, type StatusPresentation } from '@/Constants/statuses';
import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  status: StatusPresentation;
  class?: HTMLAttributes['class'];
}>();
</script>
