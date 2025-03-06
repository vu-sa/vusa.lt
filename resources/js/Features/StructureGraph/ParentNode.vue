<template>
  <button class="h-full w-full relative" @mouseenter="handleOpen" @mouseleave="handleClose"
    @blur="handleClose" @focus="isVisible = true">
    <!-- <component :is="data.label" /> -->
    <span :class=[data.textClass] class="absolute top-2 text-xs left-0 text-center leading-4 w-full" v-if="typeof data.label === 'string'">{{ data.label }}</span>
    <component v-else :is="data.label" />
  </button>
  <Handle type="source" :position="sourcePosition" />
  <Handle type="target" :position="targetPosition" />
  <NodeToolbar @mouseenter="isOnToolbar = true" @mouseleave="handleToolbarLeave" v-if="data.description || data.url"
    class="text-xs shadow-sm max-w-md rounded-sm bg-slate-50 p-3" :is-visible position="top">
    <strong v-if="typeof data.label === 'string'" class="block">{{ data.label }}</strong>
    {{
      data.description }}
    <a @click.stop rel="noopener noreferrer" @mouseenter="isOnToolbar = true" v-if="data.url" :href="data.url"
      target="_blank" class="underline">Nuoroda</a>
  </NodeToolbar>
</template>

<script setup>
import { Handle } from '@vue-flow/core';
import { NodeToolbar } from '@vue-flow/node-toolbar';
import { ref } from 'vue'

defineProps(['id', 'sourcePosition', 'targetPosition', 'data'])

const isVisible = ref(false);
const isOnButton = ref(false);
const isOnToolbar = ref(false);

const handleOpen = () => {
  isOnButton.value = true;
  setTimeout(() => {
    if (isOnButton.value) {
      isVisible.value = true;
    }
  }, 200)
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
