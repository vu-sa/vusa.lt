<template>
  <!-- `flex-col`, not `flex`: the Lithuanian flag's bands are horizontal. A row of three
       full-width children renders them as vertical bars, which is a different flag.
       5:3 is the flag's official proportion (width:height). `w-5 h-3` sets both dimensions
       explicitly — `aspect-5/3` alone, as a flex item, was resolving to a square instead of
       computing width from height, so it stays only as a documented invariant, not the
       mechanism. -->
  <span v-if="locale === 'lt'" class="flex aspect-5/3 h-3 w-5 shrink-0 flex-col overflow-hidden" aria-hidden="true">
    <!-- Official Lithuanian web colours: yellow #FDB913, green #006A44, red #C1272D. -->
    <span class="h-full w-full bg-[#FDB913]" />
    <span class="h-full w-full bg-[#006A44]" />
    <span class="h-full w-full bg-[#C1272D]" />
  </span>

  <!-- The Union Jack cannot be expressed as three bands — blue/white/red stacked is the French
       flag, which is what the band version rendered. Drawn instead, simplified to the crosses.
       Same 5:3 proportion as the LT flag (viewBox is 60×36 = 5:3).
       `!h-3 !w-5`, not plain `h-3 w-5`: this flag sits inside `ui/button`, whose base class ships
       `[&_svg:not([class*='size-'])]:size-4` — any descendant `<svg>` without a `size-*` class
       gets force-squared to 16×16. That selector only matches the `<svg>` tag, which is why the
       `<span>`-based LT flag above was unaffected while this one stayed square. `!` beats it
       outright regardless of specificity. -->
  <svg
    v-else
    class="!h-3 !w-5 shrink-0"
    viewBox="0 0 60 36"
    preserveAspectRatio="none"
    aria-hidden="true"
  >
    <rect width="60" height="36" fill="#012169" />
    <path d="M0,0 L60,36 M60,0 L0,36" stroke="#fff" stroke-width="7.2" />
    <path d="M0,0 L60,36 M60,0 L0,36" stroke="#c8102e" stroke-width="4" />
    <path d="M30,0 V36 M0,18 H60" stroke="#fff" stroke-width="12" />
    <path d="M30,0 V36 M0,18 H60" stroke="#c8102e" stroke-width="7.2" />
  </svg>
</template>

<script setup lang="ts">
/**
 * The language chip's flag.
 *
 * Replaces the `circle-flags` CDN images the switcher used: a third-party network request on
 * every page load, and circles in a header the design requires to be square. Purely decorative —
 * the adjacent "LT"/"EN" label is what gets announced, hence `aria-hidden`.
 */
defineProps<{
  locale: string;
}>();
</script>
