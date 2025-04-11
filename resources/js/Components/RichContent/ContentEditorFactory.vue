<template>
  <component 
    :is="editorComponent" 
    v-model="content.json_content" 
    v-model:options="content.options" />
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

