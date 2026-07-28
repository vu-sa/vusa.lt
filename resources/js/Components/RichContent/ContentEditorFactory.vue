<template>
  <div>
    <!-- Preview mode - show the display component inside a canvas so width-aware
         blocks (hero, accordion, …) render the same as they will on the public page,
         and at the block's own options.width column rather than always the prose one. -->
    <div v-if="previewMode" class="rc-canvas" style="--rc-measure: 44rem">
      <BlockPreviewRenderer v-if="content" :element="content" :resolved="previewResolved" />
    </div>

    <!-- Edit mode - show the editor component -->
    <Suspense v-else>
      <component
        :is="editorComponent"
        v-model="jsonContent"
        v-model:options="contentOptions" />
      <template #fallback>
        <div class="space-y-3">
          <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <div class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600" />
            Loading {{ getContentType(content?.type)?.label || content?.type }} editor...
          </div>
          <div class="rounded-lg border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <Skeleton class="h-24 w-full mb-3" />
            <div class="grid grid-cols-3 gap-2">
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
            </div>
          </div>
        </div>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
/**
 * ContentEditorFactory
 *
 * A dynamic component factory that selects and loads the appropriate editor
 * component based on the content type. This centralizes editor logic and
 * improves code organization by dynamically importing only the needed editor.
 *
 * Supports preview mode to show the display component instead of editor.
 */
import { computed } from 'vue';

import { getContentType } from './Types';

import BlockPreviewRenderer from './Editor/BlockPreviewRenderer.vue';
import { useLiveBlockPreview } from './composables/useLiveBlockPreview';

import { Skeleton } from '@/Components/ui/skeleton';

interface ContentData {
  type: string;
  json_content: any;
  options?: Record<string, any>;
}

// Prevent Vue warning about attribute inheritance
defineOptions({
  inheritAttrs: false,
});

const props = defineProps<{
  /**
   * Whether to show preview mode (display component) instead of editor
   */
  previewMode?: boolean;
  /** Tenant the page/news article being edited belongs to — needed to resolve server-side (link-list, event-list, …) previews. */
  tenantId?: number | null;
}>();

/**
 * Content model - using defineModel for proper two-way binding
 * This ensures changes from child editors (HeroForm, CardEditor, etc.)
 * properly propagate up to the parent RichContentEditor
 */
const content = defineModel<ContentData>('content', { required: true });

/**
 * Writable computed for json_content - enables proper two-way binding
 * When child editors emit updates, this triggers the content model update.
 * We mutate in place to avoid creating new object references which would
 * cause unnecessary re-renders and unfocus editors.
 */
const jsonContent = computed({
  get: () => content.value?.json_content,
  set: (value) => {
    if (content.value) {
      // Mutate in place to preserve object identity and avoid re-renders
      content.value.json_content = value;
    }
  },
});

/**
 * Writable computed for options - enables proper two-way binding
 * When child editors emit options updates, this triggers the content model update.
 * We mutate in place to avoid creating new object references.
 *
 * If options is null/undefined, initialize it with the content type's default options
 * or an empty object to ensure child components can bind to it.
 */
const contentOptions = computed({
  get: () => {
    if (content.value && !content.value.options) {
      // Initialize options if null - needed for existing content that was saved without options
      const contentType = getContentType(content.value.type);
      content.value.options = contentType.defaultOptions ? contentType.defaultOptions() : {};
    }
    return content.value?.options;
  },
  set: (value) => {
    if (content.value) {
      // Mutate in place to preserve object identity and avoid re-renders
      content.value.options = value;
    }
  },
});

// Editor component comes straight from the registry (Types/index.ts) — adding a content
// type no longer means adding a case here too. Preview mode's display component is
// resolved inside BlockPreviewRenderer, shared with BlockPickerDialog.
const editorComponent = computed(() => getContentType(content.value?.type ?? 'tiptap').editor);

// Server-resolved preview data (link-list, event-list, …) for this single block, kept
// in sync with edits while `previewMode` is on. Fetches nothing for types that don't
// declare `serverResolved` in the registry.
const { previewResolved } = useLiveBlockPreview(content, () => props.tenantId, () => !!props.previewMode);
</script>
