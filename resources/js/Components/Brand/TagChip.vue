<template>
  <component
    :is="href ? 'a' : 'span'"
    :href
    :class="cn(
      tagChipVariants({ variant }),
      href && 'transition-colors',
      props.class,
    )"
    data-slot="tag-chip"
  >
    <slot>{{ label }}</slot>
    <button
      v-if="removable"
      type="button"
      class="ml-1.5 -mr-1 inline-flex size-3.5 items-center justify-center opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none"
      :aria-label="removeAriaLabel || $t('Išvalyti')"
      @click.stop="emit('remove')"
    >
      <IFluentDismiss16Regular class="size-3" />
    </button>
  </component>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { tagChipVariants, type TagChipVariants } from './tagChip';

import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import { cn } from '@/Utils/Shadcn/utils';

/**
 * The tag/topic marker on a news card, event tile or article header. Square by design — the
 * public surface zeroes the radius scale, but a chip is exactly the shape people reach for
 * `rounded-full` on, so it is spelled out here once instead of per caller.
 */
const props = withDefaults(defineProps<{
  label?: string;
  /** Renders an `<a>` when given. A resolved URL — this tier never calls `route()`. */
  href?: string;
  /** `solid` is the one-accent-per-view marker; `outline` and `muted` are for lists of many. */
  variant?: TagChipVariants['variant'];
  /** Shows a dismiss button at the trailing edge. Emits `remove` when clicked. */
  removable?: boolean;
  /** Accessible label for the dismiss button. Defaults to 'Išvalyti'. */
  removeAriaLabel?: string;
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  href: undefined,
  variant: 'solid',
  removeAriaLabel: undefined,
  class: undefined,
});

const emit = defineEmits<(e: 'remove') => void>();
</script>
