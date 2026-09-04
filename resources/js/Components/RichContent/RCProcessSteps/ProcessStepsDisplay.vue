<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow"
    :band :align="element.options?.align ?? 'start'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <ol :class="['grid gap-8 sm:gap-10', COLUMN_CLASS[columns]]">
      <li v-for="(step, index) in steps" :key="index" class="border-t-2 border-brand pt-5">
        <!-- The numeral is decoration for the ordered list the markup already is, so it is
             `aria-hidden`: a screen reader announcing "zero one, one, Užpildyk anketą" reads
             the same number twice. -->
        <span class="u-display block text-3xl text-brand" aria-hidden="true">
          {{ String(index + 1).padStart(2, '0') }}
        </span>
        <h3 class="mt-3 text-lg font-bold text-foreground">
          {{ step.title }}
        </h3>
        <p v-if="step.text" class="mt-2 text-pretty leading-relaxed text-muted-foreground">
          {{ step.text }}
        </p>
      </li>
    </ol>
  </RCSection>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import RCSection from '../RCSection.vue';

import type { ProcessSteps } from '@/Types/contentParts';
import type { BandResolution } from '../bandLayout';

/**
 * A numbered process — "how you join", "how a request is handled".
 *
 * Each step hangs off a brand rule along its top edge rather than sitting in a card, and the
 * numeral is set in the display face: the sequence is the content, so it is what carries the
 * weight. Renders as a real `<ol>`, which is what it is.
 */
const props = defineProps<{
  element: ProcessSteps;
  anchorId?: number | null;
  band?: BandResolution;
}>();

const COLUMN_CLASS: Record<number, string> = {
  2: 'sm:grid-cols-2',
  3: 'sm:grid-cols-2 lg:grid-cols-3',
  4: 'sm:grid-cols-2 lg:grid-cols-4',
};

const steps = computed(() => props.element.json_content ?? []);

// Coerced once here: the value arrives from a Select, so it can be the string "3".
const columns = computed(() => {
  const n = Number(props.element.options?.columns);

  return n === 2 || n === 4 ? n : 3;
});
</script>
