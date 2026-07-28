<template>
  <div :class="layoutClasses">
    <!-- tiptap has no dedicated preview path: TiptapDisplay only reads `element.html`,
         a server-appended attribute that doesn't exist on unsaved rows, so it would
         render blank while editing. Render the live json_content directly instead. -->
    <RichContentTiptapHTML v-if="element.type === 'tiptap'" :json_content="element.json_content" />

    <Suspense v-else>
      <template #default>
        <component :is="displayComponent" :element="element" :html="false" :is-first-element="true" :resolved="resolvedForElement">
          <!-- shadcn-card takes its body through the default slot (see RichContentParser) —
               same live-json_content renderer, so its preview reflects unsaved edits too. -->
          <template v-if="element.type === 'shadcn-card'">
            <RichContentTiptapHTML :json_content="element.json_content" />
          </template>
        </component>
      </template>
      <template #fallback>
        <slot name="fallback">
          <div class="flex items-center gap-2 py-6 text-sm text-zinc-500">
            <div class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent" />
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
 * preview, BlockPickerDialog's live pane). Two things a naive `<component :is>` gets
 * wrong that this fixes:
 *
 * - Width: wraps in the same `.rc-canvas` column class RichContentParser would apply
 *   (`blockLayoutClasses`), so a block previewed at `full`/`wide` isn't silently clamped
 *   to the prose column the way the old per-surface implementations were.
 * - TipTap content: `TiptapDisplay`/`RichContentCard` read `element.html`, which is a
 *   server-appended attribute that only exists on *saved* rows. An unsaved/just-edited
 *   block has no `.html` yet, so previewing it through those components renders blank.
 *   Rendering the live `json_content` through `RichContentTiptapHTML` client-side keeps
 *   the preview in sync with keystrokes instead of only updating after a save.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { blockLayoutClasses } from '../blockLayout';
import { getContentType } from '../Types';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';

const props = defineProps<{
  element: { type: string; json_content: unknown; options?: Record<string, unknown> | null; id?: number };
  /** Server-resolved preview payload for this element (see useContentPartPreview), keyed by block key upstream. */
  resolved?: unknown;
}>();

const layoutClasses = computed(() => blockLayoutClasses(props.element));
const displayComponent = computed(() => getContentType(props.element.type).display);
// Same gate RichContentParser applies to the real `resolved` prop — an undeclared
// object prop on a display that doesn't ask for it would otherwise fall through and
// stringify into the DOM.
const resolvedForElement = computed(() => (getContentType(props.element.type).serverResolved ? props.resolved : undefined));
</script>
