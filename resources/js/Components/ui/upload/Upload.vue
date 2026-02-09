<template>
  <div
    :class="cn('relative', props.class)"
    data-slot="upload"
  >
    <!-- Hidden file input -->
    <input
      ref="inputRef"
      type="file"
      :accept
      :multiple="!isSingle"
      :disabled
      class="sr-only"
      @change="handleInputChange"
    >

    <slot
      :files
      :is-dragging
      :can-upload
      :open-file-dialog
      :remove-file
    />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, provide, ref, readonly, triggerRef, shallowRef } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { uploadVariants, type UploadFile, type UploadVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  /** Maximum number of files allowed. Use 1 for single file upload */
  max?: number;
  /** Accepted file types (e.g., "image/*", "image/png,image/jpeg") */
  accept?: string;
  /** Maximum file size in bytes */
  maxSize?: number;
  /** Upload action URL. If not provided, files are stored locally (deferred mode) */
  action?: string;
  /** Additional data to send with upload */
  data?: Record<string, string>;
  /** Whether the upload is disabled */
  disabled?: boolean;
  /** Custom class */
  class?: HTMLAttributes['class'];
  /** Size variant */
  size?: UploadVariants['size'];
  /** List type display */
  listType?: 'text' | 'image-card';
  /** Deferred mode - don't auto-upload, store files for form submission */
  deferred?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  max: Infinity,
  accept: '*',
  maxSize: 10 * 1024 * 1024, // 10MB default
  disabled: false,
  size: 'default',
  listType: 'image-card',
  deferred: false,
});

const emit = defineEmits<{
  (e: 'update:files', files: UploadFile[]): void;
  (e: 'upload:start', file: UploadFile): void;
  (e: 'upload:progress', file: UploadFile, progress: number): void;
  (e: 'upload:success', file: UploadFile, response: unknown): void;
  (e: 'upload:error', file: UploadFile, error: string): void;
  (e: 'remove', file: UploadFile): void;
  (e: 'preview', file: UploadFile): void;
}>();

const page = usePage();
const files = shallowRef<UploadFile[]>([]);
const isDragging = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

const isSingle = computed(() => props.max === 1);
const canUpload = computed(() => !props.disabled && files.value.length < props.max);
const hasFiles = computed(() => files.value.length > 0);

const variant = computed<UploadVariants['variant']>(() => {
  if (isDragging.value) return 'active';
  return 'default';
});

function generateId(): string {
  return `upload-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
}

function validateFile(file: File): string | null {
  // Check file size
  if (file.size > props.maxSize) {
    const maxMB = Math.round(props.maxSize / 1024 / 1024);
    return `Failas per didelis. Maksimalus dydis: ${maxMB}MB`;
  }

  // Check file type
  if (props.accept !== '*') {
    const acceptedTypes = props.accept.split(',').map(t => t.trim());
    const fileType = file.type;
    const fileExtension = `.${file.name.split('.').pop()?.toLowerCase()}`;

    const isAccepted = acceptedTypes.some((type) => {
      if (type.startsWith('.')) {
        return fileExtension === type.toLowerCase();
      }
      if (type.endsWith('/*')) {
        return fileType.startsWith(type.replace('/*', '/'));
      }
      return fileType === type;
    });

    if (!isAccepted) {
      return 'Netinkamas failo tipas';
    }
  }

  return null;
}

async function doUpload(file: UploadFile) {
  if (!props.action || !file.file) return;

  const formData = new FormData();
  formData.append('file', file.file);

  // Add custom data
  if (props.data) {
    Object.entries(props.data).forEach(([key, value]) => {
      formData.append(key, value);
    });
  }

  file.status = 'uploading';
  emit('upload:start', file);

  try {
    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable) {
        file.progress = Math.round((e.loaded / e.total) * 100);
        triggerRef(files);
        emit('upload:progress', file, file.progress);
      }
    });

    await new Promise<void>((resolve, reject) => {
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const response = JSON.parse(xhr.responseText);
            file.url = response.url;
            file.status = 'success';
            file.progress = 100;
            triggerRef(files);
            emit('upload:success', file, response);
            resolve();
          }
          catch {
            reject(new Error('Invalid server response'));
          }
        }
        else {
          reject(new Error(`Upload failed: ${xhr.status}`));
        }
      };

      xhr.onerror = () => reject(new Error('Network error'));

      xhr.open('POST', props.action!);
      xhr.setRequestHeader('X-CSRF-TOKEN', page.props.csrf_token as string);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.send(formData);
    });
  }
  catch (error) {
    file.status = 'error';
    file.error = error instanceof Error ? error.message : 'Upload failed';
    triggerRef(files);
    emit('upload:error', file, file.error);
  }

  emit('update:files', files.value);
}

function handleFiles(fileList: FileList | null) {
  if (!fileList || !canUpload.value) return;

  const newFiles = Array.from(fileList);
  const availableSlots = props.max - files.value.length;

  newFiles.slice(0, availableSlots).forEach((file) => {
    const error = validateFile(file);

    // In deferred mode, files are marked as 'success' immediately (ready for form submit)
    // In immediate mode, files start as 'pending' and get uploaded
    const initialStatus = props.deferred
      ? (error ? 'error' : 'success')
      : (error ? 'error' : 'pending');

    const newUploadFile: UploadFile = {
      id: generateId(),
      name: file.name,
      size: file.size,
      type: file.type,
      file,
      status: initialStatus,
      progress: props.deferred ? 100 : 0,
      error: error || undefined,
      url: URL.createObjectURL(file),
    };

    // For single file mode, replace existing file
    if (isSingle.value) {
      // Revoke old blob URL if exists
      if (files.value[0]?.url?.startsWith('blob:')) {
        URL.revokeObjectURL(files.value[0].url);
      }
      files.value = [newUploadFile];
    }
    else {
      files.value.push(newUploadFile);
    }

    // Auto-upload if action is provided, not in deferred mode, and no validation error
    if (props.action && !props.deferred && !error) {
      void doUpload(newUploadFile);
    }
  });

  triggerRef(files);
  emit('update:files', files.value);

  // Reset input
  if (inputRef.value) {
    inputRef.value.value = '';
  }
}

function removeFile(fileToRemove: UploadFile) {
  const index = files.value.findIndex(f => f.id === fileToRemove.id);
  if (index !== -1) {
    // Revoke object URL to prevent memory leaks
    if (fileToRemove.url?.startsWith('blob:')) {
      URL.revokeObjectURL(fileToRemove.url);
    }
    files.value.splice(index, 1);
    emit('remove', fileToRemove);
    emit('update:files', files.value);
  }
}

function openFileDialog() {
  // Allow opening dialog in single mode for replacement, or when canUpload is true
  if ((isSingle.value || canUpload.value) && !props.disabled && inputRef.value) {
    inputRef.value.click();
  }
}

function handleDragEnter(e: DragEvent) {
  e.preventDefault();
  if (canUpload.value) {
    isDragging.value = true;
  }
}

function handleDragLeave(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
}

function handleDragOver(e: DragEvent) {
  e.preventDefault();
}

function handleDrop(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
  if (canUpload.value) {
    handleFiles(e.dataTransfer?.files || null);
  }
}

function handleInputChange(e: Event) {
  const target = e.target as HTMLInputElement;
  handleFiles(target.files);
}

// Set file list from outside (for editing existing files)
function setFiles(newFiles: UploadFile[]) {
  files.value = newFiles;
  triggerRef(files);
}

// Get raw File objects for form submission (used in deferred mode)
function getFilesForSubmit(): File[] {
  return files.value
    .filter(f => f.status === 'success' && f.file)
    .map(f => f.file!);
}

// Get single file for form submission (used in deferred mode with max=1)
function getFileForSubmit(): File | null {
  const file = files.value.find(f => f.status === 'success' && f.file);
  return file?.file ?? null;
}

// Expose methods for parent components
defineExpose({
  files: readonly(files),
  openFileDialog,
  removeFile,
  setFiles,
  getFilesForSubmit,
  getFileForSubmit,
});

// Provide context for child components
provide('upload', {
  files: readonly(files),
  isDragging: readonly(isDragging),
  canUpload,
  isSingle,
  hasFiles,
  max: props.max,
  accept: props.accept,
  disabled: props.disabled,
  listType: props.listType,
  openFileDialog,
  removeFile,
  handleDragEnter,
  handleDragLeave,
  handleDragOver,
  handleDrop,
  onPreview: (file: UploadFile) => emit('preview', file),
});
</script>
