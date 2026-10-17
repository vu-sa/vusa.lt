<template>
  <Upload
    ref="uploadRef"
    :max
    :accept
    :max-size
    deferred
    :disabled="isBusy"
    :aria-busy="isBusy"
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

          <div
            v-if="isBusy"
            class="absolute inset-0 z-10 flex items-center justify-center bg-background/85 text-foreground"
            role="status"
            aria-live="polite"
            data-slot="image-upload-status"
          >
            <div class="flex flex-col items-center gap-2">
              <Loader2 class="size-6 motion-safe:animate-spin" aria-hidden="true" />
              <span class="text-sm">
                {{ $t(isCompressing ? 'forms.image_upload.optimizing' : 'forms.image_upload.uploading') }}
              </span>
            </div>
          </div>

          <!-- New file indicator -->
          <div
            v-if="isNewFile && !isBusy && localFiles[0]?.status === 'success'"
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
              :disabled="isBusy"
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
              :disabled="isBusy"
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
              :disabled="isBusy"
              variant="destructive"
              size="sm"
              @click="removeFile(localFiles[0] as UploadFile)"
            >
              <Trash2 class="size-4" />
            </Button>
          </div>

          <!-- Success indicator -->
          <div
            v-if="isNewFile && !isBusy && localFiles[0]?.status === 'success'"
            class="absolute bottom-2 right-2 flex size-6 items-center justify-center border border-border bg-background text-[var(--status-success)] shadow-xs"
            data-slot="image-upload-success"
          >
            <Check class="size-3.5" />
          </div>
        </div>

        <!-- Focal point button -->
        <Button
          v-if="focalPoint && hasContent && previewUrl"
          type="button"
          :disabled="isBusy || localFiles[0]?.status === 'error'"
          variant="outline"
          size="sm"
          class="mt-2"
          @click="showFocalPointModal = true"
        >
          <Crosshair class="mr-1.5 size-4" />
          {{ $t("Nustatyti fokuso tašką") }}
          <span v-if="currentFocalPoint" class="ml-1.5 font-mono text-[10px] text-muted-foreground">
            {{ currentFocalPoint }}
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
                  {{ isDragging ? $t("Paleisk failą") : $t("Įkelti nuotrauką") }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                  {{ $t("Vilk arba spustelėk") }}
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

            <div v-if="f.status === 'uploading'" class="absolute inset-0 z-10 flex items-center justify-center bg-background/85 text-foreground" role="status">
              <Loader2 class="size-5 motion-safe:animate-spin" aria-hidden="true" />
              <span class="sr-only">{{ $t('forms.image_upload.uploading') }}</span>
            </div>

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
              :disabled="isBusy"
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
              :disabled="isBusy"
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

      <div
        v-for="failed in localFiles.filter(f => f.status === 'error')"
        :key="failed.id"
        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-[var(--status-danger)]"
        role="alert"
      >
        <span>{{ failed.error }}</span>
        <Button v-if="isImmediate && failed.file" type="button" variant="outline" :disabled="isBusy" @click="retryUpload(failed)">
          {{ $t('forms.image_upload.retry') }}
        </Button>
      </div>
    </template>
  </Upload>

  <!-- Cropper Modal -->
  <Dialog v-model:open="showCropperModal">
    <DialogContent class="max-h-[calc(100dvh-2rem)] max-w-5xl gap-0 overflow-y-auto p-0">
      <ImageCropper
        v-if="showCropperModal && cropperImageUrl"
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
        v-if="showFocalPointModal && previewUrl"
        :image-url="previewUrl"
        :model-value="currentFocalPoint"
        @update:model-value="setFocalPoint"
      />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Check, Crop, Crosshair, ImagePlus, Loader2, Plus, RefreshCw, Trash2, Upload as UploadIcon, X } from '@lucide/vue';
import { toast } from 'vue-sonner';

const FocalPointPicker = defineAsyncComponent(() => import('./FocalPointPicker.vue'));
const ImageCropper = defineAsyncComponent(() => import('./ImageCropper.vue'));

import Upload from './Upload.vue';
import UploadDropzone from './UploadDropzone.vue';
import type { UploadFile } from './variants';

import { useImageCompression, type CompressionOptions, type CompressionResult } from '@/Composables/useImageCompression';
import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent } from '@/Components/ui/dialog';
import type { ImageData } from '@/Types/media';

export interface ImageUploadProps {
  /** Maximum number of files allowed. Use 1 for single file upload (default: 1) */
  max?: number;
  /** 'deferred' stores files for form submit, 'media' stages them as ImageData, 'immediate' (legacy) returns a URL */
  mode?: 'deferred' | 'media' | 'immediate';
  /** Enable cropping functionality */
  cropper?: boolean;
  /** Browser-side compression (true for defaults, or custom options). Defaults to on, except in
   *  `media` mode, where the server re-encodes the upload anyway. */
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
  compress: undefined,
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
  (e: 'update:image', value: ImageData | null): void;
}>();

// Models for two-way binding
// Note: For multiple files (files), we use emit directly instead of defineModel due to array reactivity issues
const file = defineModel<File | null>('file', { default: null });
const url = defineModel<string | null>('url', { default: null });
const urls = defineModel<string[]>('urls', { default: () => [] });
const image = defineModel<ImageData | null>('image', { default: null });

// Component refs
const uploadRef = ref<InstanceType<typeof Upload> | null>(null);

// Media mode: what the server returned for each staged file, keyed by local file id.
const stagedImages = new Map<string, ImageData>();

// Local state
const localFiles = ref<UploadFile[]>([]);
const isCompressing = ref(false);
const isBusy = ref(false);
let uploadController: AbortController | null = null;
let disposed = false;

onBeforeUnmount(() => {
  disposed = true;
  uploadController?.abort();
});

// Cropper modal state
const showCropperModal = ref(false);
const cropperImageUrl = ref<string | null>(null);
const cropperFileId = ref<string | null>(null);

// Focal point modal state
const showFocalPointModal = ref(false);

// Compression composable
const { compressImage } = useImageCompression();

// Computed
const isSingle = computed(() => props.max === 1);
const isMedia = computed(() => props.mode === 'media');
// Transitional(legacy-images): 'immediate' goes once no form uploads to the shared folders.
const isImmediate = computed(() => props.mode === 'immediate' || isMedia.value);
const currentFocalPoint = computed(() => (isMedia.value ? image.value?.focal_point : props.focalPointValue) ?? null);

function setFocalPoint(value: string | null) {
  if (isMedia.value) {
    if (image.value) {
      image.value = { ...image.value, focal_point: value };

      const currentId = localFiles.value[0]?.id;
      if (currentId && stagedImages.has(currentId)) {
        stagedImages.set(currentId, image.value);
      }
    }

    return;
  }

  emit('update:focalPointValue', value);
}
const previewObjectPosition = computed(() => {
  if (!props.focalPoint) return undefined;
  return currentFocalPoint.value ?? '50% 30%';
});

// Compression options
const compressionOptions = computed<CompressionOptions>(() => {
  if (typeof props.compress === 'object') {
    return props.compress;
  }
  return {
    // Under the 10 MB request cap without forcing extra quality loss on a 3840px photo.
    maxSizeMB: 8,
    maxWidthOrHeight: 3840,
    fileType: 'image/webp',
    quality: 0.9,
  };
});

// Initialize with existing URLs (from props or v-model)
function initializeFromExistingUrls() {
  // For single mode, check both existingUrl prop and url model
  const existingUrl = props.existingUrl || (isMedia.value ? image.value?.url : url.value);

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

// Watch for changes in url model (single mode, v-model:url / v-model:image)
watch(
  () => (isMedia.value ? image.value?.url : url.value),
  (newUrl) => {
    if (isSingle.value && newUrl && localFiles.value.length === 0) {
      initializeFromExistingUrls();
    }
  },
);

// Process file with compression
const compressionEnabled = computed(() => props.compress ?? props.mode !== 'media');

async function processFile(rawFile: File): Promise<File> {
  if (!compressionEnabled.value) {
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
  if (isBusy.value || disposed) return;

  localFiles.value = [...newFiles];
  const filesToProcess = localFiles.value.filter(
    f => f.file && f.status !== 'error' && !f.id.startsWith('processed-') && !f.id.startsWith('existing-'),
  );

  isBusy.value = filesToProcess.length > 0;
  try {
    for (const uploadFile of filesToProcess) {
      if (!uploadFile.file) continue;
      if (isImmediate.value) uploadFile.status = 'pending';
      try {
        const processedFile = await processFile(uploadFile.file);
        if (disposed) return;

        uploadFile.file = processedFile;
        uploadFile.id = `processed-${uploadFile.id}`;
        if (isImmediate.value) await uploadFileToServer(uploadFile);
      }
      catch {
        if (disposed) return;
        uploadFile.status = 'error';
        uploadFile.error = trans('forms.image_upload.failed');
      }
    }
    if (!disposed) updateModels();
  }
  finally {
    isBusy.value = false;
  }
}

// Upload file to server (immediate mode)
async function uploadFileToServer(uploadFile: UploadFile) {
  if (!uploadFile.file || disposed) return;

  const controller = new AbortController();
  uploadController = controller;
  uploadFile.status = 'uploading';
  uploadFile.progress = 0;
  uploadFile.error = undefined;
  let failureMessage = trans('forms.image_upload.failed');

  const formData = new FormData();
  formData.append('image', uploadFile.file);

  if (!isMedia.value) {
    formData.append('path', props.folder);
    formData.append('name', uploadFile.name);
  }

  try {
    const response = await fetch(route(isMedia.value ? 'api.v1.admin.pendingUploads.store' : 'files.uploadImage'), {
      method: 'POST',
      signal: controller.signal,
      headers: {
        'X-CSRF-TOKEN': usePage().props.csrf_token as string,
        'Accept': 'application/json',
      },
      body: formData,
    });

    if (!response.ok) {
      const body = await response.json().catch(() => null) as { message?: string } | null;
      failureMessage = body?.message ?? failureMessage;
      throw new Error(failureMessage);
    }

    const data = await response.json();
    if (disposed || controller.signal.aborted) return;

    if (isMedia.value) {
      const staged = data.data as ImageData;
      stagedImages.set(uploadFile.id, { ...staged, focal_point: currentFocalPoint.value });
    }

    uploadFile.url = isMedia.value ? (data.data as ImageData).url : data.url;
    uploadFile.status = 'success';
    uploadFile.progress = 100;

    updateModels();
  }
  catch {
    if (disposed || controller.signal.aborted) return;
    uploadFile.status = 'error';
    uploadFile.error = failureMessage;

    if (isMedia.value) {
      toast.error(uploadFile.error);
    }
  }
  finally {
    if (uploadController === controller) uploadController = null;
  }
}

async function retryUpload(uploadFile: UploadFile) {
  if (isBusy.value || disposed) return;
  isBusy.value = true;
  try {
    await uploadFileToServer(uploadFile);
  }
  finally {
    isBusy.value = false;
  }
}

// Update v-model values
function updateModels() {
  if (isSingle.value) {
    if (isImmediate.value && localFiles.value[0] && localFiles.value[0].status !== 'success') return;
    // Single file mode
    const currentFile = localFiles.value.find(f => f.file);
    file.value = currentFile?.file ?? null;
    emit('update:file', file.value);

    if (isMedia.value) {
      const current = localFiles.value[0];

      if (!current) {
        image.value = null;
      }
      else if (stagedImages.has(current.id)) {
        image.value = stagedImages.get(current.id) ?? null;
      }
    }
    else if (isImmediate.value) {
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
  if (isBusy.value) return;
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
  if (isBusy.value || !props.cropper || !uploadFile.url) return;

  cropperImageUrl.value = uploadFile.url;
  cropperFileId.value = uploadFile.id;
  showCropperModal.value = true;
}

// Handle crop finish
async function handleCropFinish(data: { dataUrl: string; blob: Blob }) {
  if (isBusy.value || disposed) return;
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

  isBusy.value = true;
  try {
    // Default crop output has already been encoded at the target quality.
    const processedFile = typeof props.compress === 'object' ? await processFile(croppedFile) : croppedFile;
    if (disposed) return;

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

    if (isImmediate.value) await uploadFileToServer(uploadFile);
    if (!disposed) updateModels();
  }
  catch {
    if (disposed) return;
    uploadFile.status = 'error';
    uploadFile.error = trans('forms.image_upload.failed');
  }
  finally {
    isBusy.value = false;
    cropperImageUrl.value = null;
    cropperFileId.value = null;
  }
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
