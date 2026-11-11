<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { router } from '@inertiajs/vue3';

import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import type { WikiPageSummary } from '@/Types/learning';

import WikiLinkCard from './WikiLinkCard.vue';

const props = defineProps<{ pages: Record<string, WikiPageSummary> }>();
const anchor = ref<HTMLAnchorElement | null>(null);
const open = ref(false);
const active = computed(() => anchor.value ? props.pages[anchor.value.dataset.knowledgeId ?? ''] ?? null : null);
let lastPointer = 'mouse';
let openTimer: ReturnType<typeof setTimeout> | undefined;
let closeTimer: ReturnType<typeof setTimeout> | undefined;

const linkFrom = (event: Event): HTMLAnchorElement | null => {
  const link = (event.target as HTMLElement | null)?.closest?.<HTMLAnchorElement>('a[data-knowledge-id]');
  return link && props.pages[link.dataset.knowledgeId ?? ''] ? link : null;
};

function show(link: HTMLAnchorElement): void {
  clearTimeout(closeTimer);
  anchor.value = link;
  open.value = true;
}

function scheduleClose(): void {
  clearTimeout(openTimer);
  closeTimer = setTimeout(() => { open.value = false; }, 200);
}

function keepOpen(): void {
  clearTimeout(closeTimer);
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

function onClick(event: MouseEvent): void {
  const link = linkFrom(event);
  if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
  event.preventDefault();
  event.stopPropagation();
  if (lastPointer !== 'mouse' && !(open.value && anchor.value === link)) {
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
  <div
    @pointerdown="lastPointer = $event.pointerType"
    @pointerover="onPointerOver"
    @pointerout="onPointerOut"
    @focusin="onFocusIn"
    @focusout="scheduleClose"
    @click.capture="onClick"
  >
    <slot />
    <Popover v-model:open="open">
      <PopoverAnchor :reference="anchor ?? undefined" />
      <PopoverContent
        v-if="active"
        side="bottom"
        align="start"
        :class="[active.thumbnailUrl ? 'w-96' : 'w-80', 'max-w-[calc(100vw-2rem)] rounded-none p-0']"
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
