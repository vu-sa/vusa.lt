<template>
  <div :class="rootClass">
    <component :is="`h${resolvedLevel}`" :id="id"
      :class="['font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mb-4 scroll-mt-32', SECTION_HEADING_SIZE_CLASS[resolvedLevel]]">
      {{ title }}
    </component>
    <p v-if="subtitle" :class="['text-base sm:text-lg text-zinc-600 dark:text-zinc-400 mb-6 max-w-3xl', align === 'start' ? '' : 'mx-auto']">
      {{ subtitle }}
    </p>
    <div v-if="showSeparator" :class="['w-16 h-1 bg-zinc-400 dark:bg-zinc-500 rounded-full', align === 'start' ? '' : 'mx-auto']" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { SECTION_HEADING_SIZE_CLASS, type SectionHeadingLevel } from '@/Components/RichContent/sectionClasses';

interface Props {
  title: string;
  subtitle?: string;
  align?: 'center' | 'start';
  /** Sluggified anchor id for direct linking — see RCSection.vue's `headingId`. */
  id?: string;
  /** Semantic heading level. Sizes follow the `.rc-prose` h2/h3/h4 scale (sectionClasses.ts). */
  level?: SectionHeadingLevel;
  /** Whether to render the separator bar beneath the title. */
  showSeparator?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  align: 'center',
  level: 2,
  showSeparator: true,
});

// Coerce to a valid 2|3|4 once, at the single point every consumer renders through —
// tolerates a stray string ("3" from a Select) or undefined without per-call site defence.
const resolvedLevel = computed<SectionHeadingLevel>(() => {
  const n = Number(props.level);
  return n === 3 || n === 4 ? n : 2;
});

// The roomy bottom gap exists to give the content breathing room *after the separator
// bar*. With the bar hidden the same gap reads as dead space, so tighten it — the
// section's outer spacing still comes from its `padding` option ("Vidiniai tarpai").
const rootClass = computed(() => [
  props.showSeparator ? 'mb-12 md:mb-16' : 'mb-6 md:mb-8',
  props.align === 'start' ? 'text-left' : 'text-center',
]);
</script>
