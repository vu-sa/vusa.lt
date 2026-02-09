<template>
  <template v-if="element.json_content.preset === 'VusaStructure'">
    <VusaStructure />
  </template>
</template>

<script setup lang="ts">
import type { FlowGraph } from '@/Types/contentParts';
import VusaStructure from '@/Features/StructureGraph/presets/VusaStructure.vue';

defineProps<{
  element: FlowGraph;
}>();
</script>
