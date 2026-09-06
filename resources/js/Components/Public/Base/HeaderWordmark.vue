<template>
  <span
    :class="cn('inline-flex items-center', props.class)"
    data-slot="header-wordmark"
    :data-variant="variant"
  >
    <img
      v-if="variant === 'official'"
      :src="resolvedSrc"
      :alt
      class="aspect-[2.804] h-14 w-auto max-w-full p-1 dark:invert"
      loading="eager"
      width="1200"
      height="428"
    >

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
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { cn } from '@/Utils/Shadcn/utils';
import { getAppLogoSrc } from '@/Utils/AppLogo';

/**
 * The public site mark.
 */
const props = withDefaults(defineProps<{
  variant?: 'official' | 'wordmark';
  /** Overrides the tenant and locale-specific official SVG. */
  src?: string;
  alt?: string;
  primaryLine?: string;
  secondaryLine?: string;
  class?: HTMLAttributes['class'];
}>(), {
  variant: 'official',
  src: undefined,
  alt: 'Vilniaus universiteto Studentų atstovybė',
  primaryLine: 'Vilniaus universiteto',
  secondaryLine: 'Studentų atstovybė',
  class: undefined,
});

const page = usePage();

const resolvedSrc = computed(() => props.src
  ?? getAppLogoSrc(page.props.tenant?.alias, page.props.app.locale));
</script>
