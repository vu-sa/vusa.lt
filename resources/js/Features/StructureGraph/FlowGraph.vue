<template>
  <div data-slot="flow-graph" :data-graph="entry?.key">
    <p v-if="!entry" class="border border-dashed border-border p-4 text-sm text-muted-foreground">
      {{ $t('Šios schemos nebėra.') }}
    </p>
    <div v-else-if="!flow" :class="['animate-pulse bg-muted', preview ? 'h-64' : 'h-96 sm:h-[468px]']" />
    <div v-else-if="preview" class="h-64">
      <StructureGraph readonly :nodes="flow.nodes" :edges="flow.edges" class="!mb-0" />
    </div>
    <StructureGraphWrapper v-else :nodes="flow.nodes" :edges="flow.edges" />
  </div>
</template>

<script setup lang="ts">
import type { Edge, Node } from '@vue-flow/core';
import { computed, shallowRef, watch } from 'vue';
import { getActiveLanguage } from 'laravel-vue-i18n';

import StructureGraph from './StructureGraph.vue';
import StructureGraphWrapper from './StructureGraphWrapper.vue';
import { findGraph } from './registry';
import { toFlow, type GraphArticle } from './toFlow';
import type { GraphDef } from './types';

const props = defineProps<{
  graphKey: string;
  /** The small still version shown inside an editor. */
  preview?: boolean;
  highlight?: string | null;
  articles?: Record<string, GraphArticle>;
}>();

const entry = computed(() => findGraph(props.graphKey));
const graph = shallowRef<GraphDef | null>(null);
const flow = computed<{ nodes: Node[]; edges: Edge[] } | null>(() =>
  graph.value ? toFlow(graph.value, getActiveLanguage() === 'en' ? 'en' : 'lt', { highlight: props.highlight, articles: props.articles }) : null);

watch(entry, async (current) => {
  graph.value = null;
  const loaded = await current?.load();
  if (loaded && entry.value === current) graph.value = loaded;
}, { immediate: true });
</script>
