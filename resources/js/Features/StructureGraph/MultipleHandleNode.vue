<template>
  <button
    type="button"
    class="relative flex h-full w-full items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    :class="[data.nodeClass, data.highlighted && 'outline-2 outline-offset-4 outline-brand']"
    :aria-expanded="hasDetails ? showDetails : undefined"
    @focus="focused = true"
    @blur="focused = false"
  >
    <span v-if="typeof data.label === 'string'" :class="[data.textClass]" class="mx-auto block w-fit px-2 py-1.5 text-[11px] leading-tight">{{ data.label }}</span>
    <component :is="data.label" v-else />
    <!-- Marks a node that opens a wiki article, so readers know it leads somewhere. -->
    <span v-if="data.article" aria-hidden="true" class="absolute right-1 top-1 size-1.5 bg-current opacity-70" data-testid="structure-node-linked" />

    <Handle v-for="handle in handles" :id="handle.id" :key="handle.id" :position="handle.position" :style="handle.style" />
  </button>
  <NodeDetails v-if="hasDetails" :is-visible="showDetails" :data />
</template>

<script setup lang="ts">
import { Handle } from '@vue-flow/core';
import { computed, ref } from 'vue';

import NodeDetails from './NodeDetails.vue';
import { handlesFor, type StructureNodeData } from './handles';

const props = defineProps<{
  id: string;
  data: StructureNodeData;
  selected?: boolean;
}>();

const focused = ref(false);
const hasDetails = computed(() => Boolean(props.data.description || props.data.url || props.data.article));
// Click (select) or keyboard focus opens the details, so nothing depends on hover.
const showDetails = computed(() => Boolean(props.selected) || focused.value);
const handles = computed(() => handlesFor(props.data.handles));
</script>
