<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.flow_graph_preset') }}</FieldLabel>
      <FlowGraphChoice v-model="selected" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { FlowGraph } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import FlowGraphChoice from '@/Features/StructureGraph/FlowGraphChoice.vue';
import { findGraph } from '@/Features/StructureGraph/registry';

const modelValue = defineModel<FlowGraph['json_content']>({ required: true });

const selected = computed({
  // Older blocks stored the preset name; show them as the graph they mean.
  get: () => findGraph(modelValue.value.preset)?.key ?? '',
  set: (preset: string) => { modelValue.value = { ...modelValue.value, preset }; },
});
</script>
