<template>
  <section
    :aria-labelledby="headingId"
    :class="cn('border border-border bg-background', props.class)"
    data-slot="form-panel"
  >
    <header class="flex items-center gap-2 border-b border-border px-4 py-3">
      <component :is="icon" v-if="icon" class="size-4 shrink-0 text-brand" aria-hidden="true" />
      <h2 :id="headingId" :class="cn('text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground', titleClass)">
        {{ title }}
      </h2>
      <div v-if="$slots.action" class="ml-auto">
        <slot name="action" />
      </div>
    </header>

    <div :class="flush ? 'flex flex-col' : 'flex flex-col gap-5 p-4'">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
import { useId, type Component, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/** A titled group of controls in a form's side column; not a SectionCard, which is for records. */
const props = withDefaults(defineProps<{
  title: string;
  icon?: Component;
  /** No body padding: for rows that draw their own hairlines (toggle rows, a `dl`). */
  flush?: boolean;
  class?: HTMLAttributes['class'];
  titleClass?: string;
}>(), {
  icon: undefined,
  class: undefined,
  titleClass: undefined,
});

const headingId = `form-panel-${useId()}`;
</script>
