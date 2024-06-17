<template>
  <NButton :size="size ?? 'small'" v-bind="$attrs" @click="handleModalOpen">
    <slot />
    <template #icon>
      <NIcon :component="Video20Regular" />
    </template>
  </NButton>
  <ImageSelector v-model:show-modal="showModal" selection-type="video" @submit="$emit('submit', $event)" />
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { Video20Regular } from "@vicons/fluent";
import { ref } from "vue";

import type { Size } from "naive-ui/es/button/src/interface";

import ImageSelector from "./ImageSelector.vue";

defineProps<{
  size?: Size;
}>()

defineEmits<{
  (e: 'submit', url: string): void
}>()

const showModal = ref(false);

async function handleModalOpen() {
  showModal.value = true;
}
</script>
