<template>
  <span
    :class="cn('inline-flex items-center', props.class)"
    data-slot="header-wordmark"
    :data-variant="variant"
  >
    <!-- Official institutional lockup. `dark:invert` because the asset is one-colour dark.
         `aspect-[1.886]` is the source SVG's real ratio (viewBox 850.394×450.858) — the old
         width/height attributes (120×40 = 3:1) lied about it, so the browser's implicit
         aspect-ratio squashed the mark; `aspect-*` here is authored CSS and wins over that.
         Padded (`p-1`), not flush, so it doesn't crowd the tenant tag and padaliniai selector
         beside it. -->
    <img
      v-if="variant === 'official'"
      :src
      :alt
      class="aspect-[1.886] h-14 w-auto max-w-full p-1 dark:invert"
      loading="eager"
      width="189"
      height="100"
    >

    <!-- Typographic mark: a brand rule beside a two-line lockup, matching the poster style of
         the VU SA key visuals. Not `sr-only` text plus an image — it is real text, so it scales
         with the a11y font setting and stays legible at any width. -->
    <span v-else class="border-l-2 border-brand pl-3 leading-none">
      <span class="block text-[0.6875rem] font-bold uppercase tracking-[0.1em] text-foreground">
        {{ primaryLine }}
      </span>
      <span class="mt-1 block text-[0.625rem] font-medium uppercase tracking-[0.22em] text-muted-foreground">
        {{ secondaryLine }}
      </span>
      <span class="sr-only">{{ alt }}</span>
    </span>
  </span>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * The site mark, in both forms the design allows.
 *
 * `official` (the default) is the institutional SVG, shown small and padded rather than as a
 * headline-sized wordmark. `wordmark` is the typographic lockup from the v0 prototype — kept
 * available via the prop, since it's still the reference layout if the official mark is ever
 * swapped out. Keep the official mark in the footer and share images either way.
 */
const props = withDefaults(defineProps<{
  variant?: 'official' | 'wordmark';
  /** Path to the official SVG; the caller picks the locale-appropriate file. */
  src?: string;
  alt?: string;
  primaryLine?: string;
  secondaryLine?: string;
  class?: HTMLAttributes['class'];
}>(), {
  variant: 'official',
  src: '/logos/vusa.lin.hor.svg',
  alt: 'Vilniaus universiteto Studentų atstovybė',
  primaryLine: 'Vilniaus universiteto',
  secondaryLine: 'Studentų atstovybė',
  class: undefined,
});
</script>
