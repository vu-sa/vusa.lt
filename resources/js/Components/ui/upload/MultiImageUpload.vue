<template>
  <Upload
    ref="uploadRef"
    accept="image/jpg,image/jpeg,image/png"
    :max-size="5 * 1024 * 1024"
    :action="route('files.uploadImage')"
    :data="{ path: folder }"
    list-type="image-card"
    :class="cn('w-full', props.class)"
    @update:files="handleFilesUpdate"
    @upload:success="handleUploadSuccess"
    @upload:error="handleUploadError"
    @remove="handleRemove"
  >
    <template #default="{ canUpload, openFileDialog }">
      <div class="flex flex-wrap gap-4">
        <!-- Existing & uploaded images -->
        <div
          v-for="file in localFiles"
          :key="file.id"
          class="group relative aspect-square w-24 rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 sm:w-28"
        >
          <img
            v-if="file.url"
            :src="file.url"
            :alt="file.name"
            class="h-full w-full rounded-md object-cover transition-transform group-hover:scale-105"
          >

          <!-- Loading overlay -->
          <div
            v-if="file.status === 'uploading'"
            class="absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-md bg-black/50"
          >
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-white border-t-transparent" />
            <span class="text-xs text-white">{{ file.progress }}%</span>
          </div>

          <!-- Remove button - positioned inside with proper spacing -->
          <Button
            v-if="file.status === 'success'"
            type="button"
            variant="destructive"
            size="icon-xs"
            class="absolute right-1 top-1 opacity-0 shadow-md transition-opacity group-hover:opacity-100"
            @click="handleRemove(file)"
          >
            <IFluentDismiss12Regular class="h-3 w-3" />
          </Button>

          <!-- Error state -->
          <div
            v-if="file.status === 'error'"
            class="absolute inset-0 flex flex-col items-center justify-center bg-red-500/90 p-2 text-center text-white"
          >
            <IFluentDismiss12Regular class="mb-1 h-4 w-4" />
            <span class="line-clamp-2 text-[10px]">{{ file.error || 'Klaida' }}</span>
          </div>
        </div>

        <!-- Add more button / Drop zone -->
        <UploadDropzone
          v-if="canUpload"
          size="card"
          class="w-24 sm:w-28"
        >
          <template #default="{ isDragging }">
            <div class="flex flex-col items-center justify-center gap-1">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                :class="isDragging ? 'bg-vusa-red/20 text-vusa-red' : 'bg-zinc-200 text-zinc-400 dark:bg-zinc-700'"
              >
                <IFluentAdd20Regular class="h-5 w-5" />
              </div>
              <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ isDragging ? $t('Paleisti') : $t('Pridėti') }}
              </span>
            </div>
          </template>
        </UploadDropzone>
      </div>
    </template>
  </Upload>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

import { cn } from '@/Utils/Shadcn/utils';
import { Upload, UploadDropzone, type UploadFile } from '@/Components/ui/upload';
import { Button } from '@/Components/ui/button';
import { useToasts } from '@/Composables/useToasts';

interface ExistingImage {
  id: string | number;
  url?: string;
  original_url?: string;
  name?: string;
  status?: string;
}

interface Props {
  /** Folder path for image storage */
  folder: string;
  /** Route name for deleting existing media */
  deleteRoute?: string;
  /** Model ID for delete route */
  modelId?: number | string;
  /** Custom class for the container */
  class?: string;
}

const props = defineProps<Props>();
const images = defineModel<ExistingImage[]>('images', { default: () => [] });

const toasts = useToasts();
const uploadRef = ref<InstanceType<typeof Upload> | null>(null);
const localFiles = ref<UploadFile[]>([]);

// Convert existing images to UploadFile format on mount
onMounted(() => {
  if (images.value && images.value.length > 0) {
    localFiles.value = images.value.map((img, index) => ({
      id: String(img.id || `existing-${index}`),
      name: img.name || `image-${index}.jpg`,
      size: 0,
      type: 'image/jpeg',
      url: img.original_url || img.url || '',
      status: 'success' as const,
      progress: 100,
    }));
  }
});

// Sync local files back to images model
watch(localFiles, (newFiles) => {
  images.value = newFiles.map(f => ({
    id: f.id,
    url: f.url,
    original_url: f.url,
    name: f.name,
    status: f.status === 'success' ? 'finished' : f.status,
  }));
}, { deep: true });

function handleUploadSuccess(file: UploadFile, response: any) {
  // Update the file's URL with the server response
  const index = localFiles.value.findIndex(f => f.id === file.id);
  if (index !== -1) {
    localFiles.value[index].url = response.url;
    localFiles.value[index].status = 'success';
  }
}

function handleUploadError(file: UploadFile, error: string) {
  toasts.error(error || 'Įvyko klaida įkeliant nuotrauką');
}

function handleFilesUpdate(files: UploadFile[]) {
  localFiles.value = files;
}

function handleRemove(file: UploadFile) {
  // If it's an existing file (not pending), call delete route
  if (file.status === 'success' && props.deleteRoute && props.modelId && !file.id.startsWith('upload-')) {
    router.post(
      route(props.deleteRoute, {
        calendar: props.modelId,
        media: file.id,
      }),
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          localFiles.value = localFiles.value.filter(f => f.id !== file.id);
        },
      },
    );
  }
  else {
    localFiles.value = localFiles.value.filter(f => f.id !== file.id);
  }
}
</script>
