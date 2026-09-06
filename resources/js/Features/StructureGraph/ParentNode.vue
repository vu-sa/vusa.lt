<template>
  <button type="button"
    class="relative h-full w-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    @mouseenter="handleOpen" @mouseleave="handleClose"
    @blur="handleClose" @focus="isVisible = true">
    <!-- <component :is="data.label" /> -->
    <span v-if="typeof data.label === 'string'" :class="[data.textClass]" class="absolute top-2 text-xs left-0 text-center leading-4 w-full">{{ data.label }}</span>
    <component :is="data.label" v-else />
  </button>
  <Handle type="source" :position="sourcePosition" />
  <Handle type="target" :position="targetPosition" />
  <NodeToolbar v-if="data.description || data.url" class="max-w-md border border-border bg-popover p-3 text-xs text-popover-foreground shadow-sm" :is-visible
    position="top" @mouseenter="isOnToolbar = true" @mouseleave="handleToolbarLeave"
    @focusin="isOnToolbar = true" @focusout="handleToolbarLeave">
    <strong v-if="typeof data.label === 'string'" class="block">{{ data.label }}</strong>
    {{
      data.description }}
    <a v-if="data.url" rel="noopener noreferrer" :href="data.url" target="_blank" class="text-brand underline"
      @click.stop @mouseenter="isOnToolbar = true" @focus="isOnToolbar = true">Nuoroda</a>
  </NodeToolbar>
</template>

<script setup>
import { Handle } from '@vue-flow/core';
import { NodeToolbar } from '@vue-flow/node-toolbar';
import { ref } from 'vue';

defineProps(['id', 'sourcePosition', 'targetPosition', 'data']);

const isVisible = ref(false);
const isOnButton = ref(false);
const isOnToolbar = ref(false);

const handleOpen = () => {
  isOnButton.value = true;
  setTimeout(() => {
    if (isOnButton.value) {
      isVisible.value = true;
    }
  }, 200);
};

const handleClose = () => {
  isOnButton.value = false;
  setTimeout(() => {
    if (!isOnToolbar.value) {
      isVisible.value = false;
    }
  }, 180);
};

const handleToolbarLeave = () => {
  isOnToolbar.value = false;
  handleClose();
};
</script>
