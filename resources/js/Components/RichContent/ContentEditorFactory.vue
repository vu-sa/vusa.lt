<template>
  <div>
    <!-- Preview mode - show the display component with typography styling -->
    <div v-if="previewMode" class="typography mx-auto max-w-3xl">
      <Suspense>
        <component
          :is="displayComponent"
          :element="content"
          :html="false" />
        <template #fallback>
          <div class="flex items-center gap-2 text-sm text-zinc-500">
            <div class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent" />
            Loading preview...
          </div>
        </template>
      </Suspense>
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
import { computed, defineAsyncComponent } from 'vue';

import { getContentType } from './Types';

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

// Dynamic editor component mapping based on content type
const editorComponent = computed(() => {
  switch (content.value?.type) {
    case 'tiptap':
      return defineAsyncComponent(() => import('./Types/TiptapEditor.vue'));
    case 'shadcn-accordion':
      return defineAsyncComponent(() => import('./Types/AccordionEditor.vue'));
    case 'shadcn-card':
      return defineAsyncComponent(() => import('./Types/CardEditor.vue'));
    case 'image-grid':
      return defineAsyncComponent(() => import('./Types/ImageGridEditor.vue'));
    case 'hero':
      return defineAsyncComponent(() => import('./RCHeroSection/HeroForm.vue'));
    case 'spotify-embed':
      return defineAsyncComponent(() => import('./Types/SpotifyEmbedEditor.vue'));
    case 'social-embed':
      return defineAsyncComponent(() => import('./Types/SocialEmbedEditor.vue'));
    case 'flow-graph':
      return defineAsyncComponent(() => import('./Types/FlowGraphEditor.vue'));
    case 'number-stat-section':
      return defineAsyncComponent(() => import('./Types/NumberStatEditor.vue'));
    case 'news':
      return defineAsyncComponent(() => import('./Types/NewsEditor.vue'));
    case 'calendar':
      return defineAsyncComponent(() => import('./Types/CalendarEditor.vue'));
    case 'content-grid':
      return defineAsyncComponent(() => import('./Types/ContentGridEditor.vue'));
    default:
      return defineAsyncComponent(() => import('./Types/TiptapEditor.vue'));
  }
});

// Dynamic display component mapping for preview mode
const displayComponent = computed(() => {
  switch (content.value?.type) {
    case 'tiptap':
      return defineAsyncComponent(() => import('./Types/TiptapDisplay.vue'));
    case 'shadcn-accordion':
      return defineAsyncComponent(() => import('./RCAccordion.vue'));
    case 'shadcn-card':
      return defineAsyncComponent(() => import('./RichContentCard.vue'));
    case 'image-grid':
      return defineAsyncComponent(() => import('./Types/ImageGridDisplay.vue'));
    case 'hero':
      return defineAsyncComponent(() => import('./RCHeroSection/HeroElement.vue'));
    case 'spotify-embed':
      return defineAsyncComponent(() => import('./RCSpotifyEmbed.vue'));
    case 'social-embed':
      return defineAsyncComponent(() => import('./RCSocialEmbed.vue'));
    case 'flow-graph':
      return defineAsyncComponent(() => import('./RCFlowGraph.vue'));
    case 'number-stat-section':
      return defineAsyncComponent(() => import('./RCNumberStatSection/RCNumberSection.vue'));
    case 'news':
      return defineAsyncComponent(() => import('@/Components/Public/NewsElement.vue'));
    case 'calendar':
      return defineAsyncComponent(() => import('@/Components/Public/FullWidth/EventCalendarElement.vue'));
    case 'content-grid':
      return defineAsyncComponent(() => import('./Types/ContentGridDisplay.vue'));
    default:
      return defineAsyncComponent(() => import('./Types/TiptapDisplay.vue'));
  }
});
</script>
