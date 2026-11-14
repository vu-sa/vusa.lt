<template>
  <div class="h-96 sm:h-[468px]">
    <StructureGraph :min-zoom="0.2" :nodes :edges show-fullscreen @show-dialog="handleModal" />
  </div>

  <dialog ref="dialogRef" class="m-0 h-dvh max-h-none w-dvw max-w-none bg-background p-4 text-foreground backdrop:bg-black/50" @close="showGraph = false">
    <div class="h-full">
      <StructureGraph v-if="showGraph" :min-zoom="0.2" :nodes :edges :animated scroll-zoom show-close @close="dialogRef?.close()" />
    </div>
  </dialog>
</template>

<script setup lang="ts">
import type { Edge, Node } from '@vue-flow/core';
import { ref, useTemplateRef } from 'vue';

import StructureGraph from './StructureGraph.vue';

defineProps<{
  nodes: Node[];
  edges: Edge[];
}>();

const dialogRef = useTemplateRef<HTMLDialogElement | null>('dialogRef');
const showGraph = ref(false);

function handleModal() {
  dialogRef.value?.showModal();
  showGraph.value = true;
}

const animated = ref(true);
</script>
