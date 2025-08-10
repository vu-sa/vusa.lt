<template>
  <div class="space-y-4">
    <!-- Upload Area -->
    <div
      @drop="handleDrop"
      @dragover.prevent
      @dragenter.prevent
      :class="[
        'relative border-2 border-dashed rounded-lg p-8 text-center transition-colors',
        isDragOver 
          ? 'border-vusa-red bg-vusa-red/5' 
          : 'border-muted-foreground hover:border-vusa-red hover:bg-muted/50'
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        @change="handleFileSelect"
        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        :accept="acceptString"
      />
      
      <div class="flex flex-col items-center gap-3">
        <IFluentCloudArrowUp24Regular class="h-12 w-12 text-muted-foreground" />
        <div>
          <p class="text-base font-medium text-foreground">
            Įkelkite failus
          </p>
          <p class="text-sm text-muted-foreground mt-1">
            Vilkite failus čia arba <span class="text-vusa-red font-medium">spustelėkite pasirinkimui</span>
          </p>
          <div class="mt-2 space-y-1">
            <p class="text-xs text-muted-foreground">
              <span class="font-medium">Palaikomi formatai:</span> {{ formattedExtensions }}
            </p>
            <p class="text-xs text-muted-foreground">
              <span class="font-medium">Maksimalus dydis:</span> {{ allowedTypes?.maxSizeMB || maxSizeMB }}MB per failą
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Files List -->
    <div v-if="selectedFiles.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-medium text-foreground">
          Pasirinkti failai ({{ selectedFiles.length }})
        </h4>
        <Button variant="outline" size="sm" @click="clearFiles">
          <IFluentDismiss24Regular class="h-4 w-4 mr-1" />
          Išvalyti
        </Button>
      </div>
      
      <div class="space-y-2 max-h-48 overflow-y-auto">
        <div
          v-for="(file, index) in selectedFiles"
          :key="index"
          class="flex items-center gap-3 p-3 rounded-md border border-border bg-muted/30"
        >
          <component 
            :is="getFileIcon(file.name)" 
            class="h-8 w-8 text-muted-foreground flex-shrink-0" 
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-foreground truncate">
              {{ file.name }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ formatFileSize(file.size) }}
            </p>
          </div>
          <Button
            variant="ghost"
            size="sm"
            @click="removeFile(index)"
            class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
          >
            <IFluentDelete24Regular class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <!-- Upload Button -->
      <div class="flex justify-end items-center pt-2">
        <Button 
          @click="uploadFiles" 
          :disabled="loading || selectedFiles.length === 0"
          class="min-w-24"
        >
          <div v-if="loading" class="flex items-center gap-2">
            <div class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
            Įkeliama...
          </div>
          <div v-else class="flex items-center gap-2">
            <IFluentCloudArrowUp24Regular class="h-4 w-4" />
            Įkelti
          </div>
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, defineEmits, onMounted, computed } from 'vue';
import { Button } from '@/Components/ui/button';

interface FileUploadProps {
  accept?: string;
  maxSizeMB?: number;
  loading?: boolean;
  // When true, use provided accept/extensions instead of server-allowed types
  forceAccept?: boolean;
  // Optional filtered extensions to display/validate (e.g., only images)
  extensions?: string[];
}

const props = withDefaults(defineProps<FileUploadProps>(), {
  accept: '*',
  maxSizeMB: 50,
  loading: false,
  forceAccept: false,
});

const emit = defineEmits<{
  upload: [files: File[]];
  'files-selected': [files: File[]];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const isDragOver = ref(false);
const allowedTypes = ref<{extensions: string[], accept: string, maxSizeMB: number} | null>(null);

// Computed accept string for file input
const acceptString = computed(() => {
  // If forcing accept, always use provided accept string
  if (props.forceAccept && props.accept) {
    return props.accept;
  }
  if (allowedTypes.value) {
    return allowedTypes.value.accept;
  }
  return props.accept;
});

// Computed formatted extensions for display
const formattedExtensions = computed(() => {
  if (props.forceAccept && props.extensions && props.extensions.length > 0) {
    return props.extensions.join(', ').toUpperCase();
  }
  if (allowedTypes.value === null) {
    return 'Kraunama...';
  }
  if (allowedTypes.value) {
    return allowedTypes.value.extensions.join(', ').toUpperCase();
  }
  return 'Visi formatai';
});

// Fetch allowed file types on component mount
onMounted(async () => {
  // Skip fetching if caller forces accept/extensions
  if (props.forceAccept) return;
  try {
    const response = await fetch('/mano/files/allowed-types');
    if (response.ok) {
      allowedTypes.value = await response.json();
    }
  } catch (error) {
    console.warn('Could not fetch allowed file types:', error);
  }
});

function handleDrop(event: DragEvent) {
  event.preventDefault();
  isDragOver.value = false;
  
  const files = Array.from(event.dataTransfer?.files || []);
  addFiles(files);
}

function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files || []);
  addFiles(files);
}

function addFiles(files: File[]) {
  const validFiles = files.filter(file => {
    // Check file size
    const maxSize = allowedTypes.value?.maxSizeMB || props.maxSizeMB;
    if (file.size > maxSize * 1024 * 1024) {
      console.warn(`File ${file.name} is too large (max ${maxSize}MB)`);
      return false;
    }
    
    // Resolve allowed extensions: custom filter or server-provided
    const allowedExts = (props.forceAccept && props.extensions?.length)
      ? props.extensions.map(e => e.toLowerCase())
      : (allowedTypes.value?.extensions || undefined);

    if (allowedExts) {
      const fileExtension = file.name.split('.').pop()?.toLowerCase();
      if (!fileExtension || !allowedExts.includes(fileExtension)) {
        console.warn(`File type ${fileExtension} is not allowed`);
        return false;
      }
    }
    
    return true;
  });
  
  selectedFiles.value = [...selectedFiles.value, ...validFiles];
  emit('files-selected', selectedFiles.value);
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1);
  emit('files-selected', selectedFiles.value);
}

function clearFiles() {
  selectedFiles.value = [];
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  emit('files-selected', []);
}

function uploadFiles() {
  if (selectedFiles.value.length > 0) {
    emit('upload', selectedFiles.value);
  }
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function getFileIcon(fileName: string) {
  const extension = fileName.split('.').pop()?.toLowerCase();
  
  if (!extension) return 'IFluentDocument24Regular';
  
  // Document files
  if (['doc', 'docx', 'odt'].includes(extension)) {
    return 'IFluentDocumentText24Regular';
  }
  
  // Spreadsheet files
  if (['xls', 'xlsx', 'csv'].includes(extension)) {
    return 'IFluentDocumentTable24Regular';
  }
  
  // PDF files
  if (extension === 'pdf') {
    return 'IFluentDocumentPdf24Regular';
  }
  
  // Image files
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension)) {
    return 'IFluentImage24Regular';
  }
  
  // Video files
  if (['mp4', 'avi', 'mkv', 'mov', 'webm'].includes(extension)) {
    return 'IFluentVideo24Regular';
  }
  
  // Audio files
  if (['mp3', 'wav', 'flac', 'aac'].includes(extension)) {
    return 'IFluentMusicNote24Regular';
  }
  
  // Archive files
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
    return 'IFluentFolderZip24Regular';
  }
  
  // Code files
  if (['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java'].includes(extension)) {
    return 'IFluentCode24Regular';
  }
  
  return 'IFluentDocument24Regular';
}

// Expose method to clear files externally
defineExpose({
  clearFiles
});
</script>