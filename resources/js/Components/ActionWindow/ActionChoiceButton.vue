<template>
  <button
    type="button"
    data-slot="action-choice-button"
    :disabled
    :aria-pressed="selected ? 'true' : undefined"
    :class="cn(
      'group relative flex w-full items-center justify-between gap-3.5 border p-3.5 sm:p-4 text-left min-h-14 transition-all duration-150',
      'border-border/50 bg-secondary/35 hover:border-brand/60 hover:bg-secondary/60 dark:bg-secondary/20 dark:hover:bg-secondary/40',
      'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
      'disabled:pointer-events-none disabled:opacity-50',
      selected && 'border-brand bg-brand/10 dark:bg-brand/20 hover:bg-brand/15 dark:hover:bg-brand/25',
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
      <span class="text-base font-bold leading-snug text-foreground">
        <slot name="title">{{ title }}</slot>
      </span>
      <span v-if="description || $slots.description" class="text-xs leading-snug text-muted-foreground mt-0.5">
        <slot name="description">{{ description }}</slot>
      </span>
      <span v-if="$slots.meta" class="mt-1 flex flex-wrap items-center gap-2">
        <slot name="meta" />
      </span>
    </span>

    <span
      v-if="!showChevron || selected !== undefined"
      class="flex size-6 shrink-0 items-center justify-center border transition-colors"
      :class="selected
        ? 'border-brand bg-brand-fill text-brand-foreground'
        : 'border-border bg-background text-transparent group-hover:border-brand/80'"
      aria-hidden="true"
    >
      <Check class="size-3.5" />
    </span>
    <ChevronRight
      v-else-if="showChevron"
      class="size-4 shrink-0 text-muted-foreground/70 transition-colors group-hover:text-foreground"
      aria-hidden="true"
    />
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
  neutral: 'border-border bg-background text-foreground group-hover:border-foreground/30',
  info: 'border-status-info-border bg-status-info-surface text-status-info',
  progress: 'border-status-progress-border bg-status-progress-surface text-status-progress',
  attention: 'border-status-attention-border bg-status-attention-surface text-status-attention',
  success: 'border-status-success-border bg-status-success-surface text-status-success',
  danger: 'border-status-danger-border bg-status-danger-surface text-status-danger',
};
</script>
