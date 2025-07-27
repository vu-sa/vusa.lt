<template>
  <div class="group relative flex">
    <button 
      :class="buttonClasses"
      @click="handleClick"
      @dblclick="handleDoubleClick" 
      @keydown.enter="selectionMode ? handleClick() : undefined"
      @keydown.space.prevent="selectionMode ? handleClick() : undefined"
      tabindex="0"
    >
      <!-- File/Folder thumbnail or icon -->
      <div class="h-10 w-10 flex items-center justify-center">
        <!-- Folder icon -->
        <IFluentFolder24Filled 
          v-if="isFolder" 
          class="h-12 w-12 text-muted-foreground group-hover:text-vusa-red transition-colors" 
        />
        <!-- Image thumbnail -->
        <img 
          v-else-if="isImage"
          :src="`/uploads/${item.path?.replace('public/', '') || ''}`"
          :alt="item.name"
          class="h-10 w-10 object-cover rounded shadow-sm"
        />
        <!-- File type icons -->
        <span v-else class="text-muted-foreground group-hover:text-vusa-red transition-colors">
          <!-- PDF files -->
          <IFluentDocumentPdf24Regular v-if="getFileExtension(item.path).toLowerCase() === 'pdf'" class="h-10 w-10" />
          <!-- Document files -->
          <IFluentDocumentText24Regular v-else-if="['doc', 'docx', 'odt', 'txt', 'rtf'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Spreadsheet files including CSV -->
          <IFluentDocumentTable24Regular v-else-if="['xls', 'xlsx', 'csv', 'ods'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Video files -->
          <IFluentVideo24Regular v-else-if="['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Audio files -->
          <IFluentMusicNote24Regular v-else-if="['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Archive files -->
          <IFluentFolderZip24Regular v-else-if="['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Code files -->
          <IFluentCode24Regular v-else-if="['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Image files -->
          <IFluentImage24Regular v-else-if="['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'ico'].includes(getFileExtension(item.path).toLowerCase())" class="h-10 w-10" />
          <!-- Default fallback for any other file type -->
          <IFluentDocument24Regular v-else class="h-10 w-10" />
        </span>
      </div>
      <span class="text-sm text-center mt-3 line-clamp-2 break-all leading-tight"
        :class="isFolder ? 'text-foreground font-medium' : 'text-muted-foreground'">
        {{ item.name }}
      </span>
    </button>
    
    <!-- Selection indicators -->
    <Transition name="selection-badge">
      <div v-if="showSelectionBadge" 
        :class="selectionBadgeClasses">
        {{ selectionBadgeText }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Import all necessary icons
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';
import IFluentDocumentPdf24Regular from '~icons/fluent/document-pdf-24-regular';
import IFluentDocumentText24Regular from '~icons/fluent/document-text-24-regular';
import IFluentDocumentTable24Regular from '~icons/fluent/document-table-24-regular';
import IFluentVideo24Regular from '~icons/fluent/video-24-regular';
import IFluentMusicNote24Regular from '~icons/fluent/music-note-2-24-regular';
import IFluentFolderZip24Regular from '~icons/fluent/folder-zip-24-regular';
import IFluentCode24Regular from '~icons/fluent/code-24-regular';
import IFluentImage24Regular from '~icons/fluent/image-24-regular';
import IFluentDocument24Regular from '~icons/fluent/document-24-regular';

const props = defineProps<{
  item: any;
  isSelected: boolean;
  isMultiSelected: boolean;
  selectionMode?: boolean;
  isMultiSelectMode: boolean;
  isFolder?: boolean;
}>();

const emit = defineEmits<{
  'click': [item: any, event?: MouseEvent];
  'doubleClick': [item: any];
}>();

const isFolder = computed(() => props.isFolder || false);

const isImage = computed(() => {
  if (isFolder.value) return false;
  return props.item?.name?.match(/\.(jpg|jpeg|png|webp)$/i);
});


const buttonClasses = computed(() => {
  const baseClasses = 'w-full aspect-square flex flex-col items-center justify-center p-4 rounded-lg border border-border bg-background transition-all duration-200 hover:shadow-md focus:ring-2 focus:ring-vusa-red focus:ring-offset-2';
  
  if (props.selectionMode && props.isSelected) {
    return `${baseClasses} ring-2 ring-vusa-red ring-offset-2 bg-vusa-red/5`;
  } else if (props.isMultiSelectMode && props.isMultiSelected) {
    return `${baseClasses} ring-2 ring-vusa-red ring-offset-2 bg-vusa-red/5`;
  } else if (props.isSelected) {
    return `${baseClasses} ring-2 ring-muted-foreground ring-offset-2 bg-muted`;
  } else {
    return `${baseClasses} hover:bg-muted`;
  }
});

const showSelectionBadge = computed(() => {
  return (props.selectionMode && props.isSelected) ||
         (props.isMultiSelectMode && props.isMultiSelected) ||
         (!props.selectionMode && !props.isMultiSelectMode && props.isSelected);
});

const selectionBadgeClasses = computed(() => {
  const baseClasses = 'absolute top-1 right-1 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold z-20 shadow-lg';
  
  if ((props.selectionMode && props.isSelected) || (props.isMultiSelectMode && props.isMultiSelected)) {
    return `${baseClasses} bg-vusa-red text-white`;
  } else {
    return `${baseClasses} bg-muted-foreground text-white`;
  }
});

const selectionBadgeText = computed(() => {
  if ((props.selectionMode && props.isSelected) || (props.isMultiSelectMode && props.isMultiSelected)) {
    return '✓';
  } else {
    return 'i';
  }
});

function getFileExtension(filePath: string): string {
  const fileName = filePath?.split('/').pop() || '';
  return fileName.split('.').pop()?.toLowerCase() || '';
}

function handleClick(event?: MouseEvent) {
  emit('click', props.item, event);
}

function handleDoubleClick() {
  emit('doubleClick', props.item);
}
</script>

<style scoped>
/* Selection badge animation */
.selection-badge-enter-active {
  transition: all 0.3s ease-out;
}

.selection-badge-leave-active {
  transition: all 0.2s ease-in;
}

.selection-badge-enter-from {
  opacity: 0;
  transform: scale(0.5) rotate(-180deg);
}

.selection-badge-leave-to {
  opacity: 0;
  transform: scale(0.5);
}
</style>