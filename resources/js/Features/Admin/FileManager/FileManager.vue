<template>
  <div class="space-y-4">
    <!-- Inline create-folder form in selection mode -->
    <div v-if="props.selectionMode && showFolderUploadModal" class="border border-border p-4 bg-muted/30">
      <div class="grid w-full max-w-sm items-center gap-1.5 mb-4">
        <Label for="folderNameInline">{{ $t('files.ui.new_folder_name') }}</Label>
        <Input id="folderNameInline" v-model="newFolderName" :placeholder="$t('files.ui.name_placeholder')" />
      </div>
      <div class="flex gap-2">
        <Button :disabled="loading" :data-loading="loading" @click="createDirectory">
          {{ $t('files.ui.create') }}
        </Button>
        <Button variant="outline" @click="showFolderUploadModal = false">
          {{ $t('files.ui.cancel') }}
        </Button>
      </div>
    </div>

    <!-- Main Container: 2-column on desktop (standalone), 1-column in selection mode/mobile -->
    <div
      :class="[
        'grid grid-cols-1 border border-border bg-border',
        !props.small && !props.selectionMode ? 'lg:grid-cols-[240px_1fr]' : '',
      ]"
    >
      <!-- Left Sidebar (Full Mode) -->
      <FileManagerSidebar
        v-if="!props.small && !props.selectionMode"
        :active-view="activeView"
        :directories="displayedDirectories"
        :current-path="props.path"
        :starred-count="starredSet.size"
        :files-count="totalItems"
        :total-size="totalSize"
        @update:active-view="activeView = $event"
        @open-folder="handleFolderClickByPath"
        @open-create-folder="openCreateFolder"
        @go-home="goHome"
        @go-up="handleBack"
      />

      <!-- Right Main Panel -->
      <section class="min-w-0 bg-background flex flex-col">
        <!-- Toolbar & Breadcrumb Sub-bar -->
        <FileManagerHeader
          :path="props.path"
          :search="search"
          :search-everywhere="searchEverywhere"
          :searching="props.searching"
          :is-upload-mode="isUploadMode"
          :selection-mode="props.selectionMode"
          :small="props.small"
          :allow-upload-in-selection="props.allowUploadInSelection"
          :type-filter="typeFilter"
          :sort-key="sortKey"
          :sort-dir="sortDir"
          :view-mode="viewMode"
          :total-items="totalItems"
          :selected-count="selectedFiles.size"
          :all-selected="allFilesSelected"
          :active-view="activeView"
          @update:search="search = $event"
          @update:search-everywhere="searchEverywhere = $event"
          @update:type-filter="typeFilter = $event"
          @update:sort-key="sortKey = $event"
          @update:sort-dir="sortDir = $event"
          @update:view-mode="viewMode = $event"
          @update:is-upload-mode="isUploadMode = $event"
          @navigate-to-path="navigateToPath"
          @show-create-folder="openCreateFolder"
          @toggle-select-all="toggleSelectAllFiles"
          @star-selected="toggleStarSelected"
          @download-selected="downloadSelected"
          @delete-selected="deleteSelectedFiles"
          @clear-selection="clearSelection"
        />

        <!-- Compact Folder Strip (in selection mode / small mode) -->
        <div v-if="(props.small || props.selectionMode) && !isRecursiveSearch" class="p-3 border-b border-border bg-muted/20">
          <FolderStrip
            :directories="displayedDirectories"
            :loading="props.listLoading"
            @open="handleFolderClick"
          />
        </div>

        <!-- Upload Mode -->
        <div v-if="isUploadMode && (!props.selectionMode || props.allowUploadInSelection)" class="p-4 flex-1">
          <FileUploadArea
            ref="uploadAreaRef"
            :loading="loading"
            :force-accept="!!props.uploadAccept || !!props.uploadExtensions"
            :accept="props.uploadAccept || '*'"
            :extensions="props.uploadExtensions"
            @upload="handleFileUpload"
            @files-selected="onFilesSelected"
          />
        </div>

        <!-- Browse Mode (Grid / List) -->
        <FileGrid
          v-else
          :paginated-files="paginatedFiles"
          :selected-file="selectedFile"
          :selected-files="selectedFiles"
          :starred-files="starredSet"
          :is-multi-select-mode="isMultiSelectMode"
          :selection-mode="props.selectionMode"
          :loading="props.listLoading || props.searching"
          :view-mode="viewMode"
          :search="search"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :current-page="currentPage"
          :totalPages="totalPages"
          :visible-pages="visiblePages"
          @file-click="handleFileClick"
          @file-double-click="handleFileDoubleClick"
          @toggle-select="handleToggleSelect"
          @toggle-star="handleToggleStar"
          @preview-file="openLightboxPreview"
          @files-dropped="handleFileUpload"
          @update:items-per-page="itemsPerPage = $event"
          @update:current-page="currentPage = $event"
        />
      </section>
    </div>

    <!-- Right Slide-Over File Detail Drawer -->
    <FilePropertiesDrawer
      :selected-file="selectedFile"
      :files="displayedFiles"
      :selection-mode="props.selectionMode"
      :is-starred="selectedFile ? starredSet.has(selectedFile) : false"
      @preview="openLightboxForSelected"
      @toggle-star="selectedFile ? handleToggleStar({ path: selectedFile } as FileEntry) : undefined"
      @insert="handleInsertSelected"
      @delete="selectedFile ? deleteFile(selectedFile) : undefined"
      @close="selectedFile = null"
    />

    <!-- Image Lightbox Modal -->
    <FilePreviewModal
      :file="previewTarget"
      @close="previewTarget = null"
    />

    <!-- Create Folder Dialog (Standalone) -->
    <Dialog v-if="!props.selectionMode" :open="showFolderUploadModal" @update:open="handleFolderDialogClose">
      <DialogContent class="sm:max-w-md rounded-none">
        <DialogHeader>
          <DialogTitle>{{ $t('files.ui.add_folder_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('files.ui.add_folder_description') }}
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="folderName">{{ $t('files.ui.new_folder_name') }}</Label>
            <Input
              id="folderName"
              v-model="newFolderName"
              :placeholder="$t('files.ui.folder_name_placeholder')"
              @keyup.enter="createDirectory"
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="handleFolderDialogClose(false)">
            {{ $t('files.ui.cancel') }}
          </Button>
          <Button
            :disabled="loading || !newFolderName.trim()"
            @click="createDirectory"
          >
            {{ $t('files.ui.create') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model:open="showDeleteModal"
      :title="getDeleteTitle()"
      :description="getDeleteMessage()"
      :confirm-label="$t('files.ui.delete')"
      destructive
      @confirm="deleteFileConfirmed"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { useFuse } from '@vueuse/integrations/useFuse';
import { trans as $t } from 'laravel-vue-i18n';

import FileGrid from './Components/FileGrid.vue';
import FileManagerHeader from './Components/FileManagerHeader.vue';
import FileManagerSidebar from './Components/FileManagerSidebar.vue';
import FilePreviewModal from './Components/FilePreviewModal.vue';
import FilePropertiesDrawer from './Components/FilePropertiesDrawer.vue';
import FolderStrip from './Components/FolderStrip.vue';
import type { ActiveView, DirectoryEntry, FileEntry, SortDir, SortKey, TypeFilter } from './types';
import { matchesTypeFilter } from './utils';

import FileUploadArea from '@/Components/FileUpload/FileUploadArea.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { uploadFiles, type UploadedFileResult } from '@/Composables/useFileUpload';
import { useToasts } from '@/Composables/useToasts';

const props = defineProps<{
  directories: DirectoryEntry[];
  files: FileEntry[];
  path: string;
  small?: boolean;
  /** Enable file selection mode */
  selectionMode?: boolean;
  /** Allow showing upload UI even in selection mode */
  allowUploadInSelection?: boolean;
  /** Optional accept string for uploads when in selection mode */
  uploadAccept?: string;
  /** Optional limited extensions for uploads when in selection mode */
  uploadExtensions?: string[];
  /** Whether the parent is still fetching the listing */
  listLoading?: boolean;
  /** Recursive search results, when the caller is searching every folder */
  searchResults?: FileEntry[] | null;
  /** Whether a recursive search is in flight */
  searching?: boolean;
}>();

const emit = defineEmits<{
  back: [];
  changeDirectory: [directory: string];
  fileSelected: [file: string, source: 'browse' | 'upload'];
  update: [path: string];
  search: [query: string, recursive: boolean];
}>();

const toasts = useToasts();

// State
const showFolderUploadModal = ref(false);
const showDeleteModal = ref(false);
const selectedFileForDeletion = ref('');
const newFolderName = ref('');
const loading = ref(false);
const selectedFile = ref<string | null>(null);
const selectedFiles = ref<Set<string>>(new Set());
const isMultiSelectMode = ref(false);
const isUploadMode = ref(false);
const uploadAreaRef = ref();

// Navigation & filtering state
const activeView = ref<ActiveView>('browse');
const typeFilter = ref<TypeFilter>('all');
const sortKey = ref<SortKey>('name');
const sortDir = ref<SortDir>('asc');
const search = ref('');
const searchEverywhere = ref(false);
const itemsPerPage = ref(50);
const currentPage = ref(1);
const viewMode = useStorage<'grid' | 'list'>('fileManager-viewMode', 'grid');

// Starred files in localStorage
const starredList = useStorage<string[]>('fileManager-starred', []);
const starredSet = computed(() => new Set(starredList.value));

// Lightbox preview target
const previewTarget = ref<FileEntry | null>(null);

// Fuse.js fuzzy search options
const fuseOptions = computed(() => ({
  fuseOptions: {
    keys: ['name'],
    threshold: 0.4,
  },
  matchAllWhenSearchEmpty: true,
}));

const { results: fileSearchResults } = useFuse(search, () => props.files ?? [], fuseOptions);
const { results: directorySearchResults } = useFuse(search, () => props.directories ?? [], fuseOptions);

const shownFiles = computed(() => {
  if (search.value === '') {
    return props.files ?? [];
  }
  return fileSearchResults.value.map(result => result.item);
});

const shownDirectories = computed(() => {
  if (search.value === '') {
    return props.directories ?? [];
  }
  return directorySearchResults.value.map(result => result.item);
});

const isRecursiveSearch = computed(() => searchEverywhere.value && search.value.trim().length >= 2);

const displayedDirectories = computed(() => (isRecursiveSearch.value ? [] : shownDirectories.value));

// Filter & sort files
const filteredAndSortedFiles = computed(() => {
  let list: FileEntry[] = isRecursiveSearch.value ? (props.searchResults ?? []) : shownFiles.value;

  // Active view filters
  if (activeView.value === 'starred') {
    list = list.filter(f => starredSet.value.has(f.path));
  }

  // Type filters
  if (typeFilter.value !== 'all') {
    list = list.filter(f => matchesTypeFilter(f, typeFilter.value));
  }

  // Sorting
  const dir = sortDir.value === 'asc' ? 1 : -1;
  const sorted = [...list].sort((a, b) => {
    if (activeView.value === 'recent') {
      return (b.modified - a.modified);
    }
    if (sortKey.value === 'name') {
      return a.name.localeCompare(b.name, 'lt') * dir;
    }
    if (sortKey.value === 'size') {
      return ((a.size || 0) - (b.size || 0)) * dir;
    }
    return ((a.modified || 0) - (b.modified || 0)) * dir;
  });

  return sorted;
});

const displayedFiles = computed(() => filteredAndSortedFiles.value);

const totalItems = computed(() => displayedFiles.value.length);

const totalSize = computed(() => (props.files ?? []).reduce((acc, f) => acc + (f.size || 0), 0));

const totalPages = computed(() => Math.max(1, Math.ceil(displayedFiles.value.length / itemsPerPage.value)));

const paginatedFiles = computed(() => {
  if (itemsPerPage.value >= displayedFiles.value.length) return displayedFiles.value;
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return displayedFiles.value.slice(start, start + itemsPerPage.value);
});

const visiblePages = computed(() => {
  const pages: (number | string)[] = [];
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i);
  }
  else {
    pages.push(1);
    if (current > 4) pages.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (current < total - 3) pages.push('...');
    if (total > 1) pages.push(total);
  }

  return pages;
});

const allFilesSelected = computed(() => {
  return displayedFiles.value.length > 0 && displayedFiles.value.every(f => selectedFiles.value.has(f.path));
});

// Watchers
watch([search, itemsPerPage, searchEverywhere, typeFilter, sortKey, sortDir, activeView], () => {
  currentPage.value = 1;
});

watch([search, searchEverywhere], ([query, recursive]) => {
  emit('search', query.trim(), recursive);
});

watch(isUploadMode, (newMode) => {
  if (newMode) {
    selectedFile.value = null;
    clearSelection();
    isMultiSelectMode.value = false;
    search.value = '';
    currentPage.value = 1;
  }
  else {
    currentPage.value = 1;
  }
});

// Methods exposed to parent
function openUpload() {
  isUploadMode.value = true;
}

function openCreateFolder() {
  showFolderUploadModal.value = true;
}

defineExpose({
  openUpload,
  openCreateFolder,
});

function handleFolderDialogClose(open: boolean) {
  showFolderUploadModal.value = open;
  if (!open) {
    newFolderName.value = '';
  }
}

function createDirectory() {
  loading.value = true;
  router.post(
    route('files.createDirectory'),
    { path: props.path, name: newFolderName.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toasts.success($t('files.ui.directory_created'));
        showFolderUploadModal.value = false;
        newFolderName.value = '';
        emit('update', props.path);
      },
      onFinish: () => {
        loading.value = false;
      },
    },
  );
}

function deleteFile(path: string) {
  selectedFileForDeletion.value = path;
  showDeleteModal.value = true;
}

function deleteFileConfirmed() {
  loading.value = true;

  if (selectedFileForDeletion.value.includes('|||')) {
    const filesToDelete = selectedFileForDeletion.value.split('|||');
    router.delete(route('files.bulkDelete'), {
      data: { paths: filesToDelete },
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        if (page.props.flash?.error) {
          toasts.error(page.props.flash.error);
        }
        else {
          toasts.success($t('files.ui.files_deleted', { count: String(filesToDelete.length) }));
          clearSelection();
          emit('update', props.path);
        }
      },
      onError: () => {
        toasts.error($t('files.ui.delete_files_error'));
      },
      onFinish: () => {
        loading.value = false;
        showDeleteModal.value = false;
      },
    });
  }
  else if (selectedFileForDeletion.value.startsWith('FOLDER:')) {
    const folderPath = selectedFileForDeletion.value.replace('FOLDER:', '');
    router.delete(
      route('files.deleteDirectory', { path: folderPath }),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          if (page.props.flash?.error) {
            toasts.error(page.props.flash.error);
          }
          else {
            toasts.success($t('files.ui.folder_deleted'));
            if (props.path === folderPath || props.path.startsWith(`${folderPath}/`)) {
              const parentPath = folderPath.split('/').slice(0, -1).join('/') || 'public/files';
              emit('changeDirectory', parentPath);
            }
            else {
              emit('update', props.path);
            }
          }
        },
        onError: () => {
          toasts.error($t('files.ui.delete_folder_error'));
        },
        onFinish: () => {
          loading.value = false;
          showDeleteModal.value = false;
        },
      },
    );
  }
  else {
    router.delete(
      route('files.delete', { path: selectedFileForDeletion.value }),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          if (page.props.flash?.error) {
            toasts.error(page.props.flash.error);
          }
          else {
            toasts.success($t('files.ui.file_deleted_short'));
            emit('update', props.path);
          }
        },
        onError: () => {
          toasts.error($t('files.ui.delete_file_error'));
        },
        onFinish: () => {
          loading.value = false;
          showDeleteModal.value = false;
        },
      },
    );
  }
}

function handleFolderClick(folder: DirectoryEntry) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  search.value = '';
  emit('changeDirectory', folder.path);
}

function handleFolderClickByPath(path: string) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  search.value = '';
  emit('changeDirectory', path);
}

function goHome() {
  activeView.value = 'browse';
  search.value = '';
  navigateToPath('public/files');
}

function handleBack() {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit('back');
}

function isSelectable(name: string): boolean {
  const allowed = props.uploadExtensions?.length
    ? props.uploadExtensions.map(e => e.toLowerCase())
    : null;
  if (!allowed) return true;

  const ext = name.includes('.') ? name.split('.').pop()?.toLowerCase() : undefined;
  return !!ext && allowed.includes(ext);
}

function handleFileClick(file: FileEntry, _event?: MouseEvent) {
  if (props.selectionMode) {
    if (!isSelectable(file?.name || file?.path || '')) {
      toasts.error($t('files.ui.cannot_select_file_type'));
      return;
    }
    selectedFile.value = file.path;
    emit('fileSelected', file.path, 'browse');
    return;
  }

  if (isMultiSelectMode.value) {
    handleToggleSelect(file);
    return;
  }

  selectedFile.value = file.path === selectedFile.value ? null : file.path;
}

function handleFileDoubleClick(file: FileEntry) {
  if (props.selectionMode) {
    if (!isSelectable(file?.name || file?.path || '')) {
      toasts.error($t('files.ui.cannot_select_file_type'));
      return;
    }
    selectedFile.value = file.path;
    emit('fileSelected', file.path, 'browse');
    return;
  }

  // If image, open lightbox preview
  if (/\.(jpg|jpeg|png|webp|gif|svg|avif)$/i.test(file.name)) {
    openLightboxPreview(file);
  }
  else {
    selectedFile.value = file.path;
  }
}

function handleToggleSelect(file: FileEntry) {
  if (selectedFiles.value.has(file.path)) {
    selectedFiles.value.delete(file.path);
  }
  else {
    selectedFiles.value.add(file.path);
  }
  selectedFiles.value = new Set(selectedFiles.value);
}

function handleToggleStar(file: FileEntry) {
  const path = file.path;
  if (starredSet.value.has(path)) {
    starredList.value = starredList.value.filter(p => p !== path);
  }
  else {
    starredList.value = [...starredList.value, path];
  }
}

function toggleStarSelected() {
  selectedFiles.value.forEach((path) => {
    if (starredSet.value.has(path)) {
      starredList.value = starredList.value.filter(p => p !== path);
    }
    else {
      starredList.value = [...starredList.value, path];
    }
  });
}

function openLightboxPreview(file: FileEntry) {
  previewTarget.value = file;
}

function openLightboxForSelected() {
  if (!selectedFile.value) return;
  const file = displayedFiles.value.find(f => f.path === selectedFile.value);
  if (file) {
    previewTarget.value = file;
  }
  else {
    const url = `/uploads/${selectedFile.value.replace(/^public\//, '')}`;
    window.open(url, '_blank');
  }
}

function handleInsertSelected() {
  if (!selectedFile.value) return;
  emit('fileSelected', selectedFile.value, 'browse');
}

function downloadSelected() {
  if (selectedFiles.value.size === 0) return;
  const firstPath = Array.from(selectedFiles.value)[0];
  const url = `/uploads/${firstPath.replace(/^public\//, '')}`;
  window.open(url, '_blank');
}

function navigateToPath(targetPath: string) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit('changeDirectory', targetPath);
}

function clearSelection() {
  selectedFiles.value = new Set();
}

function toggleSelectAllFiles() {
  if (allFilesSelected.value) {
    clearSelection();
  }
  else {
    const allPaths = displayedFiles.value.map(f => f.path);
    selectedFiles.value = new Set(allPaths);
  }
}

function deleteSelectedFiles() {
  if (selectedFiles.value.size === 0) return;
  const filesToDelete = Array.from(selectedFiles.value);
  selectedFileForDeletion.value = filesToDelete.join('|||');
  showDeleteModal.value = true;
}

function getFileName(filePath: string): string {
  return filePath.split('/').pop() || 'Unknown file';
}

function getDeleteTitle(): string {
  if (selectedFileForDeletion.value.startsWith('FOLDER:')) {
    return $t('files.ui.confirm_delete_folder_title');
  }
  if (selectedFileForDeletion.value.includes('|||')) {
    const fileCount = selectedFileForDeletion.value.split('|||').length;
    return $t('files.ui.confirm_delete_files_title', { count: String(fileCount) });
  }
  return $t('files.ui.confirm_delete_file_title');
}

function getDeleteMessage(): string {
  if (selectedFileForDeletion.value.startsWith('FOLDER:')) {
    const folderPath = selectedFileForDeletion.value.replace('FOLDER:', '');
    const folderName = folderPath.split('/').pop() || 'Unknown folder';
    return `${$t('files.ui.confirm_delete_folder_body', { name: folderName })}\n\n${$t('files.ui.confirm_delete_folder_note')}`;
  }
  if (selectedFileForDeletion.value.includes('|||')) {
    const fileCount = selectedFileForDeletion.value.split('|||').length;
    const fileList = selectedFileForDeletion.value.split('|||')
      .map(file => getFileName(file))
      .join(', ');
    return `${$t('files.ui.confirm_delete_files_body', { count: String(fileCount) })}\n\n${fileList}`;
  }
  const fileName = getFileName(selectedFileForDeletion.value);
  return `${$t('files.ui.confirm_delete_file_body', { name: fileName })}\n\n${$t('files.ui.confirm_delete_file_note')}`;
}

async function handleFileUpload(files: File[]) {
  loading.value = true;

  try {
    const result = await uploadFiles(files, props.path);

    if (result.failed.length > 0) {
      toasts.error($t('files.errors.upload_partial', { count: String(result.failed.length) }));
    }
    else if (result.message) {
      toasts.success(result.message);
    }

    if (result.uploaded.length > 0) {
      isUploadMode.value = false;
      uploadAreaRef.value?.clearFiles();
      emit('update', props.path);
      selectUploadedFile(result.uploaded);
    }
  }
  catch (error: unknown) {
    toasts.error(error instanceof Error ? error.message : $t('files.errors.upload_all_failed'));
  }
  finally {
    loading.value = false;
  }
}

function selectUploadedFile(uploaded: UploadedFileResult[]) {
  if (!props.selectionMode) return;

  const file = uploaded.find(candidate => isSelectable(candidate.name));
  if (!file) return;

  selectedFile.value = file.path;
  emit('fileSelected', file.path, 'upload');
}

function onFilesSelected(_files: File[]) {
  // downstream handler
}

// Global keyboard handlers
function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    if (previewTarget.value) {
      previewTarget.value = null;
      return;
    }
    if (selectedFile.value) {
      selectedFile.value = null;
      return;
    }
    if (selectedFiles.value.size > 0) {
      clearSelection();
    }
  }
  else if ((event.ctrlKey || event.metaKey) && event.key === 'a' && isMultiSelectMode.value) {
    event.preventDefault();
    toggleSelectAllFiles();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
[data-loading="true"]::before {
  content: "";
  display: inline-block;
  width: 1em;
  height: 1em;
  margin-right: 0.5rem;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  animation: spin 0.6s linear infinite;
  vertical-align: text-bottom;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
