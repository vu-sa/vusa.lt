<template>
  <div class="rc-prose tracking-normal" v-html="generateHTMLfromTiptap(json_content)" />
</template>

<script setup lang="ts">
defineProps<{
  json_content: Record<string, unknown>;
}>();
</script>

<script lang="ts">
import { renderToHTMLString } from '@tiptap/static-renderer/pm/html-string';
import { trans } from 'laravel-vue-i18n';

import { createRenderExtensions } from '../TipTap/extensions/render';

const renderExtensions = createRenderExtensions();

// DOM-free, so SSR and hydration produce the same markup (`generateHTML` needs a document).
export const generateHTMLfromTiptap = (json_content: Record<string, unknown>) => {
  if (!json_content || Object.keys(json_content).length === 0) {
    return '';
  }

  try {
    return renderToHTMLString({ content: json_content, extensions: renderExtensions });
  } catch {
    return `<p>${trans('Turinio nepavyko atvaizduoti')}</p>`;
  }
};
</script>
