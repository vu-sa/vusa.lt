<template>
  <!-- scroll-mt-32 matches .rc-prose's heading offset — the ToC's scroll-to logic uses a
       160px JS offset (TableOfContents.vue) which already runs slightly ahead of the
       128px CSS one on tiptap headings; kept consistent with that existing behavior. -->
  <section :class="['relative scroll-mt-32', PADDING_CLASS[padding], BACKGROUND_CLASS[background], ROUNDED_CLASS[rounded]]">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="title" :title :subtitle :align :id="headingId" :level="headingLevel" :show-separator="showSeparator" />
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
/**
 * Shared section chrome for content types that render their own full-bleed block
 * (hero, accordion, card-stack, carousel, photo-gallery, number-stats, the list types).
 * Before this existed, each of those displays copy-pasted its own
 * `py-16 [bg] … container mx-auto max-w-*` markup and none of them rendered a header,
 * which is why pages like MembershipPage had to supply `SectionHeader` separately
 * around the rich-content block instead of the block owning its own title/subtitle.
 */
import { computed } from 'vue';

import SectionHeader from '@/Components/ui/SectionHeader.vue';
import { latinizeId } from '@/Utils/String';
import {
  BACKGROUND_CLASS, INNER_CLASS, PADDING_CLASS, ROUNDED_CLASS,
  type SectionBackground, type SectionHeadingLevel, type SectionInner, type SectionPadding, type SectionRounded,
} from './sectionClasses';

const props = withDefaults(defineProps<{
  title?: string;
  subtitle?: string;
  background?: SectionBackground;
  padding?: SectionPadding;
  /** Inner content max-width — independent of the canvas column the block itself sits in. */
  inner?: SectionInner;
  align?: 'center' | 'start';
  rounded?: SectionRounded;
  /** Semantic heading level for the title — forwarded to SectionHeader. */
  headingLevel?: SectionHeadingLevel;
  /** Whether to render the separator bar beneath the title. */
  showSeparator?: boolean;
}>(), {
  background: 'none',
  padding: 'lg',
  inner: 'wide',
  align: 'center',
  rounded: 'none',
  headingLevel: 2,
  showSeparator: true,
});

// A second anchor target alongside the `#rc-{part.id}` id this section's root element
// gets from attribute fallthrough (see tocAnchors.ts) — a human-readable, sluggified
// heading id for direct linking/anchoring, matching tiptap's own heading ids.
const headingId = computed(() => (props.title ? latinizeId(props.title) : undefined));
</script>
