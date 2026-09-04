<template>
  <component :is="band?.isBand === false ? 'div' : 'section'" :class="band?.classes ?? []"
    :id="anchorId ? `rc-${anchorId}` : undefined">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="title" :title :subtitle :eyebrow :align :id="headingId" :level="headingLevel" :show-separator="showSeparator" :inverted="band?.tint === 'emphasis'" />
      <!-- A nested `.rc-canvas` so wrapped child blocks keep their own independent
           per-block widths (prose/content/wide/full) exactly like the page-level
           canvas — see `.rc-canvas-nested` in app.css for why `--rc-measure` needs a
           reset there. Only rendered when `RichContentParser` actually gave this
           section children; previewed standalone (picker, single-block editor
           preview) it has none, and renders header-only — a valid use case in its
           own right (a plain divider/heading, `options.wraps: 'none'`). -->
      <div v-if="hasChildren" class="rc-canvas rc-canvas-nested">
        <slot />
      </div>
    </div>
  </component>
</template>

<script setup lang="ts">
/**
 * Display for the standalone `section` block — a marker block that
 * `RichContentParser`'s `groupedContent` groups every following content part under,
 * until the next `section` marker (or the end of the content), rendered here as a
 * real `<section>` element via the default slot. Takes the standard `element`/
 * `anchorId` display contract like every other block so it previews correctly,
 * header-only, in the block picker and the single-block editor preview — `hasChildren`
 * is the one addition, set only by `RichContentParser` once it knows what this
 * section actually wraps.
 */
import { computed } from 'vue';

import SectionHeader from '@/Components/ui/SectionHeader.vue';
import { latinizeId } from '@/Utils/String';
import type { Section } from '@/Types/contentParts';
import type { BandResolution } from '../bandLayout';
import { INNER_CLASS, type SectionHeadingLevel } from '../sectionClasses';

const props = defineProps<{
  element: Section;
  anchorId?: number | null;
  hasChildren?: boolean;
  /** This section's resolved chrome — computed by `RichContentParser` via `resolveBands`. */
  band?: BandResolution;
}>();

const title = computed(() => props.element.options?.title);
const subtitle = computed(() => props.element.options?.subtitle);
const eyebrow = computed(() => props.element.options?.eyebrow);
const inner = computed(() => props.element.options?.inner ?? 'full');
const align = computed(() => props.element.options?.align ?? 'center');
const headingLevel = computed<SectionHeadingLevel>(() => props.element.options?.headingLevel ?? 2);
const showSeparator = computed(() => props.element.options?.showSeparator ?? true);

const headingId = computed(() => (title.value ? latinizeId(title.value) : undefined));
</script>
