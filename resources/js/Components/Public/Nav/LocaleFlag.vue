<template>
  <!-- `flex-col`, not `flex`: the Lithuanian flag's bands are horizontal. A row of three
       full-width children renders them as vertical bars, which is a different flag. -->
  <span v-if="locale === 'lt'" class="flex h-3 w-5 shrink-0 flex-col overflow-hidden" aria-hidden="true">
    <span class="h-full w-full bg-[oklch(0.78_0.15_80)]" />
    <span class="h-full w-full bg-[oklch(0.55_0.13_150)]" />
    <span class="h-full w-full bg-[oklch(0.55_0.16_25)]" />
  </span>

  <!-- The Union Jack cannot be expressed as three bands — blue/white/red stacked is the French
       flag, which is what the band version rendered. Drawn instead, simplified to the crosses. -->
  <svg
    v-else
    class="h-3 w-5 shrink-0"
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
