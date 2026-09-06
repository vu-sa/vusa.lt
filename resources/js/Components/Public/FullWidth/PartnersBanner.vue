<template>
  <SectionBand divider="top" spacing="tight">
    <EyebrowLabel :text="$t('accessibility.partner_organizations')" class="text-center text-muted-foreground" />

    <!-- Ruled grid of marks. Flexbox, not CSS Grid: a `flex-wrap` line centers its own items via
         `justify-center` independently of other lines, so a half-empty last row centers itself
         instead of hugging the left edge with dead space on the right — Grid's unified track
         structure can't do that. `shrink-0 grow-0` + explicit `basis-*` holds each cell's width
         to its "column" regardless of how many items share its line. Static, not a carousel — a
         tenant with more than 5 active banners just gets another row, nothing autoplays.

         Every cell owns its own `border-l`/`border-b` rather than the grid owning a shared
         `border-l` — a container-level left rule is a single line fixed to the container's edge,
         so it strands itself (floating with a gap next to it) whenever a centered row's cells
         don't start there. A per-cell left border is always exactly where that cell is, centered
         row or not, so it never strands.

         The right edge needs a rule on the last cell of *every* row (so a full row closes on the
         right too), not just the very last cell overall — otherwise only the last of the whole
         list gets one, and every full row above a wrapped last row is left open. There's no CSS
         way to select "last in a wrapped flex line" directly, but the column count per breakpoint
         is fixed (2/3/5, matching `basis-*` above), so "last in row" is exactly "every Nth cell,
         or the actual last cell" for that breakpoint's N — expressible with `nth-child`.

         Each breakpoint's rule is scoped to an EXCLUSIVE width range (`max-sm:`, `sm:max-lg:`,
         `lg:`) rather than the usual min-width-only cascade, so only one tier's rule is ever live
         at a time — no "cancel the previous tier" rule needed. That matters here because Tailwind
         emits all `border-r` instances (every breakpoint) as one group, then all `border-r-0`
         instances as a separate, later group — so with plain min-width tiers, a smaller
         breakpoint's cancel rule ends up *after* a larger breakpoint's positive rule in the
         compiled CSS and silently wins the tie regardless of screen width (confirmed via computed
         styles: item 5 of 10 at the lg/5-col width kept losing its border-r to the sm/3-col tier's
         cancel rule this way). An exclusive range sidesteps the ordering fight entirely. -->
    <div class="mt-6 flex flex-wrap justify-center border-t border-border">
      <SmartLink
        v-for="banner in banners"
        :key="banner.id"
        :href="banner.link_url || null"
        :aria-label="`${$t('accessibility.visit')} ${banner.title}`"
        :class="[
          'group flex shrink-0 grow-0 items-center justify-center text-center',
          'basis-1/2 sm:basis-1/3 lg:basis-1/5',
          'border-b border-l border-border px-4 py-8',
          'max-sm:[&:is(:nth-child(2n),:last-child)]:border-r',
          'sm:max-lg:[&:is(:nth-child(3n),:last-child)]:border-r',
          'lg:[&:is(:nth-child(5n),:last-child)]:border-r',
          'transition-colors hover:bg-secondary',
        ]"
      >
        <img
          v-if="banner.image_url"
          :src="banner.image_url"
          :alt="banner.title"
          loading="lazy"
          :class="[
            'max-h-10 w-auto object-contain transition-[filter] duration-300',
            'grayscale group-hover:grayscale-0',
            // Dark logos need inverting to read against the near-black canvas — but only at
            // rest. Reversing the invert on hover would recolour the mark in dark mode too,
            // which is the light-mode-only affordance; `dark:group-hover:grayscale` re-asserts
            // grayscale on hover to cancel the plain (light-mode) `group-hover:grayscale-0`.
            'dark:invert dark:group-hover:grayscale',
          ]"
        >
        <span
          v-else
          class="text-base font-bold uppercase leading-tight tracking-wide text-foreground transition-colors group-hover:text-brand"
        >
          {{ banner.title }}
        </span>
      </SmartLink>
    </div>
  </SectionBand>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { EyebrowLabel, SectionBand } from '@/Components/Public/Base';

defineProps<{
  banners: App.Entities.Banner[];
}>();
</script>
