<template>
  <div class="rc-prose tracking-normal" v-html="generateHTMLfromTiptap(json_content)" />
</template>

<script setup lang="ts">
import { createRenderExtensions, createRenderExtensions as createRenderExtensionsCore } from '../TipTap/extensions/presets';

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
