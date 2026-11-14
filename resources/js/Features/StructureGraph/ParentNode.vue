<template>
  <button
    type="button"
    class="relative h-full w-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    :class="[data.nodeClass, data.highlighted && 'outline-2 outline-offset-4 outline-brand']"
    :aria-expanded="hasDetails ? showDetails : undefined"
    @focus="focused = true"
    @blur="focused = false"
  >
    <span v-if="typeof data.label === 'string'" :class="[data.textClass]" class="absolute inset-x-0 top-1.5 px-2 text-left text-[10px] leading-tight">{{ data.label }}</span>
    <component :is="data.label" v-else />
    <span v-if="data.article" aria-hidden="true" class="absolute right-1 top-1 size-1.5 bg-current opacity-70" data-testid="structure-node-linked" />
  </button>
  <template v-if="data.handles">
    <Handle v-for="handle in handlesFor(data.handles)" :id="handle.id" :key="handle.id" :position="handle.position" :style="handle.style" />
  </template>
  <template v-else>
    <Handle type="source" :position="sourcePosition ?? Position.Bottom" />
    <Handle type="target" :position="targetPosition ?? Position.Top" />
  </template>
  <NodeDetails v-if="hasDetails" :is-visible="showDetails" :data />
</template>

<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core';
import { computed, ref } from 'vue';

import NodeDetails from './NodeDetails.vue';
import { handlesFor, type StructureNodeData } from './handles';

const props = defineProps<{
  id: string;
  data: StructureNodeData;
  selected?: boolean;
  sourcePosition?: Position;
  targetPosition?: Position;
}>();

const focused = ref(false);
const hasDetails = computed(() => Boolean(props.data.description || props.data.url || props.data.article));
const showDetails = computed(() => Boolean(props.selected) || focused.value);
</script>
