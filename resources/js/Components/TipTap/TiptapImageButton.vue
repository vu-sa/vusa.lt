<template>
  <NButton :size="size ?? 'small'" v-bind="$attrs" @click="handleModalOpen">
    <slot />
    <template #icon>
      <IFluentImage20Regular />
    </template>
  </NButton>
  <ImageSelector v-model:show-modal="showModal" @submit="$emit('submit', $event)" />
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { Size } from "naive-ui/es/button/src/interface";

import ImageSelector from "./ImageSelector.vue";

defineProps<{
  size?: Size;
}>()

defineEmits<{
  (e: 'submit', imageData: { src: string; alt: string; title: string }): void
}>()

const showModal = ref(false);

async function handleModalOpen() {
  showModal.value = true;
}

</script>
