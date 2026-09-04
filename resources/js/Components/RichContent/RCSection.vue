<template>
  <!-- scroll-mt-32 matches .rc-prose's heading offset — the ToC's scroll-to logic uses a
       160px JS offset (TableOfContents.vue) which already runs slightly ahead of the
       128px CSS one on tiptap headings; kept consistent with that existing behavior.
       `band.classes` already carries `relative scroll-mt-32` for an actual band. A
       `plain` block has no ground, but may carry its author-selected vertical padding. -->
  <component :is="band?.isBand === false ? 'div' : 'section'" :class="band?.classes ?? []">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="title" :title :subtitle :eyebrow :align :id="headingId" :level="headingLevel" :show-separator="showSeparator" :inverted="band?.tint === 'emphasis'" />
      <slot />
    </div>
  </component>
</template>

<script setup lang="ts">
/**
 * Shared section chrome for content types that render their own full-bleed block
 * (hero, accordion, card-stack, carousel, photo-gallery, number-stats, the list types).
 * Before this existed, each of those displays copy-pasted its own
 * `py-16 [bg] … container mx-auto max-w-*` markup and none of them rendered a header,
 * which is why pages like MembershipPage had to supply `SectionHeader` separately
 * around the rich-content block instead of the block owning its own title/subtitle.
 *
 * Chrome (ground, padding, border) is entirely driven by the `band` prop — resolved by
 * the caller via `bandLayout.ts`'s `resolveBand`/`resolveBands`, which alternates tints
 * automatically from document position. This component no longer picks its own colours.
 */
import SectionHeader from '@/Components/ui/SectionHeader.vue';
import { latinizeId } from '@/Utils/String';
import type { BandResolution } from './bandLayout';
import {
  INNER_CLASS, type SectionHeadingLevel, type SectionInner,
} from './sectionClasses';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  title?: string;
  subtitle?: string;
  /** Brand kicker above the title — forwarded to SectionHeader. */
  eyebrow?: string;
  /** This block's resolved chrome (see bandLayout.ts). Undefined renders as a plain,
   *  chrome-free flow block — the same as an explicit `{ isBand: false }`. */
  band?: BandResolution;
  /** Inner content max-width — independent of the canvas column the block itself sits in. */
  inner?: SectionInner;
  align?: 'center' | 'start';
  /** Semantic heading level for the title — forwarded to SectionHeader. */
  headingLevel?: SectionHeadingLevel;
  /** Whether to render the separator bar beneath the title. */
  showSeparator?: boolean;
}>(), {
  inner: 'wide',
  align: 'center',
  headingLevel: 2,
  showSeparator: true,
});

// A second anchor target alongside the `#rc-{part.id}` id this section's root element
// gets from attribute fallthrough (see tocAnchors.ts) — a human-readable, sluggified
// heading id for direct linking/anchoring, matching tiptap's own heading ids.
const headingId = computed(() => (props.title ? latinizeId(props.title) : undefined));
</script>
