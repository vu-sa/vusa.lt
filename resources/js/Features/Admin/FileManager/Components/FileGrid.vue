<template>
  <div class="border border-border">
    <!-- Files count and view controls -->
    <div v-if="hasContent && !loading" class="border-b border-border px-4 py-3 bg-muted/50">
      <div class="flex flex-col gap-3 sm:gap-4">
        <!-- Top row: count and main controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="text-sm text-muted-foreground">
            {{ $t('files.ui.files_count', { count: String(totalItems) }) }}
            <span v-if="search"> · {{ $t('files.ui.filtered_by', { search }) }}</span>
            <span v-if="selectedFiles.size > 0"> · {{ $t('files.ui.selected_count', { count: String(selectedFiles.size) }) }}</span>
          </div>

          <div class="flex items-center gap-4">
            <!-- View mode toggle -->
            <div v-if="!hideViewToggle" class="flex items-center gap-1 border border-border p-0.5">
              <Button
                :variant="viewMode === 'grid' ? 'default' : 'ghost'"
                size="sm"
                class="h-7 w-7 p-0"
                @click="$emit('update:viewMode', 'grid')"
              >
                <LayoutGrid class="h-4 w-4" />
              </Button>
              <Button
                :variant="viewMode === 'list' ? 'default' : 'ghost'"
                size="sm"
                class="h-7 w-7 p-0"
                @click="$emit('update:viewMode', 'list')"
              >
                <List class="h-4 w-4" />
              </Button>
            </div>

            <div class="flex items-center gap-2" aria-labelledby="items-per-page-label">
              <span id="items-per-page-label" class="text-sm text-muted-foreground">{{ $t('files.ui.show') }}</span>
              <select
                class="text-sm border border-border px-2 py-1 bg-background text-foreground"
                :value="itemsPerPage"
                @change="$emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value))"
              >
                <option :value="25">
                  25
                </option>
                <option :value="50">
                  50
                </option>
                <option :value="100">
                  100
                </option>
                <option :value="totalItems">
                  {{ $t('files.ui.show_all') }}
                </option>
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
              :class="{ 'bg-brand text-brand-foreground': isMultiSelectMode }"
              @click="$emit('toggleMultiSelect')"
            >
              <CheckCircle2 v-if="isMultiSelectMode" class="h-4 w-4 mr-1" />
              <Circle v-else class="h-4 w-4 mr-1" />
              {{ isMultiSelectMode ? $t('files.ui.finish_selection') : $t('files.ui.select_multiple') }}
            </Button>

            <!-- Bulk actions - separate row on mobile -->
            <div v-if="isMultiSelectMode && selectedFiles.size > 0" class="flex flex-wrap items-center gap-1 sm:gap-2">
              <Button variant="outline" size="sm" @click="$emit('selectAll')">
                <CheckCircle2 class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">{{ $t('files.ui.select_all') }}</span>
              </Button>
              <Button variant="outline" size="sm" @click="$emit('clearSelection')">
                <Circle class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">{{ $t('files.ui.clear') }}</span>
              </Button>
              <Button variant="destructive" size="sm" @click="$emit('deleteSelected')">
                <Trash2 class="h-4 w-4 sm:mr-1" />
                <span class="hidden sm:inline">{{ $t('files.ui.delete') }}</span>
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
        <Skeleton v-for="i in 8" :key="i" class="aspect-square" />
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
        <FileItem
          v-for="file in paginatedFiles"
          :key="file.path"
          :item="file"
          :is-selected="selectedFile === file.path"
          :is-multi-selected="selectedFiles.has(file.path)"
          :selection-mode
          :is-multi-select-mode
          @click="$emit('fileClick', file, $event)"
          @double-click="$emit('fileDoubleClick', file)"
        />
      </div>

      <!-- List View (Table) -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-border">
            <tr class="text-left text-muted-foreground">
              <th class="pb-2 font-medium w-10" />
              <th class="pb-2 font-medium">
                {{ $t('files.ui.name') }}
              </th>
              <th class="pb-2 font-medium w-24 text-right hidden sm:table-cell">
                {{ $t('files.ui.size') }}
              </th>
              <th class="pb-2 font-medium w-36 text-right hidden md:table-cell">
                {{ $t('files.ui.modified') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="file in paginatedFiles"
              :key="file.path"
              :class="[
                'border-b border-border/50 cursor-pointer transition-colors',
                selectedFile === file.path ? 'bg-muted' : 'hover:bg-muted/50',
                selectedFiles.has(file.path) ? 'bg-brand/5' : '',
              ]"
              @click="$emit('fileClick', file, $event)"
              @dblclick="$emit('fileDoubleClick', file)"
            >
              <td class="py-2 px-1">
                <component :is="getFileIcon(file.name)" class="h-5 w-5 text-muted-foreground" />
              </td>
              <td class="py-2">
                {{ file.name }}
                <span v-if="showDirectory && file.directory" class="block text-xs text-muted-foreground">
                  {{ displayDirectory(file.directory) }}
                </span>
              </td>
              <td class="py-2 text-right text-muted-foreground hidden sm:table-cell">
                {{ formatFileSize(file.size) }}
              </td>
              <td class="py-2 text-right text-muted-foreground hidden md:table-cell">
                {{ formatDate(file.modified) }}
              </td>
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
          {{ $t('files.ui.previous') }}
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
          {{ $t('files.ui.next') }}
        </Button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-16 px-8 text-center">
      <FolderX class="h-12 w-12 text-muted-foreground/60 mb-4" />
      <h3 class="text-lg font-medium text-foreground mb-2">
        {{ search
          ? $t('files.ui.no_files_found')
          : hasFolders ? $t('files.ui.no_files_here') : $t('files.ui.empty_folder')
        }}
      </h3>
      <p class="text-muted-foreground mb-6 max-w-sm">
        {{ search
          ? $t('files.ui.no_files_for_search', { search })
          : hasFolders
            ? $t('files.ui.no_files_here_help', { count: String(folderCount) })
            : selectionMode
              ? $t('files.ui.empty_folder_selection_help')
              : $t('files.ui.empty_folder_help')
        }}
      </p>
      <div v-if="!selectionMode && !search" class="flex flex-wrap gap-2 justify-center">
        <Button size="sm" @click="$emit('showUploadMode')">
          <Upload class="mr-2 h-4 w-4" />
          {{ $t('files.ui.upload_file') }}
        </Button>
        <Button variant="outline" size="sm" @click="$emit('showCreateFolder')">
          <FolderPlus class="mr-2 h-4 w-4" />
          {{ $t('files.ui.create_folder') }}
        </Button>
        <Button v-if="path !== 'public/files'" variant="outline" size="sm" @click="$emit('goBack')">
          <ArrowLeft class="mr-2 h-4 w-4" />
          {{ $t('files.ui.go_back') }}
        </Button>
        <Button
          v-if="path !== 'public/files'"
          variant="destructive"
          size="sm"
          :disabled="hasFolders"
          :title="hasFolders ? $t('files.ui.delete_folder_blocked', { count: String(folderCount) }) : undefined"
          @click="$emit('deleteFolder')"
        >
          <Trash2 class="mr-2 h-4 w-4" />
          {{ $t('files.ui.delete_folder') }}
        </Button>
      </div>
      <!-- The server refuses to delete a non-empty folder, and with the folder strip collapsed
           there was nothing on screen explaining why the button did nothing. -->
      <p
        v-if="hasFolders && path !== 'public/files' && !selectionMode && !search"
        class="mt-3 text-xs text-muted-foreground"
      >
        {{ $t('files.ui.delete_folder_blocked', { count: String(folderCount) }) }}
      </p>
      <div v-else class="flex flex-wrap gap-2 justify-center">
        <Button v-if="search" variant="outline" size="sm" @click="$emit('clearSearch')">
          {{ $t('files.ui.clear_search') }}
        </Button>
        <Button v-if="path !== 'public/files'" variant="outline" size="sm" @click="$emit('goBack')">
          <ArrowLeft class="mr-2 h-4 w-4" />
          {{ $t('files.ui.go_back') }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  ArrowLeft,
  CheckCircle2,
  Circle,
  FolderPlus,
  FolderX,
  LayoutGrid,
  List,
  Trash2,
  Upload,
} from 'lucide-vue-next';

import type { FileEntry } from '../types';

import FileItem from './FileItem.vue';

import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatFileSize, getFileIcon } from '@/Utils/fileIcons';

const props = defineProps<{
  paginatedFiles: FileEntry[];
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
  /** Render each file's parent folder — recursive search spans directories. */
  showDirectory?: boolean;
  /** How many subfolders the strip above is showing, so "empty" is not a lie. */
  folderCount?: number;
}>();

defineEmits<{
  'update:itemsPerPage': [value: number];
  'update:currentPage': [value: number];
  'update:viewMode': [value: 'grid' | 'list'];
  'toggleMultiSelect': [];
  'selectAll': [];
  'clearSelection': [];
  'deleteSelected': [];
  'fileClick': [file: FileEntry, event?: MouseEvent];
  'fileDoubleClick': [file: FileEntry];
  'showUploadMode': [];
  'showCreateFolder': [];
  'goBack': [];
  'clearSearch': [];
  'deleteFolder': [];
}>();

const viewMode = computed(() => props.viewMode ?? 'grid');

const folderCount = computed(() => props.folderCount ?? 0);

const hasFolders = computed(() => folderCount.value > 0);

const hasContent = computed(() => {
  return props.paginatedFiles.length > 0;
});

/** Recursive hits come from anywhere in the tree; show the folder, not the storage prefix. */
function displayDirectory(directory: string): string {
  return directory.replace(/^public\/files\/?/, '') || '/';
}

function formatDate(timestamp?: number): string {
  if (!timestamp) return '—';
  const date = new Date(timestamp * 1000);
  return date.toLocaleDateString('lt-LT');
}
</script>
