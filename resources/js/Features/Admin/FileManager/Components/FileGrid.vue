<template>
  <!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
  <div
    class="relative flex min-h-[440px] flex-1 flex-col bg-background"
    data-slot="file-grid"
    @dragover.prevent="dragOver = acceptsDrop"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="handleDrop"
  >
    <div
      v-if="dragOver"
      class="pointer-events-none absolute inset-2 z-30 flex items-start justify-center border-2 border-dashed border-brand pt-6"
    >
      <p class="flex items-center gap-2 bg-background px-3 py-2 text-sm font-bold text-brand">
        <Upload class="size-4" aria-hidden="true" />
        {{ $t('files.ui.drop_here', { folder: folderName }) }}
      </p>
    </div>

    <div v-if="loading" class="p-4" data-testid="file-grid-skeleton">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
        <Skeleton v-for="i in 12" :key="i" class="aspect-[4/3] w-full rounded-none" />
      </div>
    </div>

    <template v-else>
      <FolderStrip
        v-if="folders.length > 0"
        :directories="folders"
        :actions-for="folderActionsFor"
        :accepts-drop
        @open="$emit('openFolder', $event)"
        @action="(key, dir) => $emit('folderAction', key, dir)"
        @drop="(dir, dropped) => $emit('dropFiles', dropped, dir.path)"
      />

      <EmptyState
        v-if="files.length === 0"
        :mode="filtered ? 'no-results' : 'empty'"
        :icon="filtered ? undefined : FolderOpen"
        :title="emptyTitle"
        :description="emptyDescription"
        :clear-label="filtered ? $t('files.ui.clear_filters') : undefined"
        class="flex-1"
        @clear="$emit('clearFilters')"
      />

      <div v-else class="flex-1 p-4">
        <ul
          v-if="view === 'cards'"
          class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6"
          :aria-label="$t('files.ui.files')"
        >
          <li v-for="file in files" :key="file.path">
            <FileItem
              :item="file"
              :selected="selectedFile === file.path"
              :checked="checked.has(file.path)"
              :starred="starred.has(file.path)"
              :show-checkbox="checked.size > 0"
              :selection-mode
              :can-preview="canPreview(file)"
              :actions="fileActionsFor?.(file)"
              @click="$emit('fileClick', file, $event)"
              @dblclick="$emit('fileDblclick', file)"
              @toggle-check="$emit('toggleCheck', file, $event)"
              @preview="$emit('preview', file)"
              @action="$emit('fileAction', $event, file)"
            />
          </li>
        </ul>

        <div v-else class="border border-border">
          <table class="w-full table-fixed text-left text-sm">
            <thead class="border-b border-border bg-secondary/40 text-[11px] font-bold uppercase tracking-[0.12em] text-muted-foreground">
              <tr>
                <th v-if="!selectionMode" scope="col" class="w-11 px-3 py-2">
                  <Checkbox
                    :model-value="allChecked ? true : checked.size > 0 ? 'indeterminate' : false"
                    :aria-label="$t('files.ui.select_all')"
                    @update:model-value="$emit('toggleCheckAll')"
                  />
                </th>
                <th scope="col" class="px-3 py-2">
                  {{ $t('files.ui.name') }}
                </th>
                <th scope="col" class="hidden w-24 px-3 py-2 text-right sm:table-cell">
                  {{ $t('files.ui.size') }}
                </th>
                <th scope="col" class="hidden w-32 px-3 py-2 text-right md:table-cell">
                  {{ $t('files.ui.modified') }}
                </th>
                <th v-if="fileActionsFor" scope="col" class="w-12 px-2 py-2">
                  <span class="sr-only">{{ $t('files.ui.actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="file in files"
                :key="file.path"
                :aria-selected="checked.has(file.path) || selectedFile === file.path"
                :class="[
                  'cursor-pointer transition-colors',
                  checked.has(file.path) || selectedFile === file.path ? 'bg-brand/5' : 'hover:bg-secondary/60',
                ]"
                @click="$emit('fileClick', file, $event)"
                @dblclick="$emit('fileDblclick', file)"
              >
                <td v-if="!selectionMode" class="px-3 py-1.5" @click.stop="$emit('toggleCheck', file, $event)">
                  <Checkbox
                    :model-value="checked.has(file.path)"
                    :aria-label="checked.has(file.path) ? $t('files.ui.uncheck', { name: file.name }) : $t('files.ui.check', { name: file.name })"
                  />
                </td>
                <td class="px-3 py-1.5">
                  <button
                    type="button"
                    class="flex w-full min-w-0 items-center gap-3 text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40"
                    @click.stop="$emit('fileClick', file, $event)"
                  >
                    <FileThumb
                      :path="file.path"
                      :name="file.name"
                      :width="160"
                      icon-class="size-4 text-muted-foreground"
                      class="size-10 shrink-0 border border-border"
                    />
                    <span class="min-w-0 flex-1">
                      <span class="flex items-center gap-1.5">
                        <span class="truncate font-medium text-foreground" :title="file.name">{{ file.name }}</span>
                        <Star v-if="starred.has(file.path)" class="size-3 shrink-0 fill-status-attention text-status-attention" :aria-label="$t('files.ui.starred')" />
                      </span>
                      <span class="block truncate text-xs text-muted-foreground">
                        <span class="uppercase">{{ getFileExtension(file.name) || '—' }}</span>
                        <span class="sm:hidden"> · {{ formatBytes(file.size) }}</span>
                        <template v-if="file.directory"> · {{ relativeToRoot(file.directory) || $t('files.ui.root') }}</template>
                      </span>
                    </span>
                  </button>
                </td>
                <td class="hidden px-3 py-1.5 text-right text-xs whitespace-nowrap text-muted-foreground tabular-nums sm:table-cell">
                  {{ formatBytes(file.size) }}
                </td>
                <td class="hidden px-3 py-1.5 text-right text-xs whitespace-nowrap text-muted-foreground tabular-nums md:table-cell">
                  {{ formatDate(file.modified) }}
                </td>
                <td v-if="fileActionsFor" class="px-2 py-1.5 text-right" @click.stop>
                  <ItemActionsMenu :label="file.name" :actions="fileActionsFor(file)" @select="$emit('fileAction', $event, file)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <CollectionLoadMore :has-more :loading="false" @load="$emit('loadMore')" />
        <p v-if="totalFiles > 0" class="pt-4 text-center text-xs text-muted-foreground tabular-nums">
          {{ $t('files.ui.shown_of', { shown: String(files.length), total: String(totalFiles) }) }}
        </p>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { FolderOpen, Star, Upload } from '@lucide/vue';

import type { DirectoryEntry, FileEntry, FileViewMode, ItemAction } from '../types';
import { formatBytes, formatDate, relativeToRoot } from '../utils';

import FileItem from './FileItem.vue';
import FileThumb from './FileThumb.vue';
import FolderStrip from './FolderStrip.vue';
import ItemActionsMenu from './ItemActionsMenu.vue';

import CollectionLoadMore from '@/Components/Collection/CollectionLoadMore.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import { Checkbox } from '@/Components/ui/checkbox';
import { Skeleton } from '@/Components/ui/skeleton';
import { getFileExtension } from '@/Utils/fileIcons';

type FolderLike = Pick<DirectoryEntry, 'path' | 'name' | 'items'>;

const props = withDefaults(defineProps<{
  files: FileEntry[];
  totalFiles: number;
  hasMore?: boolean;
  folders?: DirectoryEntry[];
  view: FileViewMode;
  selectedFile: string | null;
  checked: Set<string>;
  starred?: Set<string>;
  allChecked?: boolean;
  selectionMode?: boolean;
  loading?: boolean;
  /** A search or type filter is narrowing the list. */
  filtered?: boolean;
  acceptsDrop?: boolean;
  folderName?: string;
  canPreview?: (file: FileEntry) => boolean;
  fileActionsFor?: (file: FileEntry) => ItemAction[];
  folderActionsFor?: (directory: FolderLike) => { actions: ItemAction[]; note?: string };
}>(), {
  folders: () => [],
  starred: () => new Set<string>(),
  folderName: '',
  canPreview: () => false,
  fileActionsFor: undefined,
  folderActionsFor: undefined,
});

const emit = defineEmits<{
  fileClick: [file: FileEntry, event: MouseEvent];
  fileDblclick: [file: FileEntry];
  toggleCheck: [file: FileEntry, event: MouseEvent];
  toggleCheckAll: [];
  preview: [file: FileEntry];
  fileAction: [key: string, file: FileEntry];
  openFolder: [directory: FolderLike];
  folderAction: [key: string, directory: FolderLike];
  dropFiles: [files: File[], directory?: string];
  loadMore: [];
  clearFilters: [];
}>();

const dragOver = ref(false);

const emptyTitle = computed(() => {
  if (props.filtered) return $t('files.ui.no_files_found');
  return props.folders.length > 0 ? $t('files.ui.no_files_here') : $t('files.ui.empty_folder');
});

const emptyDescription = computed(() => {
  if (props.filtered) return $t('files.ui.no_files_filtered_help');
  if (props.selectionMode) return $t('files.ui.empty_folder_selection_help');
  return props.acceptsDrop ? $t('files.ui.empty_folder_help') : undefined;
});

function onDragLeave(event: DragEvent) {
  const target = event.relatedTarget as Node | null;
  if (target && (event.currentTarget as HTMLElement).contains(target)) {
    return;
  }
  dragOver.value = false;
}

function handleDrop(event: DragEvent) {
  dragOver.value = false;
  const files = Array.from(event.dataTransfer?.files ?? []);
  if (props.acceptsDrop && files.length > 0) {
    emit('dropFiles', files);
  }
}
</script>
