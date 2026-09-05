<template>
  <aside
    v-if="highlights && highlights.length > 0"
    class="not-prose my-8 w-full max-w-full border-l-2 border-brand bg-secondary/50 p-5 md:p-6"
  >
    <p class="u-eyebrow mb-4 flex items-center gap-2">
      <IFluentLightbulb24Regular class="size-4" aria-hidden="true" />
      {{ $t('Trumpai') }}
    </p>
    <ul class="space-y-3">
      <li
        v-for="(highlight, index) in highlights"
        :key="index"
        class="flex items-start gap-3"
      >
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center bg-brand-fill text-[0.6875rem] font-bold text-brand-foreground">
          {{ index + 1 }}
        </span>
        <span class="text-sm leading-relaxed text-foreground">{{ highlight }}</span>
      </li>
    </ul>
  </aside>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import IFluentLightbulb24Regular from '~icons/fluent/lightbulb-24-regular';

/**
 * The article's "in short" summary. Hung off the brand rule like every other aside on this
 * surface, rather than the rounded red-tinted card it used to be — that card hardcoded `#bd2835`,
 * so it stayed light-mode red on a near-black page.
 */
defineProps<{
  highlights?: string[] | null;
}>();
</script>
