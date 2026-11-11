<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import type { WikiPageSummary } from '@/Types/learning';

import WikiLinkCard from './WikiLinkCard.vue';

const props = withDefaults(defineProps<{
  html: string;
  /** Articles the body links to; a link to anything missing (deleted, hidden) reads as plain text. */
  pages?: Record<string, WikiPageSummary>;
  proseClass?: string;
}>(), { pages: () => ({}), proseClass: '' });

const root = ref<HTMLElement | null>(null);
const anchor = ref<HTMLElement | null>(null);
const open = ref(false);
const activeId = ref<string | null>(null);
const active = computed(() => activeId.value ? props.pages[activeId.value] ?? null : null);
let lastPointer = 'mouse';
let openTimer: ReturnType<typeof setTimeout> | undefined;
let closeTimer: ReturnType<typeof setTimeout> | undefined;

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

const linkFrom = (event: Event): HTMLAnchorElement | null =>
  (event.target as HTMLElement | null)?.closest?.<HTMLAnchorElement>('a.wiki-link') ?? null;

function show(link: HTMLAnchorElement): void {
  clearTimeout(closeTimer);
  anchor.value = link;
  activeId.value = link.dataset.knowledgeId ?? null;
  open.value = true;
}

function scheduleClose(): void {
  clearTimeout(openTimer);
  closeTimer = setTimeout(() => { open.value = false; }, 200);
}

function keepOpen(): void {
  clearTimeout(closeTimer);
}

function onPointerDown(event: PointerEvent): void {
  lastPointer = event.pointerType;
}

function onPointerOver(event: PointerEvent): void {
  const link = linkFrom(event);
  if (!link || event.pointerType !== 'mouse') return;
  clearTimeout(closeTimer);
  clearTimeout(openTimer);
  openTimer = setTimeout(() => show(link), 350);
}

function onPointerOut(event: PointerEvent): void {
  if (linkFrom(event) && event.pointerType === 'mouse') scheduleClose();
}

function onFocusIn(event: FocusEvent): void {
  const link = linkFrom(event);
  if (link && link.matches(':focus-visible')) show(link);
}

// On touch the first tap previews and a second tap on the same link opens it.
function onClick(event: MouseEvent): void {
  const link = linkFrom(event);
  if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
  event.preventDefault();
  const id = link.dataset.knowledgeId ?? null;
  if (lastPointer !== 'mouse' && !(open.value && activeId.value === id)) {
    show(link);
    return;
  }
  open.value = false;
  router.visit(link.href);
}

onBeforeUnmount(() => {
  clearTimeout(openTimer);
  clearTimeout(closeTimer);
});
</script>

<template>
  <div data-slot="wiki-prose">
    <!-- Sanitized on write (HtmlSanitizerService); links are decorated after render. -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      ref="root"
      :class="['rc-prose', proseClass]"
      @pointerdown="onPointerDown"
      @pointerover="onPointerOver"
      @pointerout="onPointerOut"
      @focusin="onFocusIn"
      @focusout="scheduleClose"
      @click="onClick"
      v-html="html"
    />
    <Popover v-model:open="open">
      <PopoverAnchor :reference="anchor ?? undefined" />
      <PopoverContent
        v-if="active"
        side="bottom"
        align="start"
        class="w-80 max-w-[calc(100vw-2rem)] rounded-none p-0"
        :trap-focus="false"
        @open-auto-focus.prevent
        @close-auto-focus.prevent
        @pointerenter="keepOpen"
        @pointerleave="scheduleClose"
      >
        <WikiLinkCard :page="active" />
      </PopoverContent>
    </Popover>
  </div>
</template>
