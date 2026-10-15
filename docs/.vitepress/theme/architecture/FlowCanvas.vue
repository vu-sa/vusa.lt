<template>
  <div class="af-canvas" :style="{ '--af-height': `${height}px` }">
    <VueFlow
      :nodes
      :edges
      fit-view-on-init
      :nodes-draggable="false"
      :nodes-connectable="false"
      :zoom-on-scroll="false"
      :prevent-scrolling="false"
      :min-zoom="0.3"
    >
      <template #node-step="{ data, selected }">
        <FlowStepNode :data :selected />
      </template>
      <Controls :show-interactive="false" position="bottom-right" />
    </VueFlow>
  </div>
</template>

<script setup lang="ts">
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'

import { MarkerType, VueFlow, type Edge, type Node } from '@vue-flow/core'
import { Controls } from '@vue-flow/controls'
import { computed } from 'vue'
import FlowStepNode, { type StepData } from './FlowStepNode.vue'
import type { Flow, Locale } from './flows'

const props = defineProps<{
  flow: Flow
  locale: Locale
}>()

const bottom = computed(() => Math.max(...props.flow.nodes.map(node => node.position.y)))

const nodes = computed<Node<StepData>[]>(() => props.flow.nodes.map(node => ({
  id: node.id,
  type: 'step',
  position: node.position,
  class: ['af-node', `af-node--${node.kind}`],
  ariaLabel: node.label[props.locale],
  data: {
    label: breakable(node.label[props.locale]),
    detail: node.detail[props.locale],
    file: node.file,
    // The canvas clips its content, so steps in the lower part open their tooltip upwards.
    placement: node.position.y > bottom.value * 0.6 ? 'above' : 'below',
  },
})))

// Identifiers like `StoreCalendarRequest::authorize()` have no spaces; let them wrap after `::` and `/`.
function breakable(label: string): string {
  return label.replace(/(::|\/)/g, '$1\u200B')
}

// Tall flows would otherwise be shrunk to fit a fixed box and their labels become unreadable.
const height = computed(() => Math.min(Math.max(bottom.value * 0.8 + 120, 420), 1000))

const edges = computed<Edge[]>(() => props.flow.edges.map(edge => ({
  id: `${edge.source}-${edge.target}`,
  source: edge.source,
  target: edge.target,
  label: edge.label?.[props.locale],
  type: 'smoothstep',
  animated: edge.branch === 'async',
  class: edge.branch ? `af-edge--${edge.branch}` : undefined,
  markerEnd: MarkerType.ArrowClosed,
})))
</script>

<style>
.af-canvas {
  height: var(--af-height);
  border: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-soft);
}

@media (max-width: 640px) {
  .af-canvas {
    height: 440px;
  }
}

.af-canvas .vue-flow__node.af-node {
  width: 200px;
  padding: 8px 10px;
  border: 1px solid var(--vp-c-divider);
  border-left: 4px solid var(--af-kind);
  border-radius: 0;
  background: var(--vp-c-bg);
  color: var(--vp-c-text-1);
  font-size: 13px;
  line-height: 1.35;
  text-align: left;
  overflow-wrap: anywhere;
  cursor: pointer;
}

.af-canvas .vue-flow__node.af-node--async {
  border-style: dashed;
  border-left-style: solid;
}

.af-canvas .vue-flow__node.af-node.selected {
  box-shadow: 0 0 0 2px var(--af-kind);
}

/* Lift the hovered or selected step so its tooltip is not covered by the steps drawn after it. */
.af-canvas .vue-flow__node.af-node:hover,
.af-canvas .vue-flow__node.af-node.selected {
  z-index: 1000 !important;
}

.af-tip {
  display: none;
  position: absolute;
  left: -4px;
  width: 280px;
  padding: 10px 12px;
  border: 1px solid var(--vp-c-divider);
  border-left: 4px solid var(--af-kind);
  background: var(--vp-c-bg);
  box-shadow: var(--vp-shadow-3);
  color: var(--vp-c-text-1);
  font-size: 12px;
  line-height: 1.45;
  cursor: auto;
}

.af-tip--below {
  top: calc(100% + 6px);
}

.af-tip--above {
  bottom: calc(100% + 6px);
}

.af-tip::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  height: 8px;
}

.af-tip--below::before {
  bottom: 100%;
}

.af-tip--above::before {
  top: 100%;
}

.af-tip p {
  margin: 0 0 6px;
}

.af-tip a {
  word-break: break-all;
}

.af-canvas .vue-flow__node.af-node:hover .af-tip,
.af-tip--open {
  display: block;
}

.af-canvas .vue-flow__node.af-node:focus-visible {
  outline: 2px solid var(--vp-c-brand-1);
  outline-offset: 2px;
}

.af-canvas .vue-flow__handle {
  opacity: 0;
}

.af-canvas .vue-flow__edge-path {
  stroke: var(--vp-c-text-3);
}

.af-canvas .af-edge--error .vue-flow__edge-path {
  stroke: var(--vp-c-danger-1);
}

.af-canvas .vue-flow__edge-textbg {
  fill: var(--vp-c-bg-soft);
}

.af-canvas .vue-flow__edge-text {
  fill: var(--vp-c-text-2);
  font-size: 11px;
}

.af-canvas .vue-flow__controls-button {
  background: var(--vp-c-bg);
  border-color: var(--vp-c-divider);
  fill: var(--vp-c-text-1);
}
</style>
