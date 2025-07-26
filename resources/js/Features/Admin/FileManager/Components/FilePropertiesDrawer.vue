<template>
  <Sheet :open="!!selectedFile" @update:open="handleClose">
    <SheetContent side="bottom" class="max-h-[80vh] overflow-y-auto p-0">
      <div class="p-4 pb-6">
        <SheetHeader class="pb-4">
          <SheetTitle class="text-base font-medium">
            {{ fileName }}
          </SheetTitle>
          <SheetDescription class="text-sm text-muted-foreground">
            File properties and actions
          </SheetDescription>
        </SheetHeader>
        
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- File Icon -->
          <div class="flex-shrink-0 flex justify-center lg:justify-start">
            <div class="p-3 rounded-lg bg-muted/50 inline-flex">
              <span class="text-vusa-red">
                <!-- PDF files -->
                <IFluentDocumentPdf24Regular v-if="fileExtension.toLowerCase() === 'pdf'" class="h-12 w-12" />
                <!-- Document files -->
                <IFluentDocumentText24Regular v-else-if="['doc', 'docx', 'odt', 'txt', 'rtf'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Spreadsheet files including CSV -->
                <IFluentDocumentTable24Regular v-else-if="['xls', 'xlsx', 'csv', 'ods'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Video files -->
                <IFluentVideo24Regular v-else-if="['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Audio files -->
                <IFluentMusicNote24Regular v-else-if="['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Archive files -->
                <IFluentFolderZip24Regular v-else-if="['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Code files -->
                <IFluentCode24Regular v-else-if="['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Image files -->
                <IFluentImage24Regular v-else-if="['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'ico'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Default fallback for any other file type -->
                <IFluentDocument24Regular v-else class="h-12 w-12" />
              </span>
            </div>
          </div>
          
          <div class="flex-1 min-w-0">
            <!-- File info grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentDocument24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">Type</p>
                  <p class="text-sm font-medium truncate">{{ fileExtension }}</p>
                </div>
              </div>
              
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentStorage24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">Size</p>
                  <p class="text-sm font-medium">{{ fileSize }}</p>
                </div>
              </div>
              
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentCalendar24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">Modified</p>
                  <p class="text-sm font-medium">{{ fileDate }}</p>
                </div>
              </div>
              
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentFolder24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">Location</p>
                  <p class="text-sm font-medium truncate">{{ relativePath }}</p>
                </div>
              </div>
            </div>
            
            <!-- Action buttons -->
            <div class="flex flex-wrap gap-2">
              <Button @click="$emit('preview')" size="sm" class="flex-1 sm:flex-none">
                <IFluentOpen24Regular class="h-4 w-4 mr-2" />
                Preview
              </Button>
              <Button @click="$emit('delete')" variant="destructive" size="sm" class="flex-1 sm:flex-none">
                <IFluentDelete24Filled class="h-4 w-4 mr-2" />
                Delete
              </Button>
            </div>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { 
  Sheet, 
  SheetContent, 
  SheetHeader, 
  SheetTitle, 
  SheetDescription 
} from '@/Components/ui/sheet';

// Import all necessary icons
import IFluentDocumentPdf24Regular from '~icons/fluent/document-pdf-24-regular';
import IFluentDocumentText24Regular from '~icons/fluent/document-text-24-regular';
import IFluentDocumentTable24Regular from '~icons/fluent/document-table-24-regular';
import IFluentVideo24Regular from '~icons/fluent/video-24-regular';
import IFluentMusicNote24Regular from '~icons/fluent/music-note-2-24-regular';
import IFluentFolderZip24Regular from '~icons/fluent/folder-zip-24-regular';
import IFluentCode24Regular from '~icons/fluent/code-24-regular';
import IFluentImage24Regular from '~icons/fluent/image-24-regular';
import IFluentDocument24Regular from '~icons/fluent/document-24-regular';
import IFluentStorage24Regular from '~icons/fluent/storage-24-regular';
import IFluentCalendar24Regular from '~icons/fluent/calendar-24-regular';
import IFluentFolder24Regular from '~icons/fluent/folder-24-regular';
import IFluentOpen24Regular from '~icons/fluent/open-24-regular';
import IFluentDelete24Filled from '~icons/fluent/delete-24-filled';

const props = defineProps<{
  selectedFile: string | null;
  files: any[];
}>();

const emit = defineEmits<{
  'preview': [];
  'delete': [];
  'close': [];
}>();

const fileName = computed(() => {
  if (!props.selectedFile) return '';
  return props.selectedFile.split('/').pop() || 'Unknown file';
});

const fileExtension = computed(() => {
  if (!props.selectedFile) return '';
  const fileName = props.selectedFile.split('/').pop() || '';
  const extension = fileName.split('.').pop()?.toLowerCase();
  return extension ? extension.toUpperCase() : 'File';
});

const fileSize = computed(() => {
  if (!props.selectedFile) return 'Unknown';
  const fileInfo = props.files.find((file: any) => file.path === props.selectedFile);
  if (fileInfo?.size) {
    const bytes = fileInfo.size;
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }
  return 'Unknown';
});

const fileDate = computed(() => {
  if (!props.selectedFile) return 'Unknown';
  const fileInfo = props.files.find((file: any) => file.path === props.selectedFile);
  if (fileInfo?.modified) {
    return new Date(fileInfo.modified * 1000).toLocaleDateString('lt-LT');
  }
  return 'Unknown';
});

const relativePath = computed(() => {
  if (!props.selectedFile) return '/';
  // Remove public/files prefix and filename to show just the directory
  const pathWithoutPublicFiles = props.selectedFile.replace('public/files/', '');
  const directory = pathWithoutPublicFiles.substring(0, pathWithoutPublicFiles.lastIndexOf('/'));
  return directory || '/';
});

// Handle close with proper emit
function handleClose(open: boolean) {
  if (!open) {
    emit('close');
  }
}
</script>