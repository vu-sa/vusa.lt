<template>
  <button
    type="button"
    data-slot="action-choice-button"
    :disabled
    :class="cn(
      'group relative flex w-full items-center gap-4 overflow-hidden rounded-2xl border p-4 text-left',
      'min-h-[4.5rem] transition-[border-color,background-color,transform] duration-200',
      'border-border/70 bg-card hover:border-primary/35 active:scale-[0.995]',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
      'disabled:pointer-events-none disabled:opacity-50',
      selected && 'border-primary/60 bg-primary/[0.04] dark:bg-primary/[0.08]',
      props.class,
    )"
  >
    <!-- The tint is the action's identity colour; it stays faint until hover so a
         screenful of choices reads as one surface rather than a box of stickers. -->
    <span
      v-if="icon"
      :class="cn(
        'flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-foreground/70',
        'transition-[opacity,color] duration-200 opacity-80 group-hover:opacity-100 group-hover:text-foreground',
        gradient ?? 'from-muted to-muted',
        selected && 'opacity-100 text-foreground',
      )"
    >
      <component :is="icon" class="size-[1.35rem]" :stroke-width="1.75" />
    </span>

    <span class="flex min-w-0 flex-1 flex-col gap-1">
      <span class="text-[0.975rem] font-semibold leading-snug tracking-[-0.01em] text-foreground">
        <slot name="title">{{ title }}</slot>
      </span>
      <span v-if="description || $slots.description" class="text-[0.8125rem] leading-snug text-muted-foreground">
        <slot name="description">{{ description }}</slot>
      </span>
      <span v-if="$slots.meta" class="mt-1 flex flex-wrap items-center gap-2">
        <slot name="meta" />
      </span>
    </span>

    <ChevronRight
      v-if="showChevron"
      class="size-4 shrink-0 text-muted-foreground/50 transition-[transform,color] duration-200 group-hover:translate-x-0.5 group-hover:text-primary"
    />
    <Check v-else-if="selected" class="size-5 shrink-0 text-primary" />
  </button>
</template>

<script setup lang="ts">
import { Check, ChevronRight, type LucideIcon } from 'lucide-vue-next';
import type { Component, HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  title?: string;
  description?: string;
  icon?: LucideIcon | Component;
  /** Tailwind `from-*`/`to-*` pair for the icon tile. */
  gradient?: string;
  /** Shows the chevron affordance; turn off for buttons that pick rather than navigate. */
  showChevron?: boolean;
  selected?: boolean;
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  showChevron: true,
});
</script>
