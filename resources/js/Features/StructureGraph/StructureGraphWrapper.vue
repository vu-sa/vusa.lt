<template>
  <div class="h-96 sm:h-[468px]">
    <StructureGraph :min-zoom="0.2" :nodes :edges show-fullscreen @show-dialog="handleModal" />
  </div>

  <dialog ref="dialogRef" class="w-full h-full bg-zinc-50 dark:bg-zinc-900 ml-4 mt-4">
    <div class="h-full">
      <StructureGraph v-if="showGraph" :min-zoom="0.2" :nodes :edges :animated show-close @close="dialogRef?.close()" />
    </div>
  </dialog>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from 'vue'
import StructureGraph from './StructureGraph.vue';

defineProps<{
  nodes: Record<string, any>[]
  edges: Record<string, any>[]
}>()

const dialogRef = useTemplateRef<HTMLDialogElement | null>('dialogRef')
const showGraph = ref(false)

function handleModal() {
  dialogRef.value?.showModal()
  showGraph.value = true
}

const animated = ref(true)
</script>
