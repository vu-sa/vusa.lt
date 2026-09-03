<template>
  <!-- scroll-mt-32 matches .rc-prose's heading offset — the ToC's scroll-to logic uses a
       160px JS offset (TableOfContents.vue) which already runs slightly ahead of the
       128px CSS one on tiptap headings; kept consistent with that existing behavior. -->
  <section :class="[
    'relative scroll-mt-32',
    PADDING_CLASS[padding],
    BACKGROUND_CLASS[background],
    DIVIDER_CLASS[divider],
    ROUNDED_CLASS[rounded],
    bleed && 'rc-viewport',
  ]">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="title" :title :subtitle :eyebrow :align :id="headingId" :level="headingLevel" :show-separator="showSeparator" :inverted="background === 'brand' || background === 'ink'" />
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
  BACKGROUND_CLASS, DIVIDER_CLASS, INNER_CLASS, PADDING_CLASS, ROUNDED_CLASS,
  type SectionBackground, type SectionDivider, type SectionHeadingLevel, type SectionInner,
  type SectionPadding, type SectionRounded,
} from './sectionClasses';

const props = withDefaults(defineProps<{
  title?: string;
  subtitle?: string;
  /** Brand kicker above the title — forwarded to SectionHeader. */
  eyebrow?: string;
  background?: SectionBackground;
  padding?: SectionPadding;
  /** Inner content max-width — independent of the canvas column the block itself sits in. */
  inner?: SectionInner;
  align?: 'center' | 'start';
  rounded?: SectionRounded;
  /** Hairline edges separating this band from its neighbours. */
  divider?: SectionDivider;
  /**
   * Break out to the full viewport width. A tinted band that stops at the content measure reads
   * as a panel; the design's bands run edge to edge. `.rc-viewport` escapes the rc-canvas, the
   * `.wrapper` grid and PublicLayout's `.container` in one go.
   */
  bleed?: boolean;
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
  divider: 'none',
  headingLevel: 2,
  showSeparator: true,
});

// A second anchor target alongside the `#rc-{part.id}` id this section's root element
// gets from attribute fallthrough (see tocAnchors.ts) — a human-readable, sluggified
// heading id for direct linking/anchoring, matching tiptap's own heading ids.
const headingId = computed(() => (props.title ? latinizeId(props.title) : undefined));
</script>
