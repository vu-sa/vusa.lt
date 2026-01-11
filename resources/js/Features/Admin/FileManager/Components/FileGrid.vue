<template>
  <div class="rounded-md border border-border shadow-xs">
    <!-- Files count and view controls -->
    <div v-if="hasContent && !loading" class="border-b border-border px-4 py-3 bg-muted/50">
      <div class="flex flex-col gap-3 sm:gap-4">
        <!-- Top row: count and main controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="text-sm text-muted-foreground">
            {{ totalItems }} {{ totalItems === 1 ? 'elementas' : 'elementai' }}
            <span v-if="search"> · filtruojama pagal "{{ search }}"</span>
            <span v-if="selectedFiles.size > 0"> · pasirinkta {{ selectedFiles.size }}</span>
          </div>
          
          <div class="flex items-center gap-4">
            <!-- View mode toggle -->
            <div v-if="!hideViewToggle" class="flex items-center gap-1 border border-border rounded-md p-0.5">
              <Button
                :variant="viewMode === 'grid' ? 'default' : 'ghost'"
                size="sm"
                class="h-7 w-7 p-0"
                @click="$emit('update:viewMode', 'grid')"
              >
                <IFluentGrid24Filled class="h-4 w-4" />
              </Button>
              <Button
                :variant="viewMode === 'list' ? 'default' : 'ghost'"
                size="sm"
                class="h-7 w-7 p-0"
                @click="$emit('update:viewMode', 'list')"
              >
                <IFluentAppsList20Filled class="h-4 w-4" />
              </Button>
            </div>
            
            <div class="flex items-center gap-2" aria-labelledby="items-per-page-label">
              <span id="items-per-page-label" class="text-sm text-muted-foreground">Rodyti:</span>
              <select
                class="text-sm border border-border rounded px-2 py-1 bg-background text-foreground"
                :value="itemsPerPage"
                @change="$emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value))"
              >
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
                <option :value="totalItems">Visus</option>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Bottom row: multi-select controls -->
        <div v-if="!selectionMode && !isUploadMode && !hideMultiSelect">
          <!-- Multi-select controls - responsive layout -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :class="{ 'bg-vusa-red text-white': isMultiSelectMode }"
              @click="$emit('toggleMultiSelect')"
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
    
    <!-- Loading skeleton -->
    <div v-if="loading" class="@container p-6">
      <div 
        class="grid gap-4
               grid-cols-2 @sm:grid-cols-3 @md:grid-cols-4 
               @lg:grid-cols-5 @xl:grid-cols-6 @2xl:grid-cols-7"
      >
        <Skeleton v-for="i in 8" :key="i" class="aspect-square rounded-md" />
      </div>
    </div>
    
    <!-- Files and folders grid/list -->
    <div v-else-if="hasContent" class="@container p-6">
      <!-- Grid View -->
      <div 
        v-if="viewMode === 'grid'"
  class="grid gap-4
               grid-cols-2 @sm:grid-cols-3 @md:grid-cols-4 
               @lg:grid-cols-5 @xl:grid-cols-6 @2xl:grid-cols-7 
               @3xl:grid-cols-8 @4xl:grid-cols-10"
      >
        <!-- Folders -->
        <FileItem
          v-for="folder in paginatedDirectories"
          :key="folder.id"
          :item="folder"
          :is-selected="false"
          :is-multi-selected="false"
          :selection-mode="selectionMode"
          :is-multi-select-mode="isMultiSelectMode"
          is-folder
          @click="$emit('folderClick', folder)"
          @double-click="$emit('folderDoubleClick', folder)"
        />
        
        <!-- Files -->
        <FileItem
          v-for="file in paginatedFiles"
          :key="file.id"
          :item="file"
          :is-selected="selectedFile === file.path || selectedFile === file.id"
          :is-multi-selected="selectedFiles.has(file.path)"
          :selection-mode="selectionMode"
          :is-multi-select-mode="isMultiSelectMode"
          @click="$emit('fileClick', file, $event)"
          @double-click="$emit('fileDoubleClick', file)"
        />
      </div>
      
      <!-- List View (Table) -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-border">
            <tr class="text-left text-muted-foreground">
              <th class="pb-2 font-medium w-10"></th>
              <th class="pb-2 font-medium">Pavadinimas</th>
              <th class="pb-2 font-medium w-24 text-right hidden sm:table-cell">Dydis</th>
              <th class="pb-2 font-medium w-36 text-right hidden md:table-cell">Modifikuotas</th>
            </tr>
          </thead>
          <tbody>
            <!-- Folders -->
            <tr
              v-for="folder in paginatedDirectories"
              :key="folder.id"
              class="border-b border-border/50 hover:bg-muted/50 cursor-pointer transition-colors"
              @click="$emit('folderClick', folder)"
              @dblclick="$emit('folderDoubleClick', folder)"
            >
              <td class="py-2 px-1">
                <IFluentFolder24Filled class="h-5 w-5 text-muted-foreground" />
              </td>
              <td class="py-2 font-medium">{{ folder.name }}</td>
              <td class="py-2 text-right text-muted-foreground hidden sm:table-cell">—</td>
              <td class="py-2 text-right text-muted-foreground hidden md:table-cell">—</td>
            </tr>
            
            <!-- Files -->
            <tr
              v-for="file in paginatedFiles"
              :key="file.id"
              :class="[
                'border-b border-border/50 cursor-pointer transition-colors',
                selectedFile === file.path ? 'bg-muted' : 'hover:bg-muted/50',
                selectedFiles.has(file.path) ? 'bg-vusa-red/5' : ''
              ]"
              @click="$emit('fileClick', file, $event)"
              @dblclick="$emit('fileDoubleClick', file)"
            >
              <td class="py-2 px-1">
                <component :is="getFileIcon(file)" class="h-5 w-5 text-muted-foreground" />
              </td>
              <td class="py-2">{{ file.name }}</td>
              <td class="py-2 text-right text-muted-foreground hidden sm:table-cell">{{ formatFileSize(file.size) }}</td>
              <td class="py-2 text-right text-muted-foreground hidden md:table-cell">{{ formatDate(file.modified) }}</td>
            </tr>
          </tbody>
        </table>
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
              class="w-8 h-8 p-0"
              @click="$emit('update:currentPage', page)"
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
import { computed, type Component } from 'vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import FileItem from './FileItem.vue';

// Import icons
import IFluentCheckmarkCircle24Regular from '~icons/fluent/checkmark-circle-24-regular';
import IFluentCircle24Regular from '~icons/fluent/circle-24-regular';
import IFluentDelete24Filled from '~icons/fluent/delete-24-filled';
import IFluentDocumentError24Regular from '~icons/fluent/document-error-24-regular';
import IFluentCloudArrowUp24Regular from '~icons/fluent/cloud-arrow-up-24-regular';
import IFluentFolderAdd24Regular from '~icons/fluent/folder-add-24-regular';
import IFluentArrowLeft24Regular from '~icons/fluent/arrow-left-24-regular';
import IFluentGrid24Filled from '~icons/fluent/grid-24-filled';
import IFluentAppsList20Filled from '~icons/fluent/apps-list-20-filled';
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';
import IFluentDocument24Regular from '~icons/fluent/document-24-regular';
import IFluentDocumentPdf24Regular from '~icons/fluent/document-pdf-24-regular';
import IFluentDocumentText24Regular from '~icons/fluent/document-text-24-regular';
import IFluentImage24Regular from '~icons/fluent/image-24-regular';

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
  viewMode?: 'grid' | 'list';
  loading?: boolean;
  hideMultiSelect?: boolean;
  hideViewToggle?: boolean;
}>();

const emit = defineEmits<{
  'update:itemsPerPage': [value: number];
  'update:currentPage': [value: number];
  'update:viewMode': [value: 'grid' | 'list'];
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

const viewMode = computed(() => props.viewMode ?? 'grid');

const hasContent = computed(() => {
  return props.paginatedDirectories.length > 0 || props.paginatedFiles.length > 0;
});

// Helper functions for list view
function getFileExtension(path: string): string {
  if (!path) return '';
  const parts = path.split('.');
  return parts.length > 1 ? parts.pop() ?? '' : '';
}

function getFileIcon(file: any): Component {
  const ext = getFileExtension(file.path || file.name || '').toLowerCase();
  
  if (['pdf'].includes(ext)) {
    return IFluentDocumentPdf24Regular;
  }
  if (['doc', 'docx', 'txt', 'rtf', 'odt'].includes(ext)) {
    return IFluentDocumentText24Regular;
  }
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) {
    return IFluentImage24Regular;
  }
  return IFluentDocument24Regular;
}

function formatFileSize(bytes?: number): string {
  if (!bytes) return '—';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(timestamp?: number): string {
  if (!timestamp) return '—';
  const date = new Date(timestamp * 1000);
  return date.toLocaleDateString('lt-LT');
}
</script>