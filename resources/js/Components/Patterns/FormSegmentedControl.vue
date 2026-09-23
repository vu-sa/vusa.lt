<template>
  <div
    :class="cn(
      'grid h-11 w-full border border-border bg-secondary/50 p-0.5 transition-colors pointer-coarse:h-12',
      'focus-within:bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
      props.class,
    )"
    :style="{ gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))` }"
    role="group"
    :aria-label
    data-slot="form-segmented-control"
  >
    <button
      v-for="option in options"
      :key="String(option.value)"
      type="button"
      :class="optionClass(option)"
      :aria-pressed="model === option.value"
      :data-testid="testIdPrefix ? `${testIdPrefix}-${option.testId ?? String(option.value)}` : undefined"
      @click="model = option.value"
    >
      <slot name="option" :option>
        <component :is="option.icon" v-if="option.icon" class="size-4 shrink-0" aria-hidden="true" />
      </slot>
      <span>{{ option.label }}</span>
    </button>
  </div>
</template>

<script setup lang="ts" generic="T extends string | number | boolean">
import type { Component, HTMLAttributes } from 'vue';

import { segmentVariants } from '@/Components/ui/control';
import { cn } from '@/Utils/Shadcn/utils';

export interface FormSegmentOption<V> {
  value: V;
  label: string;
  icon?: Component;
  /** Replaces the brand fill when chosen, e.g. a status's `statusRoleClasses` colours. */
  activeClass?: string;
  /** Suffix for the option's `data-testid`; defaults to the value. */
  testId?: string;
}

/** A 2–5 option choice on a form's canvas: one hairline frame, the chosen segment brand-filled. */
const props = defineProps<{
  options: FormSegmentOption<T>[];
  ariaLabel: string;
  testIdPrefix?: string;
  class?: HTMLAttributes['class'];
}>();

const model = defineModel<T>({ required: true });

function optionClass(option: FormSegmentOption<T>) {
  const active = model.value === option.value;

  if (active && option.activeClass) {
    return cn(segmentVariants({ active: true }), 'w-full border', option.activeClass);
  }

  return cn(segmentVariants({ active }), 'w-full');
}
</script>
