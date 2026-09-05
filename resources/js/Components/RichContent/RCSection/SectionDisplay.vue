<template>
  <component :is="band?.isBand === false ? 'div' : 'section'" :id="anchorId ? `rc-${anchorId}` : undefined"
    :class="band?.classes ?? []">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="!editable && title" :id="headingId" :title :subtitle :eyebrow :align :level="headingLevel" :show-separator :inverted="band?.tint === 'emphasis'" />

      <!-- Editable: same fields as SectionHeader.vue, laid out identically, but every
           field is a click-to-edit RCInlineText — plain text, not a rich TipTap doc (this
           block's title/subtitle/eyebrow are stored and rendered as plain strings, unlike
           Hero's HTML title). Renders even when every field is empty, so there's always
           something to click in the full-screen canvas — the public branch above still
           renders nothing at all in that case. -->
      <div v-else-if="editable" :class="headerRootClass">
        <div :class="['flex flex-wrap items-end justify-between gap-x-8 gap-y-3', showSeparator && 'border-b border-border pb-5']">
          <div :class="['min-w-0', align === 'start' ? 'flex-1' : 'mx-auto text-center']">
            <EyebrowLabel v-if="eyebrow || editable" class="mb-2">
              <RCInlineText as="span" :model-value="eyebrow ?? ''" editable
                :placeholder="$t('rich-content.section_eyebrow')" @update:model-value="updateOptions({ eyebrow: $event })" />
            </EyebrowLabel>
            <RCInlineText
              :as="`h${resolvedLevel}`"
              :model-value="title ?? ''" editable :placeholder="$t('rich-content.enter_section_title')"
              :class="['u-display scroll-mt-32 text-balance text-foreground', SECTION_HEADING_SIZE_CLASS[resolvedLevel]]"
              @update:model-value="updateOptions({ title: $event })"
            />
            <p v-if="subtitle || editable" :class="['mt-4 max-w-2xl leading-relaxed text-muted-foreground', align === 'start' ? '' : 'mx-auto']">
              <RCInlineText as="span" :model-value="subtitle ?? ''" editable
                :placeholder="$t('rich-content.enter_section_subtitle')" @update:model-value="updateOptions({ subtitle: $event })" />
            </p>
          </div>
        </div>
      </div>

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
import { trans as $t } from 'laravel-vue-i18n';

import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import type { BandResolution } from '../bandLayout';
import {
  INNER_CLASS, SECTION_HEADING_SIZE_CLASS, type SectionHeadingLevel,
} from '../sectionClasses';

import SectionHeader from '@/Components/ui/SectionHeader.vue';
import { EyebrowLabel } from '@/Components/Public/Base';
import { latinizeId } from '@/Utils/String';
import type { Section } from '@/Types/contentParts';

const props = defineProps<{
  element: Section;
  anchorId?: number | null;
  hasChildren?: boolean;
  /** This section's resolved chrome — computed by `RichContentParser` via `resolveBands`. */
  band?: BandResolution;
  /** Full-screen editor mode: title/subtitle/eyebrow become click-to-edit. Undefined/false
   *  in every other context (public rendering, forms-mode preview, the block picker). */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field claiming (every field
   *  here is a plain RCInlineText, never a mounted TipTap doc), but an undeclared
   *  non-undefined prop would otherwise land on the root `<section>` as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: Section) => void>();

const title = computed(() => props.element.options?.title);
const subtitle = computed(() => props.element.options?.subtitle);
const eyebrow = computed(() => props.element.options?.eyebrow);
const inner = computed(() => props.element.options?.inner ?? 'full');
const align = computed(() => props.element.options?.align ?? 'center');
const headingLevel = computed<SectionHeadingLevel>(() => props.element.options?.headingLevel ?? 2);
const showSeparator = computed(() => props.element.options?.showSeparator ?? true);
const resolvedLevel = computed<SectionHeadingLevel>(() => {
  const level = Number(headingLevel.value);
  return level === 3 || level === 4 ? level : 2;
});

const headingId = computed(() => (title.value ? latinizeId(title.value) : undefined));

// Mirrors SectionHeader.vue's own rootClass — kept in sync manually since the editable
// branch can't delegate to that `ui/` primitive (it has no editing concept of its own).
const headerRootClass = computed(() => [
  showSeparator.value ? 'mb-10 md:mb-12' : 'mb-6 md:mb-8',
  align.value === 'start' ? 'text-left' : 'text-center',
]);

function updateOptions(patch: Partial<Section['options']>): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}
</script>
