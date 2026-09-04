<template>
  <!-- The preview renders on the public surface, because that is where the block will end up:
       warm paper, square corners, brand red or amber. Without this the editor showed authors an
       admin-palette version of a block that looks different the moment it is published. -->
  <div data-surface="public" :class="['bg-background text-foreground font-public', layoutClasses]">
    <!-- tiptap has no dedicated preview path: TiptapDisplay only reads `element.html`,
         a server-appended attribute that doesn't exist on unsaved rows, so it would
         render blank while editing. Render the live json_content directly instead. -->
    <RichContentTiptapHTML v-if="element.type === 'tiptap'" :json_content="element.json_content" />

    <Suspense v-else>
      <template #default>
        <component :is="displayComponent" :element="element" :html="false" :is-first-element="true"
          :resolved="resolvedForElement" :band="bandForElement"
          :editable="editableForElement" :active-inline-field="activeInlineFieldForElement"
          :block-key="editableForElement !== undefined ? blockKey : undefined"
          @update:element="$emit('update:element', $event)"
          @claim-inline-field="$emit('claim-inline-field', $event)"
        >
          <!-- shadcn-card takes its body through the default slot (see RichContentParser) —
               same live-json_content renderer, so its preview reflects unsaved edits too.
               In the full-screen editor (editable + this block's body field claimed) it
               mounts a real TiptapEditor instead — the one Tiptap-doc inline-editable
               field this type has. -->
          <template v-if="element.type === 'shadcn-card'">
            <TiptapEditor
              v-if="editableForElement && activeInlineFieldForElement === `${blockKey}:body`"
              :model-value="cardBodyContent" preset="full" prose-style
              @update:model-value="$emit('update:element', { ...element, json_content: $event })"
            />
            <div v-else :data-rc-interactive="editableForElement ? '' : undefined" @click="editableForElement && $emit('claim-inline-field', `${blockKey}:body`)">
              <RichContentTiptapHTML :json_content="element.json_content" />
            </div>
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
 * - TipTap content: `TiptapDisplay`/`RichContentCard` read `element.html`, which is a
 *   server-appended attribute that only exists on *saved* rows. An unsaved/just-edited
 *   block has no `.html` yet, so previewing it through those components renders blank.
 *   Rendering the live `json_content` through `RichContentTiptapHTML` client-side keeps
 *   the preview in sync with keystrokes instead of only updating after a save.
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
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

const props = defineProps<{
  element: { type: string; json_content: any; options?: Record<string, unknown> | null; id?: number };
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
const displayComponent = computed(() => getContentType(props.element.type).display);
// Same gate RichContentParser applies to the real `resolved` prop — an undeclared
// object prop on a display that doesn't ask for it would otherwise fall through and
// stringify into the DOM.
const resolvedForElement = computed(() => (getContentType(props.element.type).serverResolved ? props.resolved : undefined));
// Slot 0 by default — a standalone preview (picker, single-block editor) has no
// surrounding document to alternate against (see bandLayout.ts's `resolveBand`
// docblock); the full-screen editor passes the block's real position via `bandSlot`.
const bandForElement = computed(() => {
  if (resolveBandRole(props.element.type, props.element.options) !== 'band') return undefined;
  return props.band ?? resolveBand(props.element, props.bandSlot ?? 0);
});
const editableForElement = computed(() => (getContentType(props.element.type).inlineEditable ? !!props.editable && !props.preview : undefined));
const activeInlineFieldForElement = computed(() => (getContentType(props.element.type).inlineEditable ? (props.activeInlineField ?? null) : undefined));
const blockKey = computed(() => props.blockKey ?? '');
const cardBodyContent = computed(() => (typeof props.element.json_content === 'object' && props.element.json_content ? props.element.json_content : { type: 'doc', content: [] }));
</script>
