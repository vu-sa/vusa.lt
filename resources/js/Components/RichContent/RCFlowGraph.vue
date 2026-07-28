<template>
  <!-- Single root so RichContentParser's width/spacing :class has something to fall through to. -->
  <div>
    <VusaStructure v-if="element.json_content.preset === 'VusaStructure'" />
  </div>
</template>

<script setup lang="ts">
import type { FlowGraph } from '@/Types/contentParts';
import VusaStructure from '@/Features/StructureGraph/presets/VusaStructure.vue';

defineProps<{
  element: FlowGraph;
}>();
</script>
