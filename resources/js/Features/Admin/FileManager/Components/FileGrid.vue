<template>
  <div class="rounded-md border border-border shadow-xs">
    <!-- Files count and view controls -->
    <div v-if="hasContent" class="border-b border-border px-4 py-3 bg-muted/50">
      <div class="flex flex-col gap-3 sm:gap-4">
        <!-- Top row: count and main controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="text-sm text-muted-foreground">
            {{ totalItems }} {{ totalItems === 1 ? 'elementas' : 'elementai' }}
            <span v-if="search"> · filtruojama pagal "{{ search }}"</span>
            <span v-if="selectedFiles.size > 0"> · pasirinkta {{ selectedFiles.size }}</span>
          </div>
          
          <div class="flex items-center gap-2">
            <label class="text-sm text-muted-foreground">Rodyti:</label>
            <select 
              :value="itemsPerPage"
              @change="$emit('update:itemsPerPage', Number($event.target.value))"
              class="text-sm border border-border rounded px-2 py-1 bg-background text-foreground"
            >
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
              <option :value="totalItems">Visus</option>
            </select>
          </div>
        </div>
        
        <!-- Bottom row: multi-select controls -->
        <div v-if="!selectionMode && !isUploadMode">
          <!-- Multi-select controls - responsive layout -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <Button 
              variant="outline" 
              size="sm" 
              @click="$emit('toggleMultiSelect')"
              :class="{ 'bg-vusa-red text-white': isMultiSelectMode }"
            >
              <IFluentCheckmarkCircle24Regular v-if="isMultiSelectMode" class="h-4 w-4 mr-1" />
              <IFluentCircle24Regular v-else class="h-4 w-4 mr-1" />
              {{ isMultiSelectMode ? 'Baigti pasirinkimą' : 'Pasirinkti kelis' }}
            </Button>
            
            <!-- Bulk actions - separate row on mobile -->
            <div v-if="isMultiSelectMode && selectedFiles.size > 0" class="flex flex-wrap items-center gap-1 sm:gap-2">
              <Button variant="outline" size="sm" @click="$emit('selectAll')">
                <IFluentCheckmarkCircle24Regular class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">Pasirinkti visus</span>
              </Button>
              <Button variant="outline" size="sm" @click="$emit('clearSelection')">
                <IFluentCircle24Regular class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">Išvalyti</span>
              </Button>
              <Button variant="destructive" size="sm" @click="$emit('deleteSelected')">
                <IFluentDelete24Filled class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">Ištrinti</span>
                <span class="sm:hidden">({{ selectedFiles.size }})</span>
                <span class="hidden sm:inline">({{ selectedFiles.size }})</span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Files and folders grid -->
    <div v-if="hasContent" class="p-6">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 auto-rows-fr">
        <!-- Folders -->
        <FileItem
          v-for="folder in paginatedDirectories"
          :key="folder.id"
          :item="folder"
          :is-selected="false"
          :is-multi-selected="false"
          :selection-mode="selectionMode"
          :is-multi-select-mode="isMultiSelectMode"
          :is-folder="true"
          @click="$emit('folderClick', folder)"
          @double-click="$emit('folderDoubleClick', folder)"
        />
        
        <!-- Files -->
        <FileItem
          v-for="file in paginatedFiles"
          :key="file.id"
          :item="file"
          :is-selected="selectedFile === file.path"
          :is-multi-selected="selectedFiles.has(file.path)"
          :selection-mode="selectionMode"
          :is-multi-select-mode="isMultiSelectMode"
          :is-folder="false"
          @click="$emit('fileClick', file, $event)"
          @double-click="$emit('fileDoubleClick', file)"
        />
      </div>
      
      <!-- Pagination controls -->
      <div v-if="totalPages > 1" class="mt-6 flex items-center justify-center gap-2">
        <Button 
          variant="outline" 
          size="sm" 
          :disabled="currentPage === 1"
          @click="$emit('update:currentPage', Math.max(1, currentPage - 1))"
        >
          Ankstesnis
        </Button>
        
        <div class="flex items-center gap-1">
          <template v-for="page in visiblePages" :key="page">
            <Button
              v-if="typeof page === 'number'"
              :variant="page === currentPage ? 'default' : 'outline'"
              size="sm"
              @click="$emit('update:currentPage', page)"
              class="w-8 h-8 p-0"
            >
              {{ page }}
            </Button>
            <span v-else class="px-2 text-muted-foreground">...</span>
          </template>
        </div>
        
        <Button 
          variant="outline" 
          size="sm" 
          :disabled="currentPage === totalPages"
          @click="$emit('update:currentPage', Math.min(totalPages, currentPage + 1))"
        >
          Kitas
        </Button>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-16 px-8 text-center">
      <IFluentDocumentError24Regular class="h-12 w-12 text-zinc-300 dark:text-zinc-600 mb-4" />
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ search ? 'Failų nerasta' : 'Šis aplankas tuščias' }}
      </h3>
      <p class="text-zinc-500 dark:text-zinc-400 mb-6 max-w-sm">
        {{ search 
          ? `Pagal "${search}" paieškos kriterijų failų nerasta.`
          : selectionMode 
            ? 'Nėra failų šiame aplanke. Naršykite kitus aplankus arba kreipkitės į administratorių.'
            : 'Pradėkite nuo failų įkėlimo į šį aplanką.'
        }}
      </p>
      <div v-if="!selectionMode && !search" class="flex flex-wrap gap-2 justify-center">
        <Button size="sm" @click="$emit('showUploadMode')">
          <IFluentCloudArrowUp24Regular class="mr-2 h-4 w-4" />
          Įkelti failą
        </Button>
        <Button variant="outline" size="sm" @click="$emit('showCreateFolder')">
          <IFluentFolderAdd24Regular class="mr-2 h-4 w-4" />
          Sukurti aplanką
        </Button>
        <Button v-if="path !== 'public/files'" variant="outline" size="sm" @click="$emit('goBack')">
          <IFluentArrowLeft24Regular class="mr-2 h-4 w-4" />
          Grįžti atgal
        </Button>
        <Button v-if="path !== 'public/files'" variant="destructive" size="sm" @click="$emit('deleteFolder')">
          <IFluentDelete24Filled class="mr-2 h-4 w-4" />
          Ištrinti aplanką
        </Button>
      </div>
      <div v-else class="flex flex-wrap gap-2 justify-center">
        <Button v-if="search" variant="outline" size="sm" @click="$emit('clearSearch')">
          Išvalyti paiešką
        </Button>
        <Button v-if="path !== 'public/files'" variant="outline" size="sm" @click="$emit('goBack')">
          <IFluentArrowLeft24Regular class="mr-2 h-4 w-4" />
          Grįžti atgal
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import FileItem from './FileItem.vue';

// Import icons
import IFluentCheckmarkCircle24Regular from '~icons/fluent/checkmark-circle-24-regular';
import IFluentCircle24Regular from '~icons/fluent/circle-24-regular';
import IFluentDelete24Filled from '~icons/fluent/delete-24-filled';
import IFluentDocumentError24Regular from '~icons/fluent/document-error-24-regular';
import IFluentCloudArrowUp24Regular from '~icons/fluent/cloud-arrow-up-24-regular';
import IFluentFolderAdd24Regular from '~icons/fluent/folder-add-24-regular';
import IFluentArrowLeft24Regular from '~icons/fluent/arrow-left-24-regular';

const props = defineProps<{
  paginatedDirectories: any[];
  paginatedFiles: any[];
  selectedFile: string | null;
  selectedFiles: Set<string>;
  isMultiSelectMode: boolean;
  selectionMode?: boolean;
  isUploadMode: boolean;
  search: string;
  path: string;
  totalItems: number;
  itemsPerPage: number;
  currentPage: number;
  totalPages: number;
  visiblePages: (number | string)[];
}>();

const emit = defineEmits<{
  'update:itemsPerPage': [value: number];
  'update:currentPage': [value: number];
  'toggleMultiSelect': [];
  'selectAll': [];
  'clearSelection': [];
  'deleteSelected': [];
  'folderClick': [folder: any];
  'folderDoubleClick': [folder: any];
  'fileClick': [file: any, event?: MouseEvent];
  'fileDoubleClick': [file: any];
  'showUploadMode': [];
  'showCreateFolder': [];
  'goBack': [];
  'clearSearch': [];
  'deleteFolder': [];
}>();

const hasContent = computed(() => {
  return props.paginatedDirectories.length > 0 || props.paginatedFiles.length > 0;
});
</script>