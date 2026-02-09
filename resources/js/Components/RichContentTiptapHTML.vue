<template>
  <div class="tracking-normal" v-html="generateHTMLfromTiptap(json_content)" />
</template>

<script setup lang="ts">
import { createRenderExtensions, createRenderExtensions as createRenderExtensionsCore } from './TipTap/extensions/presets';

defineProps<{
  json_content: any;
}>();
</script>

<script lang="ts">
import { generateHTML as generateHTMLCore } from '@tiptap/vue-3';

// Export this function so it can be used in other components
export const generateHTMLfromTiptap = (json_content: any) => {
  if (!json_content || Object.keys(json_content).length === 0) {
    return '';
  }

  return generateHTMLCore(json_content, createRenderExtensionsCore());
};
</script>
