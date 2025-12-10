<template>
  <Suspense>
    <component 
      :is="editorComponent" 
      v-model="content.json_content" 
      v-model:options="content.options" />
    <template #fallback>
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
          <div class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600"></div>
          Loading {{ getContentType(content.type)?.label || content.type }} editor...
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
</template>

<script setup lang="ts">
/**
 * ContentEditorFactory
 * 
 * A dynamic component factory that selects and loads the appropriate editor
 * component based on the content type. This centralizes editor logic and
 * improves code organization by dynamically importing only the needed editor.
 */
import { computed, defineAsyncComponent } from 'vue';
import { getContentType } from './Types';
import { Skeleton } from '@/Components/ui/skeleton';

const props = defineProps<{
  /** 
   * Content object containing type, json_content, and options 
   */
  content: {
    type: string;
    json_content: any;
    options?: Record<string, any>;
  };
}>();

// Dynamic component mapping based on content type
const editorComponent = computed(() => {
  switch (props.content.type) {
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
</script>

