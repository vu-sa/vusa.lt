<template>
  <div :class="rootClass">
    <div :class="['flex flex-wrap items-end justify-between gap-x-8 gap-y-3', showSeparator && 'border-b border-border pb-5']">
      <div :class="['min-w-0', align === 'start' ? 'flex-1' : 'mx-auto text-center']">
        <p v-if="eyebrow" class="u-eyebrow mb-2">
          {{ eyebrow }}
        </p>
        <component :is="`h${resolvedLevel}`" :id="id"
          :class="['u-display scroll-mt-32 text-balance text-foreground', SECTION_HEADING_SIZE_CLASS[resolvedLevel]]">
          {{ title }}
        </component>
        <p v-if="subtitle" :class="['mt-4 max-w-2xl leading-relaxed text-muted-foreground', align === 'start' ? '' : 'mx-auto']">
          {{ subtitle }}
        </p>
      </div>

      <!-- The archive/"see all" link a listing band carries opposite its title. -->
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { SECTION_HEADING_SIZE_CLASS, type SectionHeadingLevel } from '@/Components/RichContent/sectionClasses';

interface Props {
  title: string;
  subtitle?: string;
  /** Brand kicker above the title. */
  eyebrow?: string;
  align?: 'center' | 'start';
  /** Sluggified anchor id for direct linking — see RCSection.vue's `headingId`. */
  id?: string;
  /** Semantic heading level. Sizes follow the `.rc-prose` h2/h3/h4 scale (sectionClasses.ts). */
  level?: SectionHeadingLevel;
  /** Whether to close the header off with the hairline rule. */
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

// `u-eyebrow` / `u-display` are used as plain classes rather than by importing EyebrowLabel and
// DisplayHeading: this file sits in `ui/`, and importing from `Public/Base/` would run the tier
// dependency backwards. The classes carry the same values.
//
// The roomy bottom gap exists to give the content breathing room *after the rule*. With the rule
// hidden the same gap reads as dead space, so tighten it — the section's outer spacing still comes
// from its `padding` option ("Vidiniai tarpai").
const rootClass = computed(() => [
  props.showSeparator ? 'mb-10 md:mb-12' : 'mb-6 md:mb-8',
  props.align === 'start' ? 'text-left' : 'text-center',
]);
</script>
