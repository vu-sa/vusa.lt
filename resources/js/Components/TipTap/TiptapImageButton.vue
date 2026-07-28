<template>
  <!-- Default: a real button, so "Pasirinkti paveikslėlį" reads as a button rather
       than bare clickable text. Callers that already wrap their own trigger (e.g. a
       toolbar icon button) pass as-child to fall back to the old bare-div behavior. -->
  <Button v-if="!asChild" type="button" :variant :size @click="handleModalOpen">
    <IFluentImage24Regular class="h-4 w-4" />
    <slot>{{ $t('rich-content.select_image') }}</slot>
  </Button>
  <div v-else @click="handleModalOpen">
    <slot />
  </div>
  <!-- Emits both legacy string URL and full object -->
  <ImageSelector v-model:show-modal="showModal" :selection-type @submit="onImageSubmit" />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ImageSelector from './ImageSelector.vue';

import { Button, type ButtonVariants } from '@/Components/ui/button';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

withDefaults(defineProps<{
  /** Render only the slot content as the click target (caller supplies its own button). */
  asChild?: boolean;
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
  selectionType?: 'image' | 'video';
}>(), {
  asChild: false,
  variant: 'outline',
  size: 'sm',
});

const emit = defineEmits<{
  /** Legacy event: emits only the image URL (string) for existing consumers expecting a string */
  (e: 'submit', imageUrl: string): void;
  /** New event carrying full accessible image data */
  (e: 'submit:object', imageData: { src: string; alt: string; title: string }): void;
}>();

const showModal = ref(false);

async function handleModalOpen() {
  showModal.value = true;
}

function onImageSubmit(imageData: { src: string; alt: string; title: string }) {
  // Emit full object for new consumers
  emit('submit:object', imageData);
  // Emit just the src for backward compatibility
  emit('submit', imageData.src);
}
</script>
