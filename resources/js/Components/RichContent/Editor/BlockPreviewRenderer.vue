<template>
  <!-- The preview renders on the public surface, because that is where the block will end up:
       warm paper, square corners, brand red or amber. Without this the editor showed authors an
       admin-palette version of a block that looks different the moment it is published. -->
  <div data-surface="public" :class="['bg-background text-foreground font-public', layoutClasses]">
    <!-- tiptap: when not inline-editable (e.g. BlockPickerDialog / static preview), render
         live json_content directly through RichContentTiptapHTML. When inline-editable,
         TiptapDisplay mounts the interactive editor canvas and smart toolbar. -->
    <RichContentTiptapHTML v-if="element.type === 'tiptap' && !editableForElement" :json_content="element.json_content" />

    <Suspense v-else>
      <template #default>
        <component :is="displayComponent" :element :html="false" :is-first-element="true"
          :resolved="resolvedForElement" :band="bandForElement"
          :editable="editableForElement" :active-inline-field="activeInlineFieldForElement"
          :block-key="blockKeyForElement"
          @update:element="$emit('update:element', $event)"
          @claim-inline-field="$emit('claim-inline-field', $event)"
        >
          <template v-if="element.type === 'shadcn-card' && !editableForElement">
            <RichContentTiptapHTML :json_content="element.json_content" />
          </template>
        </component>
      </template>
      <template #fallback>
        <slot name="fallback">
          <div class="flex items-center gap-2 py-6 text-sm text-muted-foreground">
            <div class="h-3 w-3 animate-spin rounded-full border-2 border-border border-r-transparent" />
            {{ $t('rich-content.loading_preview') }}
          </div>
        </slot>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
/**
 * Renders a content type's live *display* component from unsaved editor state — the one
 * implementation shared by every preview surface (ContentEditorFactory's per-block
 * preview, BlockPickerDialog's live pane, and — via `bandSlot`/`editable` — the
 * full-screen editor's `RCFullscreenBlock`). Several things a naive `<component :is>`
 * gets wrong that this fixes:
 *
 * - Surface: stamps `data-surface="public"`, so the preview resolves the public palette and
 *   radius scale rather than the admin one it is embedded in.
 * - Width: wraps in the same `.rc-canvas` column class RichContentParser would apply
 *   (`blockLayoutClasses`), so a block previewed at `full`/`wide` isn't silently clamped
 *   to the prose column the way the old per-surface implementations were.
 * - TipTap content: server-rendered HTML only exists on saved rows. Rendering live
 *   `json_content` through `RichContentTiptapHTML` keeps previews in sync with
 *   keystrokes instead of only updating after a save.
 * - `editable`/`band`/`resolved` are all gated on the type declaring the matching
 *   registry field (`inlineEditable`/`bandRole`/`serverResolved`) — an undeclared object
 *   prop on a display that doesn't ask for it would otherwise fall through and
 *   stringify into the DOM.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { blockLayoutClasses } from '../blockLayout';
import { getContentType } from '../Types';
import { resolveBand, resolveBandRole, type BandResolution } from '../bandLayout';
import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';

const props = defineProps<{
  element: { type: string; json_content: unknown; options?: Record<string, unknown> | null; id?: number };
  /** Server-resolved preview payload for this element (see useContentPartPreview), keyed by block key upstream. */
  resolved?: unknown;
  /** This block's position among the document's *other* bands — see bandLayout.ts's `resolveBands`. Standalone previews (picker, side-by-side) omit it and get slot 0. Ignored when `band` is supplied directly. */
  bandSlot?: number;
  /** Pre-resolved band chrome, when the caller already ran `resolveBands()` over the whole document (RCFullscreenEditor) — skips a redundant re-resolve and guarantees the exact same alternation the public parser would produce. Takes priority over `bandSlot`. */
  band?: BandResolution;
  /** Full-screen editor only: this block is being edited, so its `inlineEditable` display should render live text instead of static. */
  editable?: boolean;
  /** Full-screen editor only: render the published display without edit-only controls. */
  preview?: boolean;
  /** This block's identity for hotspot/`activeInlineField` ids (see useActiveHotspot.ts). Required whenever `editable` is used. */
  blockKey?: string;
  /** `${blockKey}:${fieldPath}` of whichever field across the whole document is the one live Tiptap-mounted field right now. */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: BlockPreviewRendererProps['element']): void;
  (e: 'claim-inline-field', field: string | null): void;
}>();

type BlockPreviewRendererProps = typeof props;

const layoutClasses = computed(() => blockLayoutClasses(props.element));
const type = computed(() => getContentType(props.element.type));
// Full-screen editor only: is this block actually being edited right now (not merely
// `inlineEditable`-capable, and not the "preview" mode that shows the published look
// without edit controls)?
const isEditingElement = computed(() => !!props.editable && !props.preview);
// A type with its own `editableDisplay` (see Types/index.ts) keeps `display` pure —
// route to the editable twin only while actually editing; everywhere else (public
// rendering, the block picker, forms-mode preview) renders the same `display` a public
// page would.
const usesSplitEditable = computed(() => isEditingElement.value && !!type.value.editableDisplay);
const displayComponent = computed(() => (usesSplitEditable.value ? type.value.editableDisplay! : type.value.display));
// Same gate RichContentParser applies to the real `resolved` prop — an undeclared
// object prop on a display that doesn't ask for it would otherwise fall through and
// stringify into the DOM.
const resolvedForElement = computed(() => (type.value.serverResolved ? props.resolved : undefined));
// Slot 0 by default — a standalone preview (picker, single-block editor) has no
// surrounding document to alternate against (see bandLayout.ts's `resolveBand`
// docblock); the full-screen editor passes the block's real position via `bandSlot`.
const bandForElement = computed(() => {
  if (resolveBandRole(props.element.type, props.element.options) !== 'band') return undefined;
  return props.band ?? resolveBand(props.element, props.bandSlot ?? 0);
});
// Legacy single-component types still branch on `editable`/`activeInlineField` inside
// their own `display` — a type with an `editableDisplay` hands editing state to that
// component's own props instead (see HeroEditableElement.vue), so `display` never
// receives these at all, and stays pure.
const usesLegacyEditableProps = computed(() => !!type.value.inlineEditable && !type.value.editableDisplay);
const editableForElement = computed(() => (usesLegacyEditableProps.value ? isEditingElement.value : undefined));
const activeInlineFieldForElement = computed(() => (usesLegacyEditableProps.value ? (props.activeInlineField ?? null) : undefined));
const blockKeyForElement = computed(() => ((usesLegacyEditableProps.value || usesSplitEditable.value) ? (props.blockKey ?? '') : undefined));
</script>
