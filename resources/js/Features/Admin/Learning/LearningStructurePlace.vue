<template>
  <div class="space-y-3 border border-border p-3" data-slot="structure-place" :data-testid="`structure-place-${index}`">
    <div class="flex items-start gap-2">
      <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-2">
        <NativeSelect
          v-model="graph"
          :aria-label="$t('learning_library.fields.structure_graph')"
          :options="graphOptions"
          :data-testid="`structure-graph-${index}`"
        />
        <NativeSelect
          v-model="node"
          :aria-label="$t('learning_library.fields.structure_node')"
          :placeholder-value="null"
          placeholder-selectable
          :placeholder="$t('learning_library.fields.structure_node_none')"
          :options="nodeOptions"
          :disabled="!definition"
          :error
          :data-testid="`structure-node-${index}`"
        />
      </div>
      <Button
        type="button"
        variant="ghost"
        size="icon"
        class="size-11 shrink-0 text-muted-foreground hover:text-destructive"
        :aria-label="$t('learning_library.fields.structure_remove')"
        :data-testid="`structure-remove-${index}`"
        @click="$emit('remove')"
      >
        <X class="size-4" aria-hidden="true" />
      </Button>
    </div>
    <p v-if="error" class="text-xs text-destructive">
      {{ error }}
    </p>
    <FlowGraph :graph-key="graph" :highlight="node" preview />
  </div>
</template>

<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import { X } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import FlowGraph from '@/Features/StructureGraph/FlowGraph.vue';
import { findGraph, graphRegistry } from '@/Features/StructureGraph/registry';
import type { GraphDef } from '@/Features/StructureGraph/types';

const props = defineProps<{
  index: number;
  locale: 'lt' | 'en';
  /** Graphs other rows already use; an article sits once per graph. */
  taken: string[];
  error?: string;
}>();

defineEmits<{ (e: 'remove'): void }>();

const graph = defineModel<string>('graph', { required: true });
const node = defineModel<string | null>('node', { required: true });

const definition = shallowRef<GraphDef | null>(null);

const graphOptions = computed(() => graphRegistry
  .filter(entry => entry.key === graph.value || !props.taken.includes(entry.key))
  .map(entry => ({ value: entry.key, label: entry.title[props.locale] })));
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
