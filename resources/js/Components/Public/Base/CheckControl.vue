<template>
  <button
    type="button"
    role="checkbox"
    :aria-checked="modelValue"
    :class="cn(
      'flex w-full items-center justify-between gap-4 px-4 py-3.5 text-left transition-colors',
      'hover:bg-secondary/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
      props.class,
    )"
    data-slot="check-control"
    @click="modelValue = !modelValue"
  >
    <span class="text-sm text-foreground">
      <slot>{{ label }}</slot>
    </span>

    <span
      :class="cn(
        'flex size-7 shrink-0 items-center justify-center border-2 transition-colors',
        modelValue ? 'border-brand-fill bg-brand-fill text-brand-foreground' : 'border-border',
      )"
      aria-hidden="true"
    >
      <IFluentCheckmark24Filled v-if="modelValue" class="size-5" />
    </span>
  </button>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import IFluentCheckmark24Filled from '~icons/fluent/checkmark-24-filled';
import { cn } from '@/Utils/Shadcn/utils';

/**
 * A labelled on/off control for the public surface: a full-width row with a big square
 * checkmark box.
 *
 * **Use this instead of `ui/switch` anywhere on the public site.** A switch is a pill — it is
 * built from `rounded-full`, which is a literal and therefore survives the public surface's
 * zeroed radius scale, so it renders as the one soft shape on an otherwise cornery page. The
 * box is also a larger, more obvious hit target, and reads as on/off without relying on the
 * position of a thumb.
 *
 * The whole row is the control (not just the box) so the label is clickable, which is why this
 * is a `button` with `role="checkbox"` rather than a `label` + `input` pair.
 */
const props = withDefaults(defineProps<{
  label?: string;
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  class: undefined,
});

const modelValue = defineModel<boolean>({ default: false });
</script>
