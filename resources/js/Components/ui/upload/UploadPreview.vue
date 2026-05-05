<template>
  <div
    :class="cn(
      'group relative',
      isImageCard && 'aspect-square w-24 sm:w-28',
      !isImageCard && 'flex items-center gap-3 rounded-lg border bg-zinc-50 p-3 dark:bg-zinc-900',
      props.class
    )"
    data-slot="upload-preview"
  >
    <!-- Image Card Preview -->
    <template v-if="isImageCard">
      <div
        class="relative h-full w-full cursor-pointer overflow-hidden rounded-lg border bg-zinc-100 dark:bg-zinc-800"
        :class="statusClasses"
        @click="handlePreview"
      >
        <img
          v-if="file.url"
          :src="file.url"
          :alt="file.name"
          class="h-full w-full object-cover transition-transform group-hover:scale-105"
        >

        <!-- Loading overlay -->
        <div
          v-if="file.status === 'uploading'"
          class="absolute inset-0 flex items-center justify-center bg-black/40"
        >
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-white border-t-transparent" />
        </div>

        <!-- Success indicator -->
        <div
          v-if="file.status === 'success'"
          class="absolute bottom-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500 text-white"
        >
          <IFluentCheckmark12Regular class="h-3 w-3" />
        </div>

        <!-- Error indicator -->
        <div
          v-if="file.status === 'error'"
          class="absolute inset-0 flex flex-col items-center justify-center bg-red-500/90 p-2 text-center text-white"
        >
          <IFluentDismiss12Regular class="mb-1 h-4 w-4" />
          <span class="line-clamp-2 text-[10px]">{{ file.error || 'Įvyko klaida' }}</span>
        </div>
      </div>

      <!-- Remove button -->
      <Button
        v-if="showRemove"
        variant="destructive"
        size="icon-xs"
        class="absolute -right-1.5 -top-1.5 opacity-0 shadow-md transition-opacity group-hover:opacity-100"
        @click.stop="handleRemove"
      >
        <IFluentDismiss12Regular class="h-3 w-3" />
      </Button>

      <!-- Progress bar -->
      <div
        v-if="showProgress && file.status === 'uploading'"
        class="absolute bottom-0 left-0 right-0 h-1 overflow-hidden rounded-b-lg bg-zinc-200 dark:bg-zinc-700"
      >
        <div
          class="h-full bg-blue-500 transition-all"
          :style="{ width: `${file.progress}%` }"
        />
      </div>
    </template>

    <!-- Text/File Preview -->
    <template v-else>
      <!-- File icon -->
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
        <IFluentDocument24Regular v-if="!isImage" class="h-5 w-5 text-zinc-500" />
        <img
          v-else-if="file.url"
          :src="file.url"
          :alt="file.name"
          class="h-full w-full rounded-lg object-cover"
        >
      </div>

      <!-- File info -->
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
          {{ file.name }}
        </p>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          <span v-if="file.status === 'uploading'">{{ file.progress }}% • </span>
          <span v-else-if="file.status === 'error'" class="text-red-500">{{ file.error }} • </span>
          {{ formatFileSize(file.size) }}
        </p>
      </div>

      <!-- Progress bar for text type -->
      <div
        v-if="showProgress && file.status === 'uploading'"
        class="absolute bottom-0 left-0 right-0 h-0.5 overflow-hidden bg-zinc-200 dark:bg-zinc-700"
      >
        <div
          class="h-full bg-blue-500 transition-all"
          :style="{ width: `${file.progress}%` }"
        />
      </div>

      <!-- Remove button -->
      <Button
        v-if="showRemove"
        variant="ghost"
        size="icon-sm"
        class="shrink-0 text-zinc-400 hover:text-red-500"
        @click="handleRemove"
      >
        <IFluentDismiss16Regular class="h-4 w-4" />
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { inject, computed } from 'vue';

import type { UploadFile } from '.';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';

interface Props {
  file: UploadFile;
  class?: HTMLAttributes['class'];
  showRemove?: boolean;
  showProgress?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showRemove: true,
  showProgress: true,
});

const upload = inject<{
  listType: 'text' | 'image-card';
  removeFile: (file: UploadFile) => void;
  onPreview: (file: UploadFile) => void;
}>('upload')!;

const isImage = computed(() => props.file.type.startsWith('image/'));
const isImageCard = computed(() => upload.listType === 'image-card' && isImage.value);

const statusClasses = computed(() => {
  switch (props.file.status) {
    case 'uploading':
      return 'ring-2 ring-blue-500/50';
    case 'success':
      return 'ring-2 ring-green-500/50';
    case 'error':
      return 'ring-2 ring-red-500/50';
    default:
      return '';
  }
});

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function handleRemove() {
  upload.removeFile(props.file);
}

function handlePreview() {
  upload.onPreview(props.file);
}
</script>
