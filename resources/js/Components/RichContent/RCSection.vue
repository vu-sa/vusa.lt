<template>
  <!-- scroll-mt-32 matches .rc-prose's heading offset — the ToC's scroll-to logic uses a
       160px JS offset (TableOfContents.vue) which already runs slightly ahead of the
       128px CSS one on tiptap headings; kept consistent with that existing behavior.
       `band.classes` already carries `relative scroll-mt-32` for an actual band. A
       `plain` block has no ground, but may carry its author-selected vertical padding. -->
  <component :is="band?.isBand === false ? 'div' : 'section'" :class="band?.classes ?? []">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="!editable && title" :id="headingId" :title :subtitle :eyebrow :align :level="headingLevel" :show-separator :inverted="band?.tint === 'emphasis'" />

      <!-- Editable: same fields SectionHeader.vue renders, but click-to-edit. Unlike the
           standalone `section` block (RCSection/SectionDisplay.vue doesn't use this
           component at all), this header is *secondary* chrome for every type that reaches
           it — card-stack, accordion, the list types, … — so it only appears once an
           author has already put something here via the regular form; it never invites
           adding a title to a block that was never meant to have one. Clearing every field
           back to empty hides it again — re-adding one goes through the form. -->
      <div v-else-if="editable && hasHeaderField" :class="headerRootClass">
        <div :class="['flex flex-wrap items-end justify-between gap-x-8 gap-y-3', showSeparator && 'border-b border-border pb-5']">
          <div :class="['min-w-0', align === 'start' ? 'flex-1' : 'mx-auto text-center']">
            <EyebrowLabel v-if="eyebrow || editable" class="mb-2">
              <RCInlineText as="span" :model-value="eyebrow ?? ''" editable
                :placeholder="$t('rich-content.section_eyebrow')" @update:model-value="$emit('update:header', { eyebrow: $event })" />
            </EyebrowLabel>
            <RCInlineText
              :as="`h${resolvedLevel}`"
              :model-value="title ?? ''" editable :placeholder="$t('rich-content.enter_section_title')"
              :class="['u-display scroll-mt-32 text-balance', inverted ? 'text-current' : 'text-foreground', SECTION_HEADING_SIZE_CLASS[resolvedLevel]]"
              @update:model-value="$emit('update:header', { title: $event })"
            />
            <p v-if="subtitle || editable" :class="['mt-4 max-w-2xl leading-relaxed', inverted ? 'text-current/75' : 'text-muted-foreground', align === 'start' ? '' : 'mx-auto']">
              <RCInlineText as="span" :model-value="subtitle ?? ''" editable
                :placeholder="$t('rich-content.enter_section_subtitle')" @update:model-value="$emit('update:header', { subtitle: $event })" />
            </p>
          </div>
        </div>
      </div>

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
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCInlineText from './Editor/Fullscreen/RCInlineText.vue';
import type { BandResolution } from './bandLayout';
import {
  INNER_CLASS, SECTION_HEADING_SIZE_CLASS, type SectionHeadingLevel, type SectionInner,
} from './sectionClasses';

import SectionHeader from '@/Components/ui/SectionHeader.vue';
import { EyebrowLabel } from '@/Components/Public/Base';
import { latinizeId } from '@/Utils/String';

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
  /** Full-screen editor mode: title/subtitle/eyebrow become click-to-edit, gated on
   *  `hasHeaderField` — see the template comment above. */
  editable?: boolean;
}>(), {
  inner: 'wide',
  align: 'center',
  headingLevel: 2,
  showSeparator: true,
});

const emit = defineEmits<(e: 'update:header', patch: { title?: string; subtitle?: string; eyebrow?: string }) => void>();

// A second anchor target alongside the `#rc-{part.id}` id this section's root element
// gets from attribute fallthrough (see tocAnchors.ts) — a human-readable, sluggified
// heading id for direct linking/anchoring, matching tiptap's own heading ids.
const headingId = computed(() => (props.title ? latinizeId(props.title) : undefined));

const hasHeaderField = computed(() => !!(props.title || props.subtitle || props.eyebrow));
const resolvedLevel = computed<SectionHeadingLevel>(() => {
  const level = Number(props.headingLevel);
  return level === 3 || level === 4 ? level : 2;
});
const inverted = computed(() => props.band?.tint === 'emphasis');

// Mirrors SectionHeader.vue's own rootClass — kept in sync manually since the editable
// branch can't delegate to that `ui/` primitive (it has no editing concept of its own).
const headerRootClass = computed(() => [
  props.showSeparator ? 'mb-10 md:mb-12' : 'mb-6 md:mb-8',
  props.align === 'start' ? 'text-left' : 'text-center',
]);
</script>
