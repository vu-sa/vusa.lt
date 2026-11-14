<template>
  <FormFieldWrapper
    id="article-structure-graph"
    :label="$t('learning_library.fields.structure_graph')"
    :hint="$t('learning_library.fields.structure_hint')"
    :error="graphError || nodeError"
  >
    <div class="space-y-3" data-slot="structure-field">
      <div class="grid gap-3 sm:grid-cols-2">
        <NativeSelect
          id="article-structure-graph"
          v-model="graph"
          :placeholder-value="null"
          placeholder-selectable
          :placeholder="$t('learning_library.fields.structure_graph_none')"
          :options="graphOptions"
          data-testid="structure-graph"
        />
        <NativeSelect
          v-if="graph"
          id="article-structure-node"
          v-model="node"
          :aria-label="$t('learning_library.fields.structure_node')"
          :placeholder-value="null"
          placeholder-selectable
          :placeholder="$t('learning_library.fields.structure_node_none')"
          :options="nodeOptions"
          :disabled="!definition"
          data-testid="structure-node"
        />
      </div>
      <FlowGraph v-if="graph" :graph-key="graph" :highlight="node" preview />
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { NativeSelect } from '@/Components/ui/native-select';
import FlowGraph from '@/Features/StructureGraph/FlowGraph.vue';
import { findGraph, graphRegistry } from '@/Features/StructureGraph/registry';
import type { GraphDef } from '@/Features/StructureGraph/types';

const props = defineProps<{
  locale: 'lt' | 'en';
  graphError?: string;
  nodeError?: string;
}>();

const graph = defineModel<string | null>('graph', { required: true });
const node = defineModel<string | null>('node', { required: true });

const definition = shallowRef<GraphDef | null>(null);

const graphOptions = computed(() => graphRegistry.map(entry => ({ value: entry.key, label: entry.title[props.locale] })));
const nodeOptions = computed(() => (definition.value?.nodes ?? [])
  .map(item => ({ value: item.id, label: item.label[props.locale] || item.label.lt }))
  .filter(item => item.label !== '')
  .sort((a, b) => a.label.localeCompare(b.label, props.locale)));

watch(graph, async (key, previous) => {
  definition.value = null;
  // A node belongs to one graph, so another graph clears it.
  if (previous !== undefined && key !== previous) node.value = null;
  const loaded = await findGraph(key)?.load();
  if (loaded && graph.value === key) definition.value = loaded;
}, { immediate: true });
</script>
