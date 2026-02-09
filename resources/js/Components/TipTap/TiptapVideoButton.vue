<template>
  <div
    role="button"
    tabindex="0"
    @click="handleModalOpen"
    @keydown.enter="handleModalOpen"
    @keydown.space="handleModalOpen"
  >
    <slot />
  </div>
  <ImageSelector v-model:show-modal="modalState" selection-type="video" @submit="handleVideoSubmit" />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

import ImageSelector from './ImageSelector.vue';

const props = defineProps<{
  showModal?: boolean;
}>();

const emit = defineEmits<{
  (e: 'submit', url: string): void;
  (e: 'update:showModal', value: boolean): void;
}>();

const internalModal = ref(false);

// Use external modal control if provided, otherwise use internal state
const modalState = computed({
  get: () => props.showModal !== undefined ? props.showModal : internalModal.value,
  set: (value) => {
    if (props.showModal !== undefined) {
      emit('update:showModal', value);
    }
    else {
      internalModal.value = value;
    }
  },
});

async function handleModalOpen() {
  if (props.showModal !== undefined) {
    emit('update:showModal', true);
  }
  else {
    internalModal.value = true;
  }
}

function handleVideoSubmit(videoData: { src: string; alt: string; title: string }) {
  // Extract just the URL for video submissions
  emit('submit', videoData.src);
}
</script>
