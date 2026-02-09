<template>
  <Upload
    ref="uploadRef"
    :max
    :accept
    :max-size
    deferred
    list-type="image-card"
    :class="cn('w-full', props.class)"
    @update:files="handleFilesUpdate"
    @remove="handleRemove"
  >
    <template #default="{ files: uploadFiles, canUpload: canAdd, openFileDialog, removeFile }">
      <!-- Single Image Mode -->
      <template v-if="isSingle">
        <!-- Show existing/selected image -->
        <div
          v-if="hasContent"
          class="group relative aspect-video w-full max-w-xs overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
        >
          <img v-if="previewUrl" :src="previewUrl" alt="Preview" class="h-full w-full object-cover">

          <!-- Compression indicator -->
          <div
            v-if="isCompressing"
            class="absolute inset-0 flex items-center justify-center bg-black/50"
          >
            <div class="flex flex-col items-center gap-2 text-white">
              <IFluentSpinnerIos20Filled class="h-6 w-6 animate-spin" />
              <span class="text-sm">{{ $t("Optimizuojama...") }}</span>
            </div>
          </div>

          <!-- New file indicator -->
          <div
            v-if="isNewFile && !isCompressing"
            class="absolute bottom-2 left-2 flex items-center gap-1 rounded-full bg-blue-500 px-2 py-0.5 text-xs text-white shadow-md"
          >
            <IFluentArrowUpload16Regular class="h-3 w-3" />
            {{ $t("Naujas") }}
          </div>

          <!-- Actions overlay -->
          <div
            class="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 opacity-0 transition-all group-hover:bg-black/40 group-hover:opacity-100"
          >
            <!-- Crop button -->
            <Button
              v-if="cropper && previewUrl && localFiles[0]"
              type="button"
              variant="secondary"
              size="sm"
              @click="openCropper(localFiles[0] as UploadFile)"
            >
              <IFluentCrop24Regular class="mr-1.5 h-4 w-4" />
              {{ $t("Apkirpti") }}
            </Button>

            <!-- Replace button -->
            <Button type="button" variant="secondary" size="sm" @click="openFileDialog">
              <IFluentArrowSync16Regular class="mr-1.5 h-4 w-4" />
              {{ $t("Pakeisti") }}
            </Button>

            <!-- Delete button -->
            <Button
              v-if="localFiles[0]"
              type="button"
              variant="destructive"
              size="sm"
              @click="removeFile(localFiles[0] as UploadFile)"
            >
              <IFluentDelete16Regular class="h-4 w-4" />
            </Button>
          </div>

          <!-- Success indicator -->
          <div
            v-if="isNewFile && !isCompressing"
            class="absolute bottom-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white shadow-md"
          >
            <IFluentCheckmark12Regular class="h-3.5 w-3.5" />
          </div>
        </div>

        <!-- Empty state / Drop zone -->
        <UploadDropzone v-else size="default" class="w-full max-w-xs">
          <template #default="{ isDragging }">
            <div class="flex flex-col items-center gap-3 text-center">
              <div
                class="flex h-12 w-12 items-center justify-center rounded-full transition-colors"
                :class="
                  isDragging
                    ? 'bg-vusa-red/20 text-vusa-red'
                    : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800'
                "
              >
                <IFluentImage24Regular class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                  {{ isDragging ? $t("Paleiskite failą") : $t("Įkelti nuotrauką") }}
                </p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                  {{ $t("Vilkite arba spustelėkite") }}
                </p>
              </div>
              <p class="text-xs text-zinc-400">
                JPG, PNG, WebP • Max {{ Math.round(maxSize / 1024 / 1024) }}MB
              </p>
            </div>
          </template>
        </UploadDropzone>
      </template>

      <!-- Multiple Images Mode -->
      <template v-else>
        <div class="flex flex-wrap gap-4">
          <!-- Existing & selected images -->
          <div
            v-for="f in localFiles"
            :key="f.id"
            class="group relative aspect-square w-24 rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 sm:w-28"
          >
            <img v-if="f.url" :src="f.url" :alt="f.name" class="h-full w-full rounded-md object-cover">

            <!-- New file indicator -->
            <div
              v-if="f.file"
              class="absolute bottom-1 left-1 flex items-center gap-0.5 rounded-full bg-blue-500 px-1.5 py-0.5 text-[10px] text-white shadow-md"
            >
              <IFluentArrowUpload16Regular class="h-2.5 w-2.5" />
            </div>

            <!-- Crop button (on hover) -->
            <Button
              v-if="cropper && f.url"
              type="button"
              variant="secondary"
              size="icon-xs"
              class="absolute left-1 top-1 opacity-0 shadow-md transition-opacity group-hover:opacity-100"
              @click="openCropper(f)"
            >
              <IFluentCrop24Regular class="h-3 w-3" />
            </Button>

            <!-- Remove button -->
            <Button
              type="button"
              variant="destructive"
              size="icon-xs"
              class="absolute right-1 top-1 opacity-0 shadow-md transition-opacity group-hover:opacity-100"
              @click="handleRemove(f)"
            >
              <IFluentDismiss12Regular class="h-3 w-3" />
            </Button>
          </div>

          <!-- Add more button / Drop zone -->
          <UploadDropzone v-if="canUpload" size="card" class="w-24 sm:w-28">
            <template #default="{ isDragging }">
              <div class="flex flex-col items-center justify-center gap-1">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                  :class="
                    isDragging
                      ? 'bg-vusa-red/20 text-vusa-red'
                      : 'bg-zinc-200 text-zinc-400 dark:bg-zinc-700'
                  "
                >
                  <IFluentAdd20Regular class="h-5 w-5" />
                </div>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                  {{ isDragging ? $t("Paleisti") : $t("Pridėti") }}
                </span>
              </div>
            </template>
          </UploadDropzone>
        </div>
      </template>
    </template>
  </Upload>

  <!-- Cropper Modal -->
  <Dialog v-model:open="showCropperModal">
    <DialogContent class="max-w-5xl p-0 gap-0">
      <ImageCropper
        v-if="cropperImageUrl"
        :src="cropperImageUrl"
        @crop="handleCropFinish"
        @cancel="handleCropCancel"
      />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
/**
 * ImageUpload - A unified image upload component with cropping, compression, and deferred/immediate modes.
 *
 * Features:
 * - Single or multiple image uploads (controlled by `max` prop)
 * - Browser-side compression before upload/submit
 * - Optional image cropping via integrated cropper modal
 * - Deferred mode: Files stored locally for form submission (Spatie Media Library)
 * - Immediate mode: Files uploaded immediately to server and URL returned
 * - Existing image preview for edit forms
 */
import { computed, ref, watch, onMounted, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { cn } from '@/Utils/Shadcn/utils';
import { useImageCompression, type CompressionOptions, type CompressionResult } from '@/Composables/useImageCompression';
import { Upload, UploadDropzone, type UploadFile } from '@/Components/ui/upload';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { ImageCropper } from '@/Components/ui/cropper';

export interface ImageUploadProps {
  /** Maximum number of files allowed. Use 1 for single file upload (default: 1) */
  max?: number;
  /** Upload mode: 'deferred' stores files for form submit, 'immediate' uploads instantly */
  mode?: 'deferred' | 'immediate';
  /** Enable cropping functionality */
  cropper?: boolean;
  /** Enable browser-side compression (true for defaults, or pass custom options) */
  compress?: boolean | CompressionOptions;
  /** Folder path for immediate mode uploads */
  folder?: string;
  /** Existing image URL for single file edit mode */
  existingUrl?: string | null;
  /** Existing image URLs for multiple file edit mode */
  existingUrls?: Array<{ id: string | number; url: string; name?: string }>;
  /** Maximum file size in bytes (default: 10MB) */
  maxSize?: number;
  /** Custom class for the container */
  class?: string;
  /** Accepted file types */
  accept?: string;
}

const props = withDefaults(defineProps<ImageUploadProps>(), {
  max: 1,
  mode: 'deferred',
  cropper: false,
  compress: true,
  folder: 'uploads',
  existingUrl: null,
  existingUrls: () => [],
  maxSize: 10 * 1024 * 1024,
  accept: 'image/jpg,image/jpeg,image/png,image/webp',
});

const emit = defineEmits<{
  (e: 'update:file', file: File | null): void;
  (e: 'update:files', files: File[]): void;
  (e: 'update:url', url: string | null): void;
  (e: 'update:urls', urls: string[]): void;
  (e: 'compression', result: CompressionResult): void;
  (e: 'remove:existing', item: { id: string | number; url: string }): void;
}>();

// Models for two-way binding
// Note: For multiple files (files), we use emit directly instead of defineModel due to array reactivity issues
const file = defineModel<File | null>('file', { default: null });
const url = defineModel<string | null>('url', { default: null });
const urls = defineModel<string[]>('urls', { default: () => [] });

// Component refs
const uploadRef = ref<InstanceType<typeof Upload> | null>(null);

// Local state
const localFiles = ref<UploadFile[]>([]);
const isCompressing = ref(false);
const compressionProgress = ref<Map<string, number>>(new Map());

// Cropper modal state
const showCropperModal = ref(false);
const cropperImageUrl = ref<string | null>(null);
const cropperFileId = ref<string | null>(null);

// Compression composable
const { compressImage, formatFileSize } = useImageCompression();

// Computed
const isSingle = computed(() => props.max === 1);
const isImmediate = computed(() => props.mode === 'immediate');
const hasExistingImages = computed(() => {
  if (isSingle.value) {
    return !!props.existingUrl || !!url.value;
  }
  return props.existingUrls.length > 0 || urls.value.length > 0;
});

// Compression options
const compressionOptions = computed<CompressionOptions>(() => {
  if (typeof props.compress === 'object') {
    return props.compress;
  }
  return {
    maxSizeMB: 2,
    maxWidthOrHeight: 1600,
    fileType: 'image/webp',
    quality: 0.8,
  };
});

// Initialize with existing URLs (from props or v-model)
function initializeFromExistingUrls() {
  // For single mode, check both existingUrl prop and url model
  const existingUrl = props.existingUrl || url.value;

  if (isSingle.value && existingUrl) {
    localFiles.value = [
      {
        id: 'existing-1',
        name: 'image.jpg',
        size: 0,
        type: 'image/jpeg',
        url: existingUrl,
        status: 'success',
        progress: 100,
      },
    ];
  }
  else if (!isSingle.value && props.existingUrls.length > 0) {
    // Filter out existing entries that were already added (to avoid duplicates)
    const existingIds = new Set(localFiles.value.filter(f => f.id.startsWith('existing-')).map(f => f.id));
    const newExisting = props.existingUrls.filter(img => !existingIds.has(`existing-${img.id}`));

    if (localFiles.value.length === 0 || newExisting.length > 0) {
      localFiles.value = props.existingUrls.map(img => ({
        id: `existing-${img.id}`,
        name: img.name || 'image.jpg',
        size: 0,
        type: 'image/jpeg',
        url: img.url,
        status: 'success' as const,
        progress: 100,
      }));
    }
  }

  if (uploadRef.value && localFiles.value.length > 0) {
    nextTick(() => {
      uploadRef.value?.setFiles(localFiles.value);
    });
  }
}

onMounted(() => {
  initializeFromExistingUrls();
});

// Watch for changes in existingUrls (when data loads asynchronously)
watch(
  () => props.existingUrls,
  (newUrls) => {
    if (newUrls && newUrls.length > 0 && localFiles.value.filter(f => f.id.startsWith('existing-')).length === 0) {
      initializeFromExistingUrls();
    }
  },
  { deep: true },
);

// Watch for changes in existingUrl (single mode)
watch(
  () => props.existingUrl,
  (newUrl) => {
    if (isSingle.value && newUrl && localFiles.value.length === 0) {
      initializeFromExistingUrls();
    }
  },
);

// Watch for changes in url model (single mode, v-model:url)
watch(
  url,
  (newUrl) => {
    if (isSingle.value && newUrl && localFiles.value.length === 0) {
      initializeFromExistingUrls();
    }
  },
);

// Process file with compression
async function processFile(rawFile: File): Promise<File> {
  if (!props.compress) {
    return rawFile;
  }

  isCompressing.value = true;
  try {
    const result = await compressImage(rawFile, compressionOptions.value);
    emit('compression', result);
    return result.file;
  }
  finally {
    isCompressing.value = false;
  }
}

// Handle files update from Upload component
async function handleFilesUpdate(newFiles: UploadFile[]) {
  // Find newly added files (have file object and aren't processed yet)
  const filesToProcess = newFiles.filter(
    f => f.file && !f.id.startsWith('processed-') && !f.id.startsWith('existing-'),
  );

  // Process new files with compression
  for (const uploadFile of filesToProcess) {
    if (!uploadFile.file) continue;

    const originalFile = uploadFile.file;
    const processedFile = await processFile(originalFile);

    // Update the file reference
    uploadFile.file = processedFile;
    uploadFile.id = `processed-${uploadFile.id}`;

    // For immediate mode, upload right away
    if (isImmediate.value) {
      await uploadFileToServer(uploadFile);
    }
  }

  localFiles.value = newFiles;
  updateModels();
}

// Upload file to server (immediate mode)
async function uploadFileToServer(uploadFile: UploadFile) {
  if (!uploadFile.file) return;

  uploadFile.status = 'uploading';
  uploadFile.progress = 0;

  const formData = new FormData();
  formData.append('image', uploadFile.file);
  formData.append('path', props.folder);
  formData.append('name', uploadFile.name);

  try {
    const response = await fetch(route('files.uploadImage'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': usePage().props.csrf_token as string,
        'Accept': 'application/json',
      },
      body: formData,
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Upload failed: ${response.status}`);
    }

    const data = await response.json();

    uploadFile.url = data.url;
    uploadFile.status = 'success';
    uploadFile.progress = 100;

    updateModels();
  }
  catch (error) {
    console.error('[ImageUpload] Upload error:', error);
    uploadFile.status = 'error';
    uploadFile.error = error instanceof Error ? error.message : 'Upload failed';
  }
}

// Update v-model values
function updateModels() {
  if (isSingle.value) {
    // Single file mode
    const currentFile = localFiles.value.find(f => f.file);
    file.value = currentFile?.file ?? null;
    emit('update:file', file.value);

    if (isImmediate.value) {
      const successFile = localFiles.value.find(f => f.status === 'success');
      // Use local variable and emit directly to avoid defineModel reactivity issues
      const newUrl = successFile?.url ?? null;
      emit('update:url', newUrl);
    }
  }
  else {
    // Multiple files mode - use emit directly instead of defineModel for arrays (reactivity issues)
    const filesWithFile = localFiles.value.filter(f => f.file);
    const newFilesArray = filesWithFile.map(f => f.file!);
    emit('update:files', newFilesArray);

    if (isImmediate.value) {
      urls.value = localFiles.value.filter(f => f.status === 'success' && f.url).map(f => f.url!);
      emit('update:urls', urls.value);
    }
  }
}

// Handle file removal
function handleRemove(removedFile: UploadFile) {
  // Check if this is an existing file
  if (removedFile.id.startsWith('existing-')) {
    const existingId = removedFile.id.replace('existing-', '');
    const existingItem = props.existingUrls.find(e => String(e.id) === existingId);
    if (existingItem) {
      emit('remove:existing', existingItem);
    }
    else if (isSingle.value) {
      emit('remove:existing', { id: existingId, url: removedFile.url || '' });
    }
  }

  localFiles.value = localFiles.value.filter(f => f.id !== removedFile.id);
  updateModels();
}

// Open cropper modal
function openCropper(uploadFile: UploadFile) {
  if (!props.cropper || !uploadFile.url) return;

  cropperImageUrl.value = uploadFile.url;
  cropperFileId.value = uploadFile.id;
  showCropperModal.value = true;
}

// Handle crop finish
async function handleCropFinish(data: { dataUrl: string; blob: Blob }) {
  showCropperModal.value = false;

  // Find the file being cropped
  const fileIndex = localFiles.value.findIndex(f => f.id === cropperFileId.value);
  if (fileIndex === -1) return;

  const uploadFile = localFiles.value[fileIndex];
  if (!uploadFile) return;

  // Create File from blob
  const fileName = uploadFile.name.replace(/\.[^.]+$/, '.webp');
  const croppedFile = new File([data.blob], fileName, { type: 'image/webp' });

  // Process with compression
  const processedFile = await processFile(croppedFile);

  // Update the file
  uploadFile.file = processedFile;
  uploadFile.url = data.dataUrl;
  uploadFile.name = fileName;

  // Re-upload in immediate mode
  if (isImmediate.value) {
    await uploadFileToServer(uploadFile as UploadFile);
  }

  updateModels();
  cropperImageUrl.value = null;
  cropperFileId.value = null;
}

function handleCropCancel() {
  showCropperModal.value = false;
  cropperImageUrl.value = null;
  cropperFileId.value = null;
}

// Check if we have displayable content
const hasContent = computed(() => localFiles.value.length > 0);
const previewUrl = computed(() => localFiles.value[0]?.url ?? null);
const isNewFile = computed(() => !!localFiles.value[0]?.file);
const canUpload = computed(() => localFiles.value.length < props.max);
</script>
