<template>
  <div
    :class="cn(
      uploadVariants({ variant: computedVariant, size }),
      'cursor-pointer select-none',
      upload.disabled && 'pointer-events-none opacity-50',
      props.class
    )"
    data-slot="upload-dropzone"
    role="button"
    tabindex="0"
    @click="upload.openFileDialog"
    @keydown.enter="upload.openFileDialog"
    @keydown.space.prevent="upload.openFileDialog"
    @dragenter="upload.handleDragEnter"
    @dragleave="upload.handleDragLeave"
    @dragover="upload.handleDragOver"
    @drop="upload.handleDrop"
  >
    <slot :is-dragging="upload.isDragging.value" :can-upload="upload.canUpload.value" />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { inject, computed } from 'vue';

import { uploadVariants, type UploadVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  class?: HTMLAttributes['class'];
  variant?: UploadVariants['variant'];
  size?: UploadVariants['size'];
}

const props = defineProps<Props>();

const upload = inject<{
  isDragging: { value: boolean };
  canUpload: { value: boolean };
  hasFiles: { value: boolean };
  disabled: boolean;
  handleDragEnter: (e: DragEvent) => void;
  handleDragLeave: (e: DragEvent) => void;
  handleDragOver: (e: DragEvent) => void;
  handleDrop: (e: DragEvent) => void;
  openFileDialog: () => void;
}>('upload')!;

const computedVariant = computed<UploadVariants['variant']>(() => {
  if (props.variant) return props.variant;
  if (upload.isDragging.value) return 'active';
  return 'default';
});
</script>
