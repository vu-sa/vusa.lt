<template>
  <NButton :size="size ?? 'small'" v-bind="$attrs" @click="handleModalOpen">
    <slot />
    <template #icon>
      <IFluentImage20Regular />
    </template>
  </NButton>
  <!-- Emits both legacy string URL and full object -->
  <ImageSelector v-model:show-modal="showModal" @submit="onImageSubmit" />
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { Size } from "naive-ui/es/button/src/interface";

import ImageSelector from "./ImageSelector.vue";

defineProps<{
  size?: Size;
}>()

const emit = defineEmits<{
  /** Legacy event: emits only the image URL (string) for existing consumers expecting a string */
  (e: 'submit', imageUrl: string): void
  /** New event carrying full accessible image data */
  (e: 'submit:object', imageData: { src: string; alt: string; title: string }): void
}>()

const showModal = ref(false);

async function handleModalOpen() {
  showModal.value = true;
}

function onImageSubmit(imageData: { src: string; alt: string; title: string }) {
  // Emit full object for new consumers
  emit('submit:object', imageData)
  // Emit just the src for backward compatibility
  emit('submit', imageData.src)
}

</script>
