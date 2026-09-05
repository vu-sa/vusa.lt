<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'center'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="wide"
    :editable @update:header="updateOptions"
  >
    <div class="flex flex-wrap mx-auto font-bold text-xl leading-tight justify-center gap-6 md:gap-8">
      <NumberStatistic v-for="numberStat in element.json_content" :key="numberStat.label"
        :end-number="numberStat.endNumber" :show-plus="numberStat.showPlus">
        {{ numberStat.label }}
      </NumberStatistic>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import RCSection from '../RCSection.vue';
import type { BandResolution } from '../bandLayout';

import NumberStatistic from './RCNumberStatistic.vue';

import type { NumberStatSection } from '@/Types/contentParts';

const props = defineProps<{
  element: NumberStatSection;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header becomes
   *  click-to-edit. Undefined/false in every other context. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: NumberStatSection) => void>();

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}
</script>
