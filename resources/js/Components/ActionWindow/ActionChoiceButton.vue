<template>
  <button
    type="button"
    data-slot="action-choice-button"
    :disabled
    :aria-pressed="selected ? 'true' : undefined"
    :class="cn(
      'group relative flex w-full items-center gap-4 px-3 py-3 text-left',
      'min-h-16 transition-colors hover:bg-secondary',
      'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
      'disabled:pointer-events-none disabled:opacity-50',
      selected && 'bg-secondary',
      props.class,
    )"
  >
    <span
      v-if="icon"
      :class="cn('flex size-10 shrink-0 items-center justify-center border', toneClasses[tone])"
      aria-hidden="true"
    >
      <component :is="icon" class="size-5" :stroke-width="1.75" />
    </span>

    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
      <span class="text-base font-medium leading-snug text-foreground">
        <slot name="title">{{ title }}</slot>
      </span>
      <span v-if="description || $slots.description" class="text-sm leading-snug text-muted-foreground">
        <slot name="description">{{ description }}</slot>
      </span>
      <span v-if="$slots.meta" class="mt-1 flex flex-wrap items-center gap-2">
        <slot name="meta" />
      </span>
    </span>

    <ChevronRight
      v-if="showChevron"
      class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-foreground"
      aria-hidden="true"
    />
    <Check v-else-if="selected" class="size-5 shrink-0 text-foreground" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { Check, ChevronRight, type LucideIcon } from 'lucide-vue-next';
import type { Component, HTMLAttributes } from 'vue';

import type { StatusRole } from '@/Constants/statuses';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  title?: string;
  description?: string;
  icon?: LucideIcon | Component;
  /** Colours the icon tile with a status role; omit for the plain hairline tile. */
  tone?: StatusRole;
  /** Shows the chevron affordance; turn off for buttons that pick rather than navigate. */
  showChevron?: boolean;
  selected?: boolean;
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  tone: 'neutral',
  showChevron: true,
});

const toneClasses: Record<StatusRole, string> = {
  neutral: 'border-border bg-card text-foreground',
  info: 'border-status-info-border bg-status-info-surface text-status-info',
  progress: 'border-status-progress-border bg-status-progress-surface text-status-progress',
  attention: 'border-status-attention-border bg-status-attention-surface text-status-attention',
  success: 'border-status-success-border bg-status-success-surface text-status-success',
  danger: 'border-status-danger-border bg-status-danger-surface text-status-danger',
};
</script>
