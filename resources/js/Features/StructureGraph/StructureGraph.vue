<template>
  <VueFlow fit-view-on-init :nodes :edges default-marker-color="var(--muted-foreground)"
    class="basic-flow mb-4 border" :nodes-connectable="false" :nodes-draggable="!readonly"
    :elements-selectable="!readonly" :pan-on-drag="!readonly" :zoom-on-scroll="!readonly && scrollZoom"
    :zoom-on-pinch="!readonly" :zoom-on-double-click="!readonly" :prevent-scrolling="!readonly && scrollZoom" @init="onInit"
    @node-click="({ node }) => focusedId = node.id" @pane-click="focusedId = null">
    <template #node-multiple-handle="props">
      <MultipleHandleNode :id="props.id" :data="props.data" :selected="props.selected" />
    </template>
    <template #node-parent="props">
      <ParentNode :id="props.id" :data="props.data" :selected="props.selected" :source-position="props.sourcePosition"
        :target-position="props.targetPosition" />
    </template>
    <Controls v-if="!readonly" class="h-8" position="bottom-right">
      <ControlButton v-if="!isReducedMotion && !playAnimations" @click="playAnimations = true">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 32 32">
          <path
            d="M11.24 6.203A1.5 1.5 0 0 0 9 7.508V24.5a1.5 1.5 0 0 0 2.24 1.305l14.997-8.498a1.5 1.5 0 0 0 0-2.61zM7 7.508c0-2.681 2.891-4.367 5.225-3.046l14.997 8.493c2.367 1.34 2.368 4.75.001 6.09l-14.997 8.5C9.892 28.865 7 27.18 7 24.498z" />
        </svg>
      </ControlButton>
      <ControlButton v-else-if="!isReducedMotion" @click="playAnimations = false">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 20 20">
          <path
            d="M5 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM4 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zm9-2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm-1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z" />
        </svg>
      </ControlButton>
      <ControlButton v-if="showFullscreen" @click="$emit('showDialog')">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 512 512">
          <path stroke-linecap="square" stroke-miterlimit="10" stroke-width="32"
            d="M432 320v112H320m101.8-10.23L304 304M80 192V80h112M90.2 90.23L208 208M320 80h112v112M421.77 90.2L304 208M192 432H80V320m10.23 101.8L208 304" />
        </svg>
      </ControlButton>
      <ControlButton v-if="showClose" @click="$emit('close')">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
          <path
            d="M18.364 5.636a2 2 0 0 0-2.828 0L12 9.172 8.464 5.636a2 2 0 0 0-2.828 2.828L9.172 12l-3.536 3.536a2 2 0 1 0 2.828 2.828L12 14.828l3.536 3.536a2 2 0 0 0 2.828-2.828L14.828 12l3.536-3.536a2 2 0 0 0 0-2.828z" />
        </svg>
      </ControlButton>
    </Controls>
  </VueFlow>
</template>

<script setup lang="ts">
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '../../../css/vendor/vue-flow.css';

import { VueFlow, type Edge, type Node, type VueFlowStore } from '@vue-flow/core';
import { Controls, ControlButton } from '@vue-flow/controls';
import { ref, computed, shallowRef, watch } from 'vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

import { focusOn } from './focus';
import MultipleHandleNode from './MultipleHandleNode.vue';
import ParentNode from './ParentNode.vue';

const props = defineProps<{
  animated?: boolean;
  showFullscreen?: boolean;
  showClose?: boolean;
  /** A still picture: no panning, zooming, selecting or controls (the editor preview). */
  readonly?: boolean;
  /** Only fullscreen: inline, the wheel must keep scrolling the page past the graph. */
  scrollZoom?: boolean;
  nodes: Node[];
  edges: Edge[];
}>();

defineEmits<{
  (e: 'showDialog'): void;
  (e: 'close'): void;
}>();

// A clicked node fades everything unrelated, so its ties read without the rest of the graph.
const focusedId = ref<string | null>(null);
watch(() => props.nodes, () => { focusedId.value = null; });
const focus = computed(() => focusedId.value ? focusOn(focusedId.value, props.nodes, props.edges) : null);

const nodes = computed(() => props.nodes.map(node => ({
  ...node,
  class: ['vue-flow__node-default', focus.value && !focus.value.nodes.has(node.id) && 'is-faded'].filter(Boolean).join(' '),
})));

const { isReducedMotion } = useAccessibilityPreferences();
const flow = shallowRef<VueFlowStore>();
let zoomDuration = 250;

function applyMotion(): void {
  if (isReducedMotion.value) flow.value?.d3Selection.value?.interrupt();
  flow.value?.d3Zoom.value?.duration(isReducedMotion.value ? 0 : zoomDuration);
}

function onInit(instance: VueFlowStore): void {
  flow.value = instance;
  zoomDuration = instance.d3Zoom.value?.duration() ?? 250;
  applyMotion();
}

watch(isReducedMotion, applyMotion);
const playAnimations = ref(props.animated);

const edges = computed(() => props.edges.map(edge => ({
  ...edge,
  animated: Boolean(playAnimations.value && !isReducedMotion.value),
  class: [edge.class, focus.value && !focus.value.edges.has(edge.id) && 'is-faded'].filter(Boolean).join(' ') || undefined,
})));

</script>
