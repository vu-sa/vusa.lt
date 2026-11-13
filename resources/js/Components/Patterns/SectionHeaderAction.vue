<template>
  <!-- Text-sized so a section header keeps the height of its title; touch gets a larger hit area, not a taller header. -->
  <component
    :is="href ? (external ? 'a' : Link) : 'button'"
    :href="safeHref(href)"
    :type="href ? undefined : 'button'"
    :target="href && external ? '_blank' : undefined"
    :rel="href && external ? 'noopener noreferrer' : undefined"
    :disabled="!href && disabled ? true : undefined"
    :class="cn(
      'relative inline-flex shrink-0 items-center gap-1 text-sm leading-5 font-semibold transition-colors',
      tone === 'brand' ? 'text-brand hover:text-foreground' : 'text-muted-foreground hover:text-foreground',
      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
      'disabled:pointer-events-none disabled:opacity-50',
      'pointer-coarse:min-h-11 pointer-coarse:min-w-11',
      props.class,
    )"
    data-slot="section-header-action"
    @click="emit('click', $event)"
  >
    <component :is="icon" v-if="icon" class="size-4 shrink-0" aria-hidden="true" />
    <slot>{{ label }}</slot>
    <ChevronRight v-if="trailingChevron" class="size-4 shrink-0" aria-hidden="true" />
  </component>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { Component, HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';
import { safeHref } from '@/Utils/safeHref';

/** The one action a section header carries: "+ Nauja pareigybė", "Visi posėdžiai ›". */
const props = withDefaults(defineProps<{
  label?: string;
  icon?: Component;
  href?: string;
  external?: boolean;
  /** A way onward ("Visi posėdžiai") rather than a thing to do. */
  trailingChevron?: boolean;
  /** `muted` for a secondary header action beside a brand one, such as "Keisti tvarką". */
  tone?: 'brand' | 'muted';
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  icon: undefined,
  href: undefined,
  tone: 'brand',
  class: undefined,
});

const emit = defineEmits<{
  click: [event: MouseEvent];
}>();
</script>
