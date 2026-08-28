<template>
  <div>
    <!-- Header with toolbar and breadcrumbs -->
    <FileManagerHeader
      :path="props.path"
      :search
      :search-everywhere
      :searching="props.searching"
      :is-upload-mode
      :selection-mode="props.selectionMode"
      :small="props.small"
      :allow-upload-in-selection="props.allowUploadInSelection"
      @update:search="search = $event"
      @update:search-everywhere="searchEverywhere = $event"
      @update:is-upload-mode="isUploadMode = $event"
      @navigate-to-path="navigateToPath"
      @show-create-folder="showFolderUploadModal = true"
    />

    <!-- Inline create-folder form in selection mode (moved near top for visibility) -->
    <div v-if="props.selectionMode && showFolderUploadModal" class="mt-4 border rounded-md p-4 bg-muted/30">
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

    <!-- Upload Mode -->
    <div v-if="isUploadMode && (!props.selectionMode || props.allowUploadInSelection)" class="mt-4">
      <FileUploadArea
        ref="uploadAreaRef"
        :loading
        :force-accept="!!props.uploadAccept || !!props.uploadExtensions"
        :accept="props.uploadAccept || '*'"
        :extensions="props.uploadExtensions"
        @upload="handleFileUpload"
        @files-selected="onFilesSelected"
      />
    </div>

    <!-- Browse Mode -->
    <div v-else class="mt-4 space-y-4">
      <!-- Folders live in their own collapsible section so a directory with dozens of them
           cannot push the files it contains off the first page. -->
      <FolderStrip
        v-if="!isRecursiveSearch"
        :directories="displayedDirectories"
        :loading="props.listLoading"
        @open="handleFolderClick"
      />

      <!-- Main file browser - fixed width, no layout shifts -->
      <FileGrid
        :paginated-files
        :selected-file
        :selected-files
        :is-multi-select-mode
        :selection-mode="props.selectionMode"
        :is-upload-mode
        :search
        :path="props.path"
        :total-items
        :items-per-page
        :current-page
        :total-pages
        :visible-pages
        :view-mode
        :loading="props.listLoading || props.searching"
        :show-directory="isRecursiveSearch"
        :has-folders="displayedDirectories.length > 0"
        @update:items-per-page="itemsPerPage = $event"
        @update:current-page="currentPage = $event"
        @update:view-mode="viewMode = $event"
        @toggle-multi-select="toggleMultiSelectMode"
        @select-all="selectAllFiles"
        @clear-selection="clearSelection"
        @delete-selected="deleteSelectedFiles"
        @file-click="handleFileClick"
        @file-double-click="handleFileDoubleClick"
        @show-upload-mode="isUploadMode = true"
        @show-create-folder="showFolderUploadModal = true"
        @go-back="handleBack"
        @clear-search="search = ''"
        @delete-folder="handleDeleteFolder"
      />

      <!-- Properties Bottom Drawer -->
      <FilePropertiesDrawer
        v-if="!props.selectionMode"
        :selected-file
        :files="displayedFiles"
        @preview="previewFile(selectedFile!)"
        @delete="deleteFile(selectedFile!)"
        @close="selectedFile = null"
      />
    </div>

    <!-- Modals -->
    <!-- Create Folder Dialog -->
    <Dialog v-if="!props.selectionMode" :open="showFolderUploadModal" @update:open="handleFolderDialogClose">
      <DialogContent class="sm:max-w-md">
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
    <DeleteConfirmationDialog
      :is-open="showDeleteModal"
      :title="getDeleteTitle()"
      :message="getDeleteMessage()"
      :is-deleting="loading"
      @update:open="showDeleteModal = $event"
      @confirm="deleteFileConfirmed"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useFuse } from '@vueuse/integrations/useFuse';
import { useStorage } from '@vueuse/core';

import FileManagerHeader from './Components/FileManagerHeader.vue';
import FolderStrip from './Components/FolderStrip.vue';
import FileGrid from './Components/FileGrid.vue';
import FilePropertiesDrawer from './Components/FilePropertiesDrawer.vue';

import { useToasts } from '@/Composables/useToasts';
import { uploadFiles } from '@/Composables/useFileUpload';

// Components
import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';
import FileUploadArea from '@/Components/FileUpload/FileUploadArea.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

// Custom components

// Types

const props = defineProps<{
  directories: any;
  files: any;
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
  searchResults?: any[] | null;
  /** Whether a recursive search is in flight */
  searching?: boolean;
}>();

const emit = defineEmits<{
  back: [];
  changeDirectory: [directory: string];
  fileSelected: [file: string];
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
const search = ref('');
const searchEverywhere = ref(false);
const itemsPerPage = ref(50);
const currentPage = ref(1);
const viewMode = useStorage<'grid' | 'list'>('fileManager-viewMode', 'grid');

// Fuse.js fuzzy search options
const fuseOptions = computed(() => ({
  fuseOptions: {
    keys: ['name'],
    threshold: 0.4, // Allow some fuzzy matching
  },
  matchAllWhenSearchEmpty: true,
}));

// Fuzzy search for files
const { results: fileSearchResults } = useFuse(search, () => props.files ?? [], fuseOptions);

// Fuzzy search for directories
const { results: directorySearchResults } = useFuse(search, () => props.directories ?? [], fuseOptions);

// Computed properties - use fuzzy search results
const shownFiles = computed(() => {
  if (search.value === '') {
    return props.files;
  }
  return fileSearchResults.value.map(result => result.item);
});

const shownDirectories = computed(() => {
  if (search.value === '') {
    return props.directories;
  }
  return directorySearchResults.value.map(result => result.item);
});

/**
 * Recursive results replace the local listing entirely: they come from the server already
 * filtered, and their parent folders are what the user is looking for.
 */
const isRecursiveSearch = computed(() => searchEverywhere.value && search.value.trim().length >= 2);

const displayedFiles = computed(() => (isRecursiveSearch.value ? (props.searchResults ?? []) : shownFiles.value));

const displayedDirectories = computed(() => (isRecursiveSearch.value ? [] : shownDirectories.value));

const totalItems = computed(() => displayedFiles.value.length);

// Folders and files are paginated separately. They used to share one stream with folders
// first, so a root holding ~50 folders spent the whole first page on them and pushed every
// file to page 2 and beyond.
const totalPages = computed(() => Math.max(1, Math.ceil(displayedFiles.value.length / itemsPerPage.value)));

const paginatedFiles = computed(() => {
  if (itemsPerPage.value >= displayedFiles.value.length) return displayedFiles.value;

  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  return displayedFiles.value.slice(startIndex, startIndex + itemsPerPage.value);
});

const visiblePages = computed(() => {
  const pages: (number | string)[] = [];
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    // Show all pages if 7 or fewer
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  }
  else {
    // Always show first page
    pages.push(1);

    if (current > 4) {
      pages.push('...');
    }

    // Show pages around current
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    if (current < total - 3) {
      pages.push('...');
    }

    // Always show last page
    if (total > 1) {
      pages.push(total);
    }
  }

  return pages;
});

// Watchers
watch([search, itemsPerPage, searchEverywhere], () => {
  currentPage.value = 1;
});

// Emitted unconditionally, including when the toggle goes off, so the parent can drop stale
// recursive results instead of holding them until the next search.
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

// Methods
const createDirectory = () => {
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
        newFolderName.value = ''; // Clear input after successful creation
        emit('update', props.path);
      },
      onFinish: () => {
        loading.value = false;
      },
    },
  );
};

// Handle folder dialog close
function handleFolderDialogClose(open: boolean) {
  showFolderUploadModal.value = open;
  if (!open) {
    newFolderName.value = ''; // Clear input when dialog is closed
  }
}

const deleteFile = (path: string) => {
  selectedFileForDeletion.value = path;
  showDeleteModal.value = true;
};

const deleteFileConfirmed = () => {
  loading.value = true;

  if (selectedFileForDeletion.value.includes('|||')) {
    const filesToDelete = selectedFileForDeletion.value.split('|||');
    router.delete(route('files.bulkDelete'), {
      data: { paths: filesToDelete },
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        // Check if there's a flash error (e.g., from staging read-only mode)
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
    // Handle folder deletion
    const folderPath = selectedFileForDeletion.value.replace('FOLDER:', '');
    router.delete(
      route('files.deleteDirectory', { path: folderPath }),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          // Check if there's a flash error (e.g., from staging read-only mode)
          if (page.props.flash?.error) {
            toasts.error(page.props.flash.error);
          }
          else {
            toasts.success($t('files.ui.folder_deleted'));
            // Check if current path is inside the deleted folder
            if (props.path === folderPath || props.path.startsWith(`${folderPath}/`)) {
              // Navigate to parent directory of the deleted folder
              const parentPath = folderPath.split('/').slice(0, -1).join('/') || '/';
              emit('changeDirectory', parentPath);
            }
            else {
              // Just refresh current directory
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
          // Check if there's a flash error (e.g., from staging read-only mode)
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
};

// Single click opens a folder. Requiring a double click was undiscoverable, and the single
// click had no other job — it was bound to an empty handler.
function handleFolderClick(folder: any) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  search.value = '';
  emit('changeDirectory', folder.path);
}

function handleBack() {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit('back');
}

function handleFileClick(file: any, event?: MouseEvent) {
  if (props.selectionMode) {
    // If selection is restricted by allowed extensions, enforce it
    const allowed = props.uploadExtensions?.length
      ? props.uploadExtensions.map(e => e.toLowerCase())
      : null;
    if (allowed) {
      const name: string = file?.name || file?.path || '';
      const ext = name.includes('.') ? name.split('.').pop()?.toLowerCase() : undefined;
      if (!ext || !allowed.includes(ext)) {
        toasts.error($t('files.ui.cannot_select_file_type'));
        return;
      }
    }
    emit('fileSelected', file.path);
    return;
  }

  if (isMultiSelectMode.value) {
    if (selectedFiles.value.has(file.path)) {
      selectedFiles.value.delete(file.path);
    }
    else {
      selectedFiles.value.add(file.path);
    }
    selectedFiles.value = new Set(selectedFiles.value);
  }
  else {
    selectedFile.value = file.path === selectedFile.value ? null : file.path;
  }
}

function handleFileDoubleClick(file: any) {
  if (props.selectionMode) {
    const allowed = props.uploadExtensions?.length
      ? props.uploadExtensions.map(e => e.toLowerCase())
      : null;
    if (allowed) {
      const name: string = file?.name || file?.path || '';
      const ext = name.includes('.') ? name.split('.').pop()?.toLowerCase() : undefined;
      if (!ext || !allowed.includes(ext)) {
        toasts.error($t('files.ui.cannot_select_file_type'));
        return;
      }
    }
    emit('fileSelected', file.path);
  }
}

function getFileName(filePath: string): string {
  return filePath.split('/').pop() || 'Unknown file';
}

// Delete dialog helpers
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

function navigateToPath(targetPath: string) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit('changeDirectory', targetPath);
}

function toggleMultiSelectMode() {
  isMultiSelectMode.value = !isMultiSelectMode.value;
  if (!isMultiSelectMode.value) {
    clearSelection();
  }
  selectedFile.value = null;
}

function selectAllFiles() {
  const allFilePaths = [...displayedFiles.value.map((file: any) => file.path)];
  selectedFiles.value = new Set(allFilePaths);
}

function clearSelection() {
  selectedFiles.value = new Set();
}

function deleteSelectedFiles() {
  if (selectedFiles.value.size === 0) return;

  const filesToDelete = Array.from(selectedFiles.value);
  selectedFileForDeletion.value = filesToDelete.join('|||');
  showDeleteModal.value = true;
}

function previewFile(filePath: string) {
  const url = filePath.replace('public/', '/uploads/');
  window.open(url, '_blank');
}

function handleDeleteFolder() {
  // Use the current path as the folder to delete
  const folderToDelete = props.path;
  const folderName = folderToDelete.split('/').pop() || 'Unknown folder';

  // Set up for folder deletion using the unified dialog
  selectedFileForDeletion.value = `FOLDER:${folderToDelete}`;
  showDeleteModal.value = true;
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
    }
  }
  catch (error: unknown) {
    toasts.error(error instanceof Error ? error.message : $t('files.errors.upload_all_failed'));
  }
  finally {
    // finally, not a success/error pair: a cancelled or non-JSON response must still
    // release the button, or it spins over an upload that already landed.
    loading.value = false;
  }
}

function onFilesSelected(_files: File[]) {
  // Selection handled downstream by the upload flow; no action needed here.
}
</script>

<style scoped>
/* Add loading spinner to button */
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
