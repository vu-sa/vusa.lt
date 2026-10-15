<template>
  <!--
    In place, never teleported or modal: a modal dialog made every other portal inert, so the
    ActionWindow and dialogs opened from a full-screen chart landed behind it. Pinned below
    the portals' z-50, they now stack above it and stay interactive.
  -->
  <div
    data-slot="focus-mode-frame"
    :data-active="active || undefined"
    role="region"
    :aria-label="label"
    :class="active && 'fixed inset-0 z-[45] m-0! h-dvh! max-h-none! w-screen overflow-hidden bg-background p-3 sm:p-4'"
  >
    <slot :active :toggle />
  </div>
</template>

<script setup lang="ts">
import { onUnmounted, watch } from 'vue';
import { useEventListener } from '@vueuse/core';

defineProps<{
  label: string;
}>();

const active = defineModel<boolean>('active', { default: false });

/** A layer that owns Escape first: closing it must not also drop out of full screen. */
const OPEN_LAYER = '[role="dialog"][data-state="open"], [role="alertdialog"][data-state="open"], [role="menu"][data-state="open"], [data-slot="popover-content"][data-state="open"]';

let returnFocus: HTMLElement | null = null;

function toggle(): void {
  active.value = !active.value;
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key !== 'Escape' || event.defaultPrevented) return;
  if (document.querySelector(OPEN_LAYER)) return;

  active.value = false;
}

useEventListener(document, 'keydown', (event) => {
  if (active.value) {
    onKeydown(event);
  }
});

function scrollArea(): HTMLElement | null {
  return document.querySelector<HTMLElement>('[data-slot="admin-scroll-area"]');
}

watch(active, (isActive) => {
  const area = scrollArea();

  if (isActive) {
    returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    if (area) area.style.overflow = 'hidden';

    return;
  }

  if (area) area.style.overflow = '';
  returnFocus?.focus();
  returnFocus = null;
});

onUnmounted(() => {
  if (active.value) {
    const area = scrollArea();
    if (area) area.style.overflow = '';
  }
});
</script>
