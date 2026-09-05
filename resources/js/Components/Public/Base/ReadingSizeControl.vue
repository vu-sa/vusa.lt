<template>
  <div data-slot="reading-size-control">
    <!-- The control sits outside `.reading-scale`, not inside it: that element redefines the
         `--text-*` scale for everything it contains, and a stepper that grows as you press it is
         a control fighting the reader. -->
    <div class="mb-8 flex items-center justify-between gap-4 border-y border-border py-3">
      <p id="reading-size-label" class="u-eyebrow flex items-center gap-2">
        <IFluentTextFontSize24Regular class="size-3.5" aria-hidden="true" />
        {{ $t('accessibility.reading_size') }}
      </p>

      <!-- The same stepper shape as the header's text-size control: an ordered scale is a
           bigger/smaller operation, and the filled segments say where you are on it without
           four labels to read. -->
      <div class="flex items-stretch border border-border" role="group" aria-labelledby="reading-size-label">
        <button
          type="button"
          class="flex size-8 items-center justify-center border-r border-border text-foreground transition-colors hover:bg-secondary disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
          :disabled="stepIndex === 0"
          :aria-label="$t('accessibility.reading_size_decrease')"
          @click="step(-1)"
        >
          <IFluentSubtract24Regular class="size-4" />
        </button>

        <span class="flex items-center gap-1 px-3" aria-hidden="true">
          <span
            v-for="(_, index) in READING_SCALES"
            :key="index"
            :class="['h-1.5 w-4', index <= stepIndex ? 'bg-brand-fill' : 'bg-border']"
          />
        </span>

        <button
          type="button"
          class="flex size-8 items-center justify-center border-l border-border text-foreground transition-colors hover:bg-secondary disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
          :disabled="stepIndex === READING_SCALES.length - 1"
          :aria-label="$t('accessibility.reading_size_increase')"
          @click="step(1)"
        >
          <IFluentAdd24Regular class="size-4" />
        </button>
      </div>
    </div>

    <div class="reading-scale" :style="{ '--reading-scale': String(READING_SCALES[stepIndex]) }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentAdd24Regular from '~icons/fluent/add-24-regular';
import IFluentSubtract24Regular from '~icons/fluent/subtract-24-regular';
import IFluentTextFontSize24Regular from '~icons/fluent/text-font-size-24-regular';

/**
 * Scales the body copy it wraps, and nothing else on the page.
 *
 * Distinct from the header's accessibility menu on purpose: that one changes the root font size
 * for the whole site and is a standing preference, this is a per-article "this paragraph is a bit
 * small" reach, made where the reading actually happens. The CSS it drives is `.reading-scale` in
 * `app.css`, which only claims `p` and `li` — headings, chips and chrome inside the article keep
 * their own scale so the hierarchy doesn't invert at the largest step.
 */
const STORAGE_KEY = 'vusa-reading-scale';
const READING_SCALES = [1, 1.15, 1.3, 1.5] as const;

const stepIndex = ref(0);

function step(direction: 1 | -1): void {
  const next = stepIndex.value + direction;

  if (next < 0 || next >= READING_SCALES.length) return;

  stepIndex.value = next;
  persist(next);
}

function persist(value: number): void {
  try {
    localStorage.setItem(STORAGE_KEY, String(value));
  }
  catch {
    // Private mode or blocked storage — the size still applies for this page view.
  }
}

// Read on mount rather than at setup so SSR and the client agree on the initial markup; a stored
// preference then applies on hydration.
onMounted(() => {
  try {
    const stored = Number(localStorage.getItem(STORAGE_KEY));

    if (Number.isInteger(stored) && stored >= 0 && stored < READING_SCALES.length) {
      stepIndex.value = stored;
    }
  }
  catch {
    // Unreadable storage is not worth failing over — start at the default step.
  }
});
</script>
