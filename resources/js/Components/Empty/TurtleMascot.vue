<template>
  <svg
    :class="cn('h-14 w-auto', props.class)"
    viewBox="0 0 64 37"
    fill="currentColor"
    focusable="false"
    aria-hidden="true"
  >
    <ellipse class="shadow" cx="35" cy="35" rx="17" ry="1.5" />

    <!-- Legs before the body, so the part of each that runs up past the hip stays buried under
         the shell rather than showing as a seam. The far pair is tucked out of sight. -->
    <path :d="leg" transform="translate(23.5 28.5) rotate(-8) scale(0.85)" />
    <path :d="leg" transform="translate(47 28.5) rotate(8) scale(0.85)" />

    <g class="body">
      <path d="M47 25.4 C51.5 25.2 56.4 25.6 60.6 26.7 C55.6 28.4 51 28.9 47 28.4 Z" />

      <!-- Head half-withdrawn: the resting counterpart of the loader's walk. -->
      <g transform="translate(2.5 0) rotate(4 22 23.5)">
        <rect x="10" y="19" width="15" height="8" rx="4" />
        <ellipse cx="9" cy="21.5" rx="5.5" ry="4.6" />
        <circle class="eye" cx="6.6" cy="20.3" r="1.05" />
      </g>

      <path d="M19 23 C18.5 28 25 30.5 35 30.5 C44 30.5 48 28 47 23 Z" />
      <path d="M13 25 A22 16 0 0 1 57 25 C48.5 27.6 21.5 27.6 13 25 Z" />
      <path class="seam" fill="none" stroke-width="1.1" stroke-linecap="round"
        d="M15.5 23.2 C24 19.5 46 19.5 54.5 23.2
               M28 12.8 C27.3 16 27.1 19 27.3 21.2
               M42 12.8 C42.7 16 42.9 19 42.7 21.2" />
    </g>
  </svg>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * The VU SR turtle at rest — the empty-state counterpart of the walking loader in
 * resources/views/turtle-loader.blade.php. The two share a viewBox and silhouette, so a change
 * to one shape wants the same change in the other.
 */
const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

/**
 * One leg drawn around its own hip at (0,0), so each instance only says where that hip sits and
 * how it is turned. Bound rather than kept in `<defs>`: two empty states on one page would
 * collide on the id.
 */
const leg
  = 'M3.8 -6 C4.4 -1 4.3 3.2 3.6 5.2 C3.3 6.3 2.1 6.9 0.2 6.9 L-2.4 6.9 '
    + 'C-3.4 6.7 -3.9 6 -3.9 5 C-4 2.5 -4 -1 -3.6 -6 Z';
</script>

<style scoped>
/* The drawing has exactly one tone besides the body: a tint of the page ground. Solid at this
   size reads as a cut through the shell — or, for the eye, as a blank socket. */
.seam {
  stroke: var(--background);
  opacity: 0.45;
}

.eye {
  fill: var(--background);
  opacity: 0.55;
}

.shadow {
  opacity: 0.14;
}

/* One slow breath, well under the walk's tempo — it should register as alive, not as loading. */
.body {
  transform-box: view-box;
  animation: breathe 4.5s ease-in-out infinite;
}

@keyframes breathe {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-0.8px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .body {
    animation: none;
  }
}
</style>
