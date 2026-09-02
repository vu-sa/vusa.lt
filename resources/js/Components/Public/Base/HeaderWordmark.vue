<template>
  <span
    :class="cn('inline-flex items-center', props.class)"
    data-slot="header-wordmark"
    :data-variant="variant"
  >
    <!-- Official institutional lockup. `dark:invert` because the asset is one-colour dark. -->
    <img
      v-if="variant === 'official'"
      :src
      :alt
      class="h-auto w-32 max-w-full dark:invert md:w-36"
      loading="eager"
      width="120"
      height="40"
    >

    <!-- Typographic mark: a brand rule beside a two-line lockup, matching the poster style of
         the VU SA key visuals. Not `sr-only` text plus an image — it is real text, so it scales
         with the a11y font setting and stays legible at any width. -->
    <span v-else class="border-l-2 border-brand pl-3 leading-none">
      <span class="block text-[11px] font-bold uppercase tracking-[0.1em] text-foreground">
        {{ primaryLine }}
      </span>
      <span class="mt-1 block text-[10px] font-medium uppercase tracking-[0.22em] text-muted-foreground">
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
 * `official` (the default) is the institutional SVG the site ships today. `wordmark` is the
 * typographic lockup from the redesign, which sits better in the dark/brand system but is a
 * visible identity change — so it is a prop, not a rewrite, and the switch is one word once VU SA
 * brand signs off. Keep the official mark in the footer and share images either way.
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
  // The v0 design's typographic lockup. The official SVG stays available via `variant` and is
  // still what the footer, share images and print should use.
  variant: 'wordmark',
  src: '/logos/vusa.lin.hor.svg',
  alt: 'Vilniaus universiteto Studentų atstovybė',
  primaryLine: 'Vilniaus universiteto',
  secondaryLine: 'Studentų atstovybė',
  class: undefined,
});
</script>
