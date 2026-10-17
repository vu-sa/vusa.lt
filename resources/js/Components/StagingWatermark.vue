<template>
  <!-- A mask, not a coloured SVG, so the token colour follows the light and dark themes. -->
  <div
    v-if="isStaging"
    data-slot="staging-watermark"
    aria-hidden="true"
    class="pointer-events-none fixed inset-0 z-[100] select-none bg-status-attention opacity-[0.06] print:hidden dark:opacity-[0.075]"
    :style="{
      maskImage: tile,
      WebkitMaskImage: tile,
      maskRepeat: 'repeat',
      WebkitMaskRepeat: 'repeat',
    }"
  />
</template>

<script setup lang="ts">
import { useStaging } from '@/Composables/useStaging';

const { isStaging } = useStaging();

// Both names in every tile, whatever the locale: the public site doesn't load the admin translations.
const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="640" height="380">
  <g transform="rotate(-30 320 190)" text-anchor="middle" font-family="system-ui, sans-serif" font-weight="800">
    <text x="320" y="180" font-size="44" letter-spacing="4">BANDOMOJI APLINKA</text>
    <text x="320" y="230" font-size="30" letter-spacing="8">STAGING</text>
  </g>
</svg>`;

const tile = `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
</script>
