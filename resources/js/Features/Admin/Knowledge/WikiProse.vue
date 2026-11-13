<template>
  <WikiLinkPreview :pages data-slot="wiki-prose">
    <!-- Sanitized on write (HtmlSanitizerService); links are decorated after render. -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div ref="root" :class="['rc-prose wiki-prose', proseClass]" v-html="html" />
  </WikiLinkPreview>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue';

import type { WikiPageSummary } from '@/Types/learning';

import WikiLinkPreview from './WikiLinkPreview.vue';

const props = withDefaults(defineProps<{
  html: string;
  /** Articles the body links to; a link to anything missing (deleted, hidden) reads as plain text. */
  pages?: Record<string, WikiPageSummary>;
  proseClass?: string;
}>(), { pages: () => ({}), proseClass: '' });

const root = ref<HTMLElement | null>(null);

function decorate(): void {
  root.value?.querySelectorAll<HTMLAnchorElement>('a[data-knowledge-id]').forEach((link) => {
    const page = props.pages[link.dataset.knowledgeId ?? ''];
    if (!page) {
      link.replaceWith(document.createTextNode(link.textContent ?? ''));
      return;
    }
    link.href = page.url;
    link.removeAttribute('target');
    link.removeAttribute('rel');
    link.setAttribute('aria-haspopup', 'dialog');
    link.className = 'wiki-link';
  });
}

onMounted(decorate);
watch(() => [props.html, props.pages], () => nextTick(decorate));
</script>
