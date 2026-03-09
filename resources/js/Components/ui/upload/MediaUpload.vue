<script setup lang="ts">
/**
 * MediaUpload - A deferred upload component for Spatie Media Library integration.
 * 
 * Files are selected and previewed locally, then submitted with the form.
 * The File object is stored and can be retrieved via v-model:file for form submission.
 */
import { ref, watch, onMounted, computed } from "vue"
import { cn } from '@/Utils/Shadcn/utils'
import { Upload, UploadDropzone, type UploadFile } from "@/Components/ui/upload"
import { Button } from "@/Components/ui/button"

interface Props {
  /** Maximum number of files allowed. Use 1 for single file upload */
  max?: number
  /** Custom class for the container */
  class?: string
  /** Existing image URL (for edit mode) */
  existingUrl?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  max: 1,
  existingUrl: null,
})

// Model for the File object to be submitted with the form
const file = defineModel<File | null>('file', { default: null })
// For multiple files
const files = defineModel<File[]>('files', { default: () => [] })

const uploadRef = ref<InstanceType<typeof Upload> | null>(null)
const localFiles = ref<UploadFile[]>([])

const isSingle = computed(() => props.max === 1)

// Initialize with existing URL if present (for edit mode)
onMounted(() => {
  if (props.existingUrl) {
    localFiles.value = [{
      id: 'existing-1',
      name: 'image.jpg',
      size: 0,
      type: 'image/jpeg',
      url: props.existingUrl,
      status: 'success',
      progress: 100,
      // No file object - this is an existing image from the server
    }]
    if (uploadRef.value) {
      uploadRef.value.setFiles(localFiles.value)
    }
  }
})

function handleFilesUpdate(newFiles: UploadFile[]) {
  localFiles.value = newFiles
  
  if (isSingle.value) {
    // Single file mode - update file model
    const newFile = newFiles.find(f => f.file)
    file.value = newFile?.file ?? null
  } else {
    // Multiple files mode - update files model
    files.value = newFiles.filter(f => f.file).map(f => f.file!)
  }
}

function handleRemove(removedFile: UploadFile) {
  // If removing existing image (no file object), just clear the preview
  if (!removedFile.file && removedFile.id === 'existing-1') {
    localFiles.value = []
    file.value = null
  }
}

// Check if we have a displayable image (either new file or existing URL)
const hasImage = computed(() => localFiles.value.length > 0)
const previewUrl = computed(() => localFiles.value[0]?.url ?? null)
const isNewFile = computed(() => !!localFiles.value[0]?.file)
</script>

<template>
  <Upload
    ref="uploadRef"
    :max="max"
    accept="image/jpg,image/jpeg,image/png"
    :max-size="10 * 1024 * 1024"
    deferred
    list-type="image-card"
    :class="cn('w-full', props.class)"
    @update:files="handleFilesUpdate"
    @remove="handleRemove"
  >
    <template #default="{ files: uploadFiles, canUpload, openFileDialog, removeFile }">
      <!-- Single Image Mode -->
      <template v-if="isSingle">
        <!-- Show existing/selected image -->
        <div 
          v-if="hasImage" 
          class="group relative aspect-video w-full max-w-xs overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
        >
          <img 
            v-if="previewUrl"
            :src="previewUrl" 
            alt="Preview"
            class="h-full w-full object-cover"
          >
          
          <!-- New file indicator -->
          <div 
            v-if="isNewFile"
            class="absolute bottom-2 left-2 flex items-center gap-1 rounded-full bg-blue-500 px-2 py-0.5 text-xs text-white shadow-md"
          >
            <IFluentArrowUpload16Regular class="h-3 w-3" />
            {{ $t('Naujas') }}
          </div>

          <!-- Actions overlay -->
          <div 
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
              @click="removeFile(localFiles[0])"
            >
              <IFluentDelete16Regular class="h-4 w-4" />
            </Button>
          </div>

          <!-- Success indicator for new files -->
          <div 
            v-if="isNewFile"
            class="absolute bottom-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white shadow-md"
          >
            <IFluentCheckmark12Regular class="h-3.5 w-3.5" />
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

      <!-- Multiple Images Mode -->
      <template v-else>
        <div class="flex flex-wrap gap-4">
          <!-- Existing & selected images -->
          <div
            v-for="f in localFiles"
            :key="f.id"
            class="group relative aspect-square w-24 rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 sm:w-28"
          >
            <img
              v-if="f.url"
              :src="f.url"
              :alt="f.name"
              class="h-full w-full rounded-md object-cover"
            >

            <!-- New file indicator -->
            <div 
              v-if="f.file"
              class="absolute bottom-1 left-1 flex items-center gap-0.5 rounded-full bg-blue-500 px-1.5 py-0.5 text-[10px] text-white shadow-md"
            >
              <IFluentArrowUpload16Regular class="h-2.5 w-2.5" />
            </div>

            <!-- Remove button -->
            <Button
              type="button"
              variant="destructive"
              size="icon-xs"
              class="absolute right-1 top-1 opacity-0 shadow-md transition-opacity group-hover:opacity-100"
              @click="removeFile(f)"
            >
              <IFluentDismiss12Regular class="h-3 w-3" />
            </Button>
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
    </template>
  </Upload>
</template>
