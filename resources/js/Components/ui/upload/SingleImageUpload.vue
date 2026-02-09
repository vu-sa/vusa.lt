<template>
  <Upload
    ref="uploadRef"
    :max="1"
    accept="image/jpg,image/jpeg,image/png"
    :max-size="10 * 1024 * 1024"
    :action="route('files.uploadImage')"
    :data="{ path: folder }"
    list-type="image-card"
    :class="cn('w-full', props.class)"
    @upload:start="handleUploadStart"
    @upload:success="handleUploadSuccess"
    @upload:error="handleUploadError"
    @remove="handleRemove"
  >
    <template #default="{ files, canUpload, openFileDialog, removeFile }">
      <!-- Show existing/uploaded image -->
      <div v-if="files.length > 0" class="flex flex-wrap gap-3">
        <div
          v-for="file in files"
          :key="file.id"
          class="group relative aspect-video w-full max-w-xs overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
        >
          <img
            v-if="file.url"
            :src="file.url"
            :alt="file.name"
            class="h-full w-full object-cover"
          >

          <!-- Loading overlay -->
          <div
            v-if="file.status === 'uploading'"
            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/50"
          >
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-white border-t-transparent" />
            <span class="text-sm text-white">{{ file.progress }}%</span>
          </div>

          <!-- Actions overlay -->
          <div
            v-if="file.status === 'success'"
            class="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 opacity-0 transition-all group-hover:bg-black/40 group-hover:opacity-100"
          >
            <Button
              type="button"
              variant="secondary"
              size="sm"
              @click="openFileDialog"
            >
              <IFluentArrowSync16Regular class="mr-1.5 h-4 w-4" />
              {{ $t('Pakeisti') }}
            </Button>
            <Button
              type="button"
              variant="destructive"
              size="sm"
              @click="removeFile(file)"
            >
              <IFluentDelete16Regular class="h-4 w-4" />
            </Button>
          </div>

          <!-- Success indicator -->
          <div
            v-if="file.status === 'success'"
            class="absolute bottom-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white shadow-md"
          >
            <IFluentCheckmark12Regular class="h-3.5 w-3.5" />
          </div>

          <!-- Error state -->
          <div
            v-if="file.status === 'error'"
            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-red-500/90 p-4 text-center text-white"
          >
            <IFluentDismissCircle16Regular class="h-6 w-6" />
            <span class="text-sm">{{ file.error || $t('Įvyko klaida') }}</span>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              @click="removeFile(file)"
            >
              {{ $t('Bandyti dar kartą') }}
            </Button>
          </div>
        </div>
      </div>

      <!-- Empty state / Drop zone -->
      <UploadDropzone
        v-else
        size="default"
        class="w-full max-w-xs"
      >
        <template #default="{ isDragging }">
          <div class="flex flex-col items-center gap-3 text-center">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-full transition-colors"
              :class="isDragging ? 'bg-vusa-red/20 text-vusa-red' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800'"
            >
              <IFluentImage24Regular class="h-6 w-6" />
            </div>
            <div>
              <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ isDragging ? $t('Paleiskite failą') : $t('Įkelti nuotrauką') }}
              </p>
              <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $t('Vilkite arba spustelėkite') }}
              </p>
            </div>
            <p class="text-xs text-zinc-400">
              JPG, PNG • Max 10MB
            </p>
          </div>
        </template>
      </UploadDropzone>
    </template>
  </Upload>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { cn } from '@/Utils/Shadcn/utils';
import { Upload, UploadDropzone, UploadPreview, type UploadFile } from '@/Components/ui/upload';
import { Button } from '@/Components/ui/button';
import { useToasts } from '@/Composables/useToasts';

interface Props {
  /** Folder path for image storage */
  folder: string;
  /** Custom class for the container */
  class?: string;
}

const props = defineProps<Props>();
const url = defineModel<string | null>('url');

const page = usePage();
const toasts = useToasts();
const uploadRef = ref<InstanceType<typeof Upload> | null>(null);
const isUploading = ref(false);

// Initialize with existing URL if present
watch(() => url.value, (newUrl) => {
  if (newUrl && uploadRef.value) {
    const existingFile: UploadFile = {
      id: 'existing-1',
      name: 'image.jpg',
      size: 0,
      type: 'image/jpeg',
      url: newUrl,
      status: 'success',
      progress: 100,
    };
    uploadRef.value.setFiles([existingFile]);
  }
}, { immediate: true });

function handleUploadSuccess(file: UploadFile, response: any) {
  url.value = response.url;
  isUploading.value = false;
}

function handleUploadError(file: UploadFile, error: string) {
  toasts.error(error || 'Įvyko klaida įkeliant nuotrauką');
  isUploading.value = false;
}

function handleUploadStart() {
  isUploading.value = true;
}

function handleRemove() {
  url.value = null;
}
</script>
