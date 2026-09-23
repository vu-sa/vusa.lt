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
    <template #default="{ openFileDialog, removeFile }">
      <!-- Single Image Mode -->
      <template v-if="isSingle">
        <!-- Show existing/selected image -->
        <div
          v-if="hasContent"
          :class="[
            'group relative w-full overflow-hidden border border-border bg-secondary/30',
            previewAspect === '4/3'
              ? (fullWidth ? 'aspect-[4/3] w-full' : 'aspect-[4/3] max-w-sm')
              : (fullWidth ? 'aspect-video w-full' : 'aspect-video max-w-xs'),
          ]"
        >
          <img
            v-if="previewUrl"
            :src="previewUrl"
            alt="Preview"
            class="h-full w-full object-cover"
            :style="focalPoint ? { objectPosition: previewObjectPosition } : undefined"
          >

          <!-- Compression indicator -->
          <div
            v-if="isCompressing"
            class="absolute inset-0 flex items-center justify-center bg-black/50"
          >
            <div class="flex flex-col items-center gap-2 text-white">
              <Loader2 class="size-6 animate-spin" />
              <span class="text-sm">{{ $t("Optimizuojama...") }}</span>
            </div>
          </div>

          <!-- New file indicator -->
          <div
            v-if="isNewFile && !isCompressing"
            class="absolute bottom-2 left-2 flex items-center gap-1 border border-border bg-background px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-foreground shadow-xs"
          >
            <UploadIcon class="size-3" />
            {{ $t("Naujas") }}
          </div>

          <!-- Actions overlay -->
          <div
            :class="[
              'absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-ink/70 p-2',
              'opacity-100 transition-opacity sm:inset-0 sm:bg-ink/40 sm:opacity-0',
              'sm:group-hover:opacity-100 sm:group-focus-within:opacity-100 pointer-coarse:opacity-100',
            ]"
          >
            <!-- Crop button -->
            <Button
              v-if="cropper && previewUrl && localFiles[0]"
              type="button"
              variant="secondary"
              size="sm"
              class="border border-border bg-background/90 text-foreground backdrop-blur"
              @click="openCropper(localFiles[0] as UploadFile)"
            >
              <Crop class="mr-1.5 size-4" />
              {{ $t("Apkirpti") }}
            </Button>

            <!-- Replace button -->
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="border border-border bg-background/90 text-foreground backdrop-blur"
              @click="openFileDialog"
            >
              <RefreshCw class="mr-1.5 size-4" />
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
              <Trash2 class="size-4" />
            </Button>
          </div>

          <!-- Success indicator -->
          <div
            v-if="isNewFile && !isCompressing"
            class="absolute bottom-2 right-2 flex size-6 items-center justify-center border border-border bg-background text-[var(--status-success)] shadow-xs"
          >
            <Check class="size-3.5" />
          </div>
        </div>

        <!-- Focal point button -->
        <Button
          v-if="focalPoint && hasContent && previewUrl"
          type="button"
          variant="outline"
          size="sm"
          class="mt-2"
          @click="showFocalPointModal = true"
        >
          <Crosshair class="mr-1.5 size-4" />
          {{ $t("Nustatyti fokuso tašką") }}
          <span v-if="focalPointValue" class="ml-1.5 font-mono text-[10px] text-muted-foreground">
            {{ focalPointValue }}
          </span>
        </Button>

        <!-- Empty state / Drop zone -->
        <UploadDropzone
          v-if="!hasContent"
          size="default"
          :class="previewAspect === '4/3'
            ? (fullWidth ? 'w-full aspect-[4/3]' : 'w-full max-w-sm')
            : (fullWidth ? 'w-full aspect-video' : 'w-full max-w-xs')"
        >
          <template #default="{ isDragging }">
            <div class="flex flex-col items-center gap-3 text-center">
              <div
                class="flex size-12 items-center justify-center border border-border transition-colors"
                :class="
                  isDragging
                    ? 'border-brand bg-brand/10 text-brand'
                    : 'bg-background text-muted-foreground'
                "
              >
                <ImagePlus class="size-6" />
              </div>
              <div>
                <p class="text-sm font-bold text-foreground">
                  {{ isDragging ? $t("Paleiskite failą") : $t("Įkelti nuotrauką") }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                  {{ $t("Vilkite arba spustelėkite") }}
                </p>
              </div>
              <p class="text-[11px] text-muted-foreground">
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
            class="group relative aspect-square w-24 border border-border bg-secondary/30 sm:w-28"
          >
            <img v-if="f.url" :src="f.url" :alt="f.name" class="h-full w-full object-cover">

            <!-- New file indicator -->
            <div
              v-if="f.file"
              class="absolute bottom-1 left-1 flex items-center gap-0.5 bg-background border border-border px-1.5 py-0.5 text-[10px] font-bold text-foreground shadow-xs"
            >
              <UploadIcon class="size-2.5" />
            </div>

            <!-- Crop button (on hover) -->
            <Button
              v-if="cropper && f.url"
              type="button"
              variant="secondary"
              size="icon-xs"
              class="absolute left-1 top-1 opacity-0 shadow-xs transition-opacity group-hover:opacity-100"
              @click="openCropper(f)"
            >
              <Crop class="size-3" />
            </Button>

            <!-- Remove button -->
            <Button
              type="button"
              variant="destructive"
              size="icon-xs"
              class="absolute right-1 top-1 opacity-0 shadow-xs transition-opacity group-hover:opacity-100"
              @click="handleRemove(f)"
            >
              <X class="size-3" />
            </Button>
          </div>

          <!-- Add more button / Drop zone -->
          <UploadDropzone v-if="canUpload" size="card" class="w-24 sm:w-28">
            <template #default="{ isDragging }">
              <div class="flex flex-col items-center justify-center gap-1">
                <div
                  class="flex size-8 items-center justify-center border border-border transition-colors"
                  :class="
                    isDragging
                      ? 'border-brand bg-brand/10 text-brand'
                      : 'bg-background text-muted-foreground'
                  "
                >
                  <Plus class="size-4" />
                </div>
                <span class="text-xs text-muted-foreground">
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
    <DialogContent class="max-h-[calc(100dvh-2rem)] max-w-5xl gap-0 overflow-y-auto p-0">
      <ImageCropper
        v-if="cropperImageUrl"
        :src="cropperImageUrl"
        @crop="handleCropFinish"
        @cancel="handleCropCancel"
      />
    </DialogContent>
  </Dialog>

  <!-- Focal Point Modal -->
  <Dialog v-model:open="showFocalPointModal">
    <DialogContent class="max-w-xl">
      <FocalPointPicker
        v-if="previewUrl"
        :image-url="previewUrl"
        :model-value="focalPointValue ?? null"
        @update:model-value="(val: string) => { emit('update:focalPointValue', val); }"
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
 * - Optional focal point picker in a dialog
 * - Deferred mode: Files stored locally for form submission (Spatie Media Library)
 * - Immediate mode: Files uploaded immediately to server and URL returned
 * - Existing image preview for edit forms
 */
import { computed, ref, watch, onMounted, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { Check, Crop, Crosshair, ImagePlus, Loader2, Plus, RefreshCw, Trash2, Upload as UploadIcon, X } from 'lucide-vue-next';

import FocalPointPicker from './FocalPointPicker.vue';

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
  /** Enable focal point picker */
  focalPoint?: boolean;
  /** Current focal point value (e.g. "50% 30%") */
  focalPointValue?: string | null;
  /** Preview aspect ratio: 'video' (16:9) or '4/3' */
  previewAspect?: 'video' | '4/3';
  /** Expand preview and dropzone to full width of container */
  fullWidth?: boolean;
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
  previewAspect: 'video',
  fullWidth: false,
});

const emit = defineEmits<{
  (e: 'update:file', file: File | null): void;
  (e: 'update:files', files: File[]): void;
  (e: 'update:url', url: string | null): void;
  (e: 'update:urls', urls: string[]): void;
  (e: 'compression', result: CompressionResult): void;
  (e: 'remove:existing', item: { id: string | number; url: string }): void;
  (e: 'update:focalPointValue', value: string | null): void;
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

// Focal point modal state
const showFocalPointModal = ref(false);

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

const previewObjectPosition = computed(() => {
  if (!props.focalPoint) return undefined;
  return props.focalPointValue ?? '50% 30%';
});

// Compression options
const compressionOptions = computed<CompressionOptions>(() => {
  if (typeof props.compress === 'object') {
    return props.compress;
  }
  return {
    maxSizeMB: props.cropper ? 3 : 2,
    maxWidthOrHeight: props.cropper ? 2048 : 1600,
    fileType: 'image/webp',
    quality: props.cropper ? 0.9 : 0.8,
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
  const fileType = data.blob.type || 'image/webp';
  const extension = fileType === 'image/png' ? '.png' : fileType === 'image/jpeg' ? '.jpg' : '.webp';
  const fileName = uploadFile.name.replace(/\.[^.]+$/, extension);
  const croppedFile = new File([data.blob], fileName, { type: fileType });

  // Default crop output has already been encoded at the target quality.
  const processedFile = typeof props.compress === 'object'
    ? await processFile(croppedFile)
    : croppedFile;

  // Update the file
  uploadFile.file = processedFile;
  uploadFile.url = processedFile === croppedFile
    ? data.dataUrl
    : await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result));
        reader.onerror = () => reject(reader.error);
        reader.readAsDataURL(processedFile);
      });
  uploadFile.name = processedFile.name;

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
