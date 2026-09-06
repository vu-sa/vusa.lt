<template>
  <button type="button"
    class="h-full w-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    :class="[data.nodeClass]" @mouseenter="handleOpen" @mouseleave="handleClose"
    @blur="handleClose" @focus="handleOpen">
    <!-- <component :is="data.label" /> -->
    <span v-if="typeof data.label === 'string'" :class="[data.textClass]"
      class="text-[13.4px] p-2 block w-fit mx-auto">{{ data.label }}</span>
    <component :is="data.label" v-else />

    <template v-if="data?.handles">
      <Handle v-if="data?.handles?.includes('right')" id="right" :position="Position.Right" />
      <Handle v-if="data?.handles?.includes('left-top')" id="left-top" :position="Position.Left" style="top: 25%" />
      <Handle v-if="data?.handles?.includes('left')" id="left" :position="Position.Left" />
      <Handle v-if="data?.handles?.includes('left-bottom')" id="left-bottom" :position="Position.Left"
        style="top: 75%" />
      <Handle v-if="data?.handles?.includes('top-left')" id="top-left" :position="Position.Top" style="left: 10%" />
      <Handle v-if="data?.handles?.includes('top')" id="top" :position="Position.Top" />
      <Handle v-if="data?.handles?.includes('top-right')" id="top-right" :position="Position.Top" style="left: 90%" />
      <Handle v-if="data?.handles?.includes('right-top')" id="right-top" :position="Position.Right" style="top: 25%" />
      <Handle v-if="data?.handles?.includes('right-bottom')" id="right-bottom" :position="Position.Right"
        style="top: 75%" />
      <Handle v-if="data?.handles?.includes('bottom-left')" id="bottom-left" :position="Position.Bottom"
        style="left: 10%" />
      <Handle v-if="data?.handles?.includes('bottom')" id="bottom" :position="Position.Bottom" />
      <Handle v-if="data?.handles?.includes('bottom-right')" id="bottom-right" :position="Position.Bottom"
        style="left: 90%" />
    </template>
    <template v-else>
      <Handle id="left-top" :position="Position.Left" style="top: 25%" />
      <Handle id="left" :position="Position.Left" />
      <Handle id="left-bottom" :position="Position.Left" style="top: 75%" />
      <Handle id="top-left" :position="Position.Top" style="left: 25%" />
      <Handle id="top" :position="Position.Top" />
      <Handle id="top-right" :position="Position.Top" style="left: 75%" />
      <Handle id="right-top" :position="Position.Right" style="top: 25%" />
      <Handle id="right" :position="Position.Right" />
      <Handle id="right-bottom" :position="Position.Right" style="top: 75%" />
      <Handle id="bottom-left" :position="Position.Bottom" style="left: 25%" />
      <Handle id="bottom" :position="Position.Bottom" />
      <Handle id="bottom-right" :position="Position.Bottom" style="left: 75%" />
    </template>
  </button>
  <NodeToolbar v-if="data.description || data.url" class="max-w-md border border-border bg-popover p-3 text-xs text-popover-foreground shadow-sm"
    :is-visible position="top" @mouseenter="isOnToolbar = true" @mouseleave="handleToolbarLeave"
    @focusin="isOnToolbar = true" @focusout="handleToolbarLeave">
    <strong v-if="typeof data.label === 'string'" class="block">{{ data.label }}</strong>
    {{
      data.description }}
    <a v-if="data.url" rel="noopener noreferrer" :href="data.url" target="_blank" class="text-brand underline"
      @click.stop @mouseenter="isOnToolbar = true" @focus="isOnToolbar = true">Nuoroda</a>
  </NodeToolbar>
</template>

<script setup>
import { Handle, Position } from '@vue-flow/core';
import { NodeToolbar } from '@vue-flow/node-toolbar';
import { ref } from 'vue';

defineProps(['id', 'sourcePosition', 'targetPosition', 'data']);

const isVisible = ref(false);
const isOnToolbar = ref(false);

const handleOpen = () => {
  setTimeout(() => {
    isVisible.value = true;
  }, 100);
};

const handleClose = () => {
  setTimeout(() => {
    if (!isOnToolbar.value) {
      isVisible.value = false;
    }
  }, 200);
};

const handleToolbarLeave = () => {
  isOnToolbar.value = false;
  handleClose();
};
</script>
