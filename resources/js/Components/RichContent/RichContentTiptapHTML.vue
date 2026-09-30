<template>
  <div class="rc-prose tracking-normal" v-html="mounted ? generateHTMLfromTiptap(json_content) : ''" />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

import { createRenderExtensions as createRenderExtensionsCore } from '../TipTap/extensions/render';

// DOM serialization must wait until the server-rendered markup has hydrated.
const mounted = ref(typeof document !== 'undefined' && !document.querySelector('#app[data-server-rendered]'));
onMounted(() => { mounted.value = true; });

defineProps<{
  json_content: Record<string, unknown>;
}>();
</script>

<script lang="ts">
import { generateHTML as generateHTMLCore } from '@tiptap/core';

// Export this function so it can be used in other components
export const generateHTMLfromTiptap = (json_content: Record<string, unknown>) => {
  if (!json_content || Object.keys(json_content).length === 0) {
    return '';
  }

  return wrapTablesForScroll(generateHTMLCore(json_content, createRenderExtensionsCore()));
};

/**
 * Static `generateHTML` output (unlike the live editor's ProseMirror NodeView) never
 * gets the `.tableWrapper` div, so a resized table's explicit column widths can overflow
 * the reading measure with no way to scroll to the rest of it. Mirrors the wrapper
 * `App\Tiptap\TiptapEditor::getHTML()` adds to the server-rendered HTML.
 */
function wrapTablesForScroll(html: string): string {
  if (!html.includes('<table')) {
    return html;
  }

  const container = document.createElement('div');
  container.innerHTML = html;
  container.querySelectorAll('table').forEach((table) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'tableWrapper';
    table.replaceWith(wrapper);
    wrapper.append(table);
  });

  return container.innerHTML;
}
</script>
