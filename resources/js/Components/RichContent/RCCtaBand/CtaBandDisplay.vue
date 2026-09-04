<template>
  <!-- Not an RCSection: this band owns its whole ground, and the section header's eyebrow +
       hairline vocabulary belongs to the quiet bands, not to the one loud call to action. -->
  <!-- Own hardcoded ground + bespoke asymmetric padding (not band.classes' uniform
       BAND_PADDING — this row's copy/button layout carries its own responsive py-14/
       py-20 below) — see the `band` prop docs for why. `rc-band` still marks the root
       for the cross-band seam-dedup rule; `resolveBand` always resolves this type's
       `tint` to 'emphasis' and its width is locked to `full`, so `band.bleeds` is
       always true once resolved (the `!== false` guard only matters standalone, where
       no `band` prop is supplied at all). -->
  <section
    :class="[
      'relative scroll-mt-32 border-y border-brand-fill bg-brand-fill text-brand-foreground',
      band?.bleeds !== false && 'rc-band rc-viewport',
    ]"
    :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-5 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-20">
      <div class="max-w-xl">
        <h2 v-if="content.heading" class="u-display text-3xl sm:text-4xl">
          {{ content.heading }}
        </h2>
        <p v-if="content.text" class="mt-4 text-pretty text-lg leading-relaxed text-brand-foreground/85">
          {{ content.text }}
        </p>

        <dl v-if="items.length > 0" class="mt-6 flex flex-col gap-3 text-sm font-medium sm:flex-row sm:gap-8">
          <div v-for="(item, index) in items" :key="index" class="flex items-center gap-2">
            <RCIcon v-if="item.icon" :name="item.icon" class="size-4 shrink-0" />
            <dd>{{ item.label }}</dd>
          </div>
        </dl>
      </div>

      <!-- Inverted rather than outlined: on a solid brand ground an outline button is a hairline
           of the same colour family and disappears. -->
      <SmartLink
        v-if="content.button?.href && content.button?.label"
        :href="content.button.href"
        class="inline-flex shrink-0 items-center justify-center gap-2 bg-background px-7 py-4 text-sm font-bold uppercase tracking-wide text-foreground transition-colors hover:bg-foreground hover:text-background"
      >
        {{ content.button.label }}
        <IFluentArrowRight16Regular class="size-4" />
      </SmartLink>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import RCIcon from '../RCIcon.vue';

import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';
import SmartLink from '@/Components/Public/SmartLink.vue';
import type { CtaBand } from '@/Types/contentParts';
import type { BandResolution } from '../bandLayout';

/**
 * The brand-filled band a page closes on: a headline, a line of copy, a few contact facts, and
 * one button. Deliberately the loudest thing on the page — a page should carry at most one.
 */
const props = defineProps<{
  element: CtaBand;
  anchorId?: number | null;
  /** Resolved by `resolveBand` (bandLayout.ts) — always `tint: 'emphasis'` for this
   *  type, and `bleeds` follows its locked `full` width. Consumed here only for the
   *  bleed signal + alternation slot bookkeeping; the ground stays hardcoded (below). */
  band?: BandResolution;
}>();

const content = computed(() => props.element.json_content ?? {});

const items = computed(() => content.value.items ?? []);
</script>
