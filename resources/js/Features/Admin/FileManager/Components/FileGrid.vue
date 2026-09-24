<template>
<!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
  <div
    class="relative min-h-[440px] flex flex-col bg-background"
    @dragover.prevent="dragOver = true"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="handleDrop"
  >
    <!-- Drag & Drop Overlay -->
    <div
      v-if="dragOver"
      class="pointer-events-none absolute inset-3 z-30 flex items-center justify-center border-2 border-dashed border-brand bg-background/90 backdrop-blur-xs"
    >
      <p class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-brand">
        <Upload class="size-5" aria-hidden="true" />
        {{ $t('Paleisk failus čia') }}
      </p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="p-4 flex-1">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
        <Skeleton v-for="i in 12" :key="i" class="aspect-[4/3] w-full rounded-none" />
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="paginatedFiles.length === 0"
      class="m-4 flex min-h-[360px] flex-1 flex-col items-center justify-center gap-3 border border-dashed border-border p-6 text-center"
    >
      <Folder class="size-12 text-muted-foreground/60" aria-hidden="true" />
      <h3 class="text-sm font-semibold text-foreground">
        {{ search ? $t('files.ui.no_files_found') : $t('files.ui.empty_folder') }}
      </h3>
      <p class="text-xs text-muted-foreground max-w-sm">
        {{
          search
            ? $t('files.ui.no_files_for_search', { search })
            : $t('files.ui.empty_folder_help')
        }}
      </p>
    </div>

    <!-- Files Container -->
    <div v-else class="p-4 flex-1">
      <!-- Grid View -->
      <div
        v-if="viewMode === 'grid'"
        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6"
      >
        <FileItem
          v-for="file in paginatedFiles"
          :key="file.path"
          :item="file"
          :is-selected="selectedFile === file.path"
          :is-multi-selected="selectedFiles.has(file.path)"
          :is-starred="starredFiles?.has(file.path)"
          :selection-mode="selectionMode"
          :is-multi-select-mode="isMultiSelectMode"
          @click="$emit('fileClick', file, $event)"
          @double-click="$emit('fileDoubleClick', file)"
          @toggle-select="$emit('toggleSelect', file)"
          @toggle-star="$emit('toggleStar', file)"
          @preview="$emit('previewFile', file)"
        />
      </div>

      <!-- List View (Table) -->
      <div v-else class="border border-border overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-border bg-secondary/40 text-[10px] font-bold uppercase tracking-[0.16em] text-muted-foreground">
            <tr>
              <th scope="col" class="w-10 px-3 py-2.5 text-center" />
              <th scope="col" class="px-4 py-2.5">
                {{ $t('files.ui.name') }}
              </th>
              <th scope="col" class="hidden sm:table-cell px-4 py-2.5 w-28 text-right">
                {{ $t('files.ui.size') }}
              </th>
              <th scope="col" class="hidden md:table-cell px-4 py-2.5 w-36 text-right">
                {{ $t('files.ui.modified') }}
              </th>
              <th scope="col" class="hidden lg:table-cell px-4 py-2.5 w-28 text-right">
                {{ $t('files.ui.type') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="file in paginatedFiles"
              :key="file.path"
              :class="[
                'cursor-pointer transition-colors',
                selectedFile === file.path || selectedFiles.has(file.path)
                  ? 'bg-brand/10'
                  : 'hover:bg-secondary/60',
              ]"
              @click="$emit('fileClick', file, $event)"
              @dblclick="$emit('fileDoubleClick', file)"
            >
              <!-- Checkbox -->
              <td class="w-10 px-3 py-2 text-center">
                <button
                  type="button"
                  :class="[
                    'inline-flex size-4 items-center justify-center border transition-colors',
                    selectedFiles.has(file.path) || selectedFile === file.path
                      ? 'border-brand bg-brand-fill text-brand-foreground'
                      : 'border-border',
                  ]"
                  :title="selectedFiles.has(file.path) || selectedFile === file.path ? $t('Atžymėti') : $t('Pažymėti')"
                  :aria-label="selectedFiles.has(file.path) || selectedFile === file.path ? $t('Atžymėti') : $t('Pažymėti')"
                  @click.stop="$emit('toggleSelect', file)"
                >
                  <Check v-if="selectedFiles.has(file.path) || selectedFile === file.path" class="size-3" aria-hidden="true" />
                </button>
              </td>

              <!-- Name & Icon -->
              <td class="px-4 py-2">
                <div class="flex items-center gap-2.5 min-w-0">
                  <component
                    :is="getFileIcon(file.name)"
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <span class="truncate text-sm font-medium text-foreground" :title="file.name">
                    {{ file.name }}
                  </span>
                  <button
                    v-if="starredFiles?.has(file.path)"
                    type="button"
                    class="text-status-attention fill-status-attention shrink-0"
                    :title="$t('Pašalinti iš pažymėtų')"
                    :aria-label="$t('Pašalinti iš pažymėtų')"
                    @click.stop="$emit('toggleStar', file)"
                  >
                    <Star class="size-3.5 fill-status-attention" aria-hidden="true" />
                  </button>
                </div>
              </td>

              <!-- Size -->
              <td class="hidden sm:table-cell px-4 py-2 text-right text-xs text-muted-foreground whitespace-nowrap">
                {{ formatBytes(file.size) }}
              </td>

              <!-- Modified -->
              <td class="hidden md:table-cell px-4 py-2 text-right text-xs text-muted-foreground whitespace-nowrap">
                {{ formatDate(file.modified) }}
              </td>

              <!-- Type -->
              <td class="hidden lg:table-cell px-4 py-2 text-right text-xs text-muted-foreground uppercase whitespace-nowrap">
                {{ file.name.split('.').pop() || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="totalItems > 0 && !loading"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-border px-4 py-3 bg-muted/20 text-xs text-muted-foreground"
    >
      <div class="flex items-center gap-2">
        <span>{{ $t('files.ui.show') }}</span>
        <select
          :value="itemsPerPage"
          class="border border-border bg-background px-2 py-1 text-xs text-foreground outline-none"
          @change="$emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value))"
        >
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
          <option :value="totalItems">{{ $t('files.ui.show_all') }}</option>
        </select>
        <span>{{ $t('iš') }} {{ totalItems }}</span>
      </div>

      <!-- Pagination Buttons -->
      <div v-if="totalPages > 1" class="flex items-center gap-1">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="inline-flex min-h-8 items-center border border-border px-2.5 font-medium transition-colors hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
          @click="$emit('update:currentPage', currentPage - 1)"
        >
          {{ $t('files.ui.previous') }}
        </button>

        <template v-for="(page, idx) in visiblePages" :key="idx">
          <span v-if="page === '...'" class="px-1 text-muted-foreground">...</span>
          <button
            v-else
            type="button"
            :class="[
              'inline-flex min-h-8 min-w-8 items-center justify-center border font-medium transition-colors',
              currentPage === page
                ? 'border-brand bg-brand-fill text-brand-foreground font-bold'
                : 'border-border hover:bg-secondary text-foreground',
            ]"
            @click="$emit('update:currentPage', Number(page))"
          >
            {{ page }}
          </button>
        </template>

        <button
          type="button"
          :disabled="currentPage >= totalPages"
          class="inline-flex min-h-8 items-center border border-border px-2.5 font-medium transition-colors hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
          @click="$emit('update:currentPage', currentPage + 1)"
        >
          {{ $t('files.ui.next') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, Folder, Star, Upload } from 'lucide-vue-next';

import FileItem from './FileItem.vue';
import type { FileEntry } from '../types';
import { formatBytes, formatDate } from '../utils';

import { Skeleton } from '@/Components/ui/skeleton';
import { getFileIcon } from '@/Utils/fileIcons';

const props = withDefaults(
  defineProps<{
    paginatedFiles: FileEntry[];
    selectedFile: string | null;
    selectedFiles: Set<string>;
    starredFiles?: Set<string>;
    isMultiSelectMode?: boolean;
    selectionMode?: boolean;
    loading?: boolean;
    viewMode: 'grid' | 'list';
    search?: string;
    totalItems: number;
    itemsPerPage: number;
    currentPage: number;
    totalPages: number;
    visiblePages: (number | string)[];
  }>(),
  {
    starredFiles: () => new Set<string>(),
    isMultiSelectMode: false,
    selectionMode: false,
    loading: false,
    search: '',
  },
);

const emit = defineEmits<{
  'fileClick': [file: FileEntry, event?: MouseEvent];
  'fileDoubleClick': [file: FileEntry];
  'toggleSelect': [file: FileEntry];
  'toggleStar': [file: FileEntry];
  'previewFile': [file: FileEntry];
  'filesDropped': [files: File[]];
  'update:currentPage': [page: number];
  'update:itemsPerPage': [items: number];
}>();

const dragOver = ref(false);

function onDragLeave(e: DragEvent) {
  if (!e.relatedTarget || (e.currentTarget as HTMLElement).contains(e.relatedTarget as Node)) {
    return;
  }
  dragOver.value = false;
}

function handleDrop(e: DragEvent) {
  dragOver.value = false;
  if (!e.dataTransfer?.files?.length) return;
  const files = Array.from(e.dataTransfer.files);
  emit('filesDropped', files);
}
</script>
