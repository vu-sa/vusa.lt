<template>
  <section
    :class="cn(
      'w-full',
      dividerClass,
      spacingClass,
      props.class,
    )"
    data-slot="section-band"
  >
    <!-- `bleed` drops the measure, not the section: a full-bleed band still lets its own
         children opt back into the container, which is how the hero puts edge-to-edge imagery
         behind centred copy. -->
    <div v-if="bleed">
      <slot />
    </div>
    <div v-else class="mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * One vertical band of a public page: the measure and the rhythm, in one place.
 *
 * Sections are separated by whitespace and a 1px rule rather than by boxes or shadows, so this
 * owns both. Pages compose bands; they do not restate `max-w-7xl px-5 sm:px-6 lg:px-8` each time.
 */
const props = withDefaults(defineProps<{
  /** Edge-to-edge: no max-width, no horizontal padding. For heroes and full-bleed imagery. */
  bleed?: boolean;
  /** The hairline that separates one band from the next. */
  divider?: 'top' | 'bottom' | 'both' | false;
  spacing?: 'none' | 'tight' | 'default' | 'loose';
  class?: HTMLAttributes['class'];
}>(), {
  divider: false,
  spacing: 'default',
  class: undefined,
});

const dividerClass = computed(() => ({
  top: 'border-t border-border',
  bottom: 'border-b border-border',
  both: 'border-y border-border',
}[props.divider as 'top' | 'bottom' | 'both'] ?? ''));

const spacingClass = computed(() => ({
  none: '',
  tight: 'py-8 lg:py-12',
  default: 'py-16 lg:py-24',
  loose: 'py-24 lg:py-32',
}[props.spacing]));
</script>
