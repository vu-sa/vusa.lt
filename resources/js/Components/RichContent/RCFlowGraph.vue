<template>
  <!-- Single root so RichContentParser's width/spacing :class has something to fall through to. -->
  <div>
    <FlowGraph :graph-key="element.json_content.preset ?? ''" />
  </div>
</template>

<script setup lang="ts">
import type { FlowGraph as FlowGraphPart } from '@/Types/contentParts';
import FlowGraph from '@/Features/StructureGraph/FlowGraph.vue';

defineProps<{
  element: FlowGraphPart;
  /** Declared to consume the fullscreen renderer's editable contract. Graph selection
   * lives in the block toolbar; the graph itself has no on-canvas fields. */
  editable?: boolean;
  /** @see editable */
  blockKey?: string;
  /** @see editable */
  activeInlineField?: string | null;
}>();
</script>
