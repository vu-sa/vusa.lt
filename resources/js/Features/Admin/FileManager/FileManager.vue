<template>
  <div>
    <!-- Header with toolbar and breadcrumbs -->
    <FileManagerHeader
      :path="props.path"
      :search
      :is-upload-mode
      :selection-mode="props.selectionMode"
      :small="props.small"
      :allow-upload-in-selection="props.allowUploadInSelection"
      @update:search="search = $event"
      @update:is-upload-mode="isUploadMode = $event"
      @navigate-to-path="navigateToPath"
      @show-create-folder="showFolderUploadModal = true"
    />

    <!-- Inline create-folder form in selection mode (moved near top for visibility) -->
    <div v-if="props.selectionMode && showFolderUploadModal" class="mt-4 border rounded-md p-4 bg-muted/30">
      <div class="grid w-full max-w-sm items-center gap-1.5 mb-4">
        <Label for="folderNameInline">Naujo aplanko pavadinimas</Label>
        <Input id="folderNameInline" v-model="newFolderName" placeholder="Pavadinimas..." />
      </div>
      <div class="flex gap-2">
        <Button :disabled="loading" :data-loading="loading" @click="createDirectory">
          Sukurti
        </Button>
        <Button variant="outline" @click="showFolderUploadModal = false">
          Atšaukti
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
    <div v-else class="mt-4">
      <!-- Main file browser - fixed width, no layout shifts -->
      <FileGrid
        :paginated-directories
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
        @update:items-per-page="itemsPerPage = $event"
        @update:current-page="currentPage = $event"
        @update:view-mode="viewMode = $event"
        @toggle-multi-select="toggleMultiSelectMode"
        @select-all="selectAllFiles"
        @clear-selection="clearSelection"
        @delete-selected="deleteSelectedFiles"
        @folder-click="handleFolderClick"
        @folder-double-click="handleFolderDoubleClick"
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
        :files="shownFiles"
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
          <DialogTitle>Pridėti aplanką</DialogTitle>
          <DialogDescription>
            Sukurkite naują aplanką failų organizavimui
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="folderName">Naujo aplanko pavadinimas</Label>
            <Input
              id="folderName"
              v-model="newFolderName"
              placeholder="Įveskite aplanko pavadinimą..."
              @keyup.enter="createDirectory"
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="handleFolderDialogClose(false)">
            Atšaukti
          </Button>
          <Button
            :disabled="loading || !newFolderName.trim()"
            @click="createDirectory"
          >
            Sukurti
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
import { useFuse } from '@vueuse/integrations/useFuse';
import { useStorage } from '@vueuse/core';

import FileManagerHeader from './Components/FileManagerHeader.vue';
import FileGrid from './Components/FileGrid.vue';
import FilePropertiesDrawer from './Components/FilePropertiesDrawer.vue';
import type { FileSource, FileableRef } from './types';

import { useToasts } from '@/Composables/useToasts';

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
  /** File source backend: 'local' (default) or 'sharepoint' */
  source?: FileSource;
  /** Starting path for SharePoint mode */
  startingPath?: string;
  /** Associated fileable entity for SharePoint uploads */
  fileable?: FileableRef;
}>();

const emit = defineEmits<{
  back: [];
  changeDirectory: [directory: string];
  fileSelected: [file: string];
  update: [path: string];
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

const totalItems = computed(() => {
  return shownDirectories.value.length + shownFiles.value.length;
});

const totalPages = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return 1;
  return Math.ceil(totalItems.value / itemsPerPage.value);
});

const paginatedDirectories = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return shownDirectories.value;

  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;

  // Calculate how many directories to show on this page
  const directoriesEnd = Math.min(endIndex, shownDirectories.value.length);
  const directoriesStart = Math.min(startIndex, shownDirectories.value.length);

  return shownDirectories.value.slice(directoriesStart, directoriesEnd);
});

const paginatedFiles = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return shownFiles.value;

  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;

  // Calculate file pagination after directories
  const fileStartIndex = Math.max(0, startIndex - shownDirectories.value.length);
  const fileEndIndex = Math.max(0, endIndex - shownDirectories.value.length);

  // Only show files if we've gone past all directories or if directories don't fill the page
  if (startIndex >= shownDirectories.value.length) {
    return shownFiles.value.slice(fileStartIndex, fileEndIndex);
  }
  else if (endIndex > shownDirectories.value.length) {
    // Partial page - some directories and some files
    const remainingSpace = endIndex - shownDirectories.value.length;
    return shownFiles.value.slice(0, remainingSpace);
  }
  else {
    // Page is full of directories
    return [];
  }
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
watch([search, itemsPerPage], () => {
  currentPage.value = 1;
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
        toasts.success('Aplankas sukurtas');
        showFolderUploadModal.value = false;
        newFolderName.value = ''; // Clear input after successful creation
        loading.value = false;
        emit('update', props.path);
      },
      onError: () => {
        loading.value = false;
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
        loading.value = false;
        showDeleteModal.value = false;
        // Check if there's a flash error (e.g., from staging read-only mode)
        if (page.props.flash?.error) {
          toasts.error(page.props.flash.error);
        }
        else {
          toasts.success(`${filesToDelete.length} failai ištrinti`);
          clearSelection();
          emit('update', props.path);
        }
      },
      onError: () => {
        toasts.error('Klaida trinant failus');
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
          loading.value = false;
          showDeleteModal.value = false;
          // Check if there's a flash error (e.g., from staging read-only mode)
          if (page.props.flash?.error) {
            toasts.error(page.props.flash.error);
          }
          else {
            toasts.success('Aplankas ištrintas');
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
          toasts.error('Klaida trinant aplanką');
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
          loading.value = false;
          showDeleteModal.value = false;
          // Check if there's a flash error (e.g., from staging read-only mode)
          if (page.props.flash?.error) {
            toasts.error(page.props.flash.error);
          }
          else {
            toasts.success('Failas ištrintas');
            emit('update', props.path);
          }
        },
        onError: () => {
          toasts.error('Klaida trinant failą');
          loading.value = false;
          showDeleteModal.value = false;
        },
      },
    );
  }
};

function handleFolderClick(folder: any) {
  // Single click does nothing for folders
}

function handleFolderDoubleClick(folder: any) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
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
        toasts.error('Šio failo tipo pasirinkti negalima.');
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
        toasts.error('Šio failo tipo pasirinkti negalima.');
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
    return 'Ištrinti aplanką?';
  }
  if (selectedFileForDeletion.value.includes('|||')) {
    const fileCount = selectedFileForDeletion.value.split('|||').length;
    return `Ištrinti ${fileCount} failus?`;
  }
  return 'Ištrinti failą?';
}

function getDeleteMessage(): string {
  if (selectedFileForDeletion.value.startsWith('FOLDER:')) {
    const folderPath = selectedFileForDeletion.value.replace('FOLDER:', '');
    const folderName = folderPath.split('/').pop() || 'Unknown folder';
    return `Ar tikrai norite ištrinti tuščią aplanką "${folderName}"? Šio veiksmo nebus galima atšaukti.\n\nDėmesio: Aplankas turi būti tuščias, kad būtų galima jį ištrinti.`;
  }
  if (selectedFileForDeletion.value.includes('|||')) {
    const fileCount = selectedFileForDeletion.value.split('|||').length;
    const fileList = selectedFileForDeletion.value.split('|||')
      .map(file => getFileName(file))
      .join(', ');
    return `Ar tikrai norite ištrinti ${fileCount} failus? Failų bus neįmanoma atkurti!\n\nFailai: ${fileList}`;
  }
  const fileName = getFileName(selectedFileForDeletion.value);
  return `Ar tikrai norite ištrinti failą "${fileName}"? Failo bus neįmanoma atkurti!\n\nPrieš ištrinant įsitikinkite, kad failas nėra naudojamas jokiame puslapyje.`;
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
  const allFilePaths = [...shownFiles.value.map((file: any) => file.path)];
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

function handleFileUpload(files: File[]) {
  loading.value = true;

  // Separate image files from other files
  const imageFiles = files.filter(file => file.type.startsWith('image/'));
  const otherFiles = files.filter(file => !file.type.startsWith('image/'));

  let completedUploads = 0;
  const totalUploads = (imageFiles.length > 0 ? 1 : 0) + (otherFiles.length > 0 ? 1 : 0);

  const checkCompletion = () => {
    completedUploads++;
    if (completedUploads >= totalUploads) {
      loading.value = false;
      isUploadMode.value = false;
      uploadAreaRef.value?.clearFiles();
      emit('update', props.path);
    }
  };

  // Upload images using the optimization route (one by one for better UX)
  if (imageFiles.length > 0) {
    let processedImages = 0;

    const uploadNextImage = () => {
      if (processedImages >= imageFiles.length) {
        checkCompletion();
        return;
      }

      const file = imageFiles[processedImages];
      if (!file) {
        processedImages++;
        uploadNextImage();
        return;
      }

      processedImages++;

      router.post(
        route('files.uploadImage'),
        {
          image: file,
          name: file.name,
          path: props.path, // Use current path instead of content folder
        },
        {
          preserveScroll: true,
          preserveState: true,
          onSuccess: () => {
            // Success notification handled by server
            uploadNextImage(); // Process next image
          },
          onError: () => {
            toasts.error(`Failed to upload ${file.name}`);
            uploadNextImage(); // Continue with next image even if one fails
          },
        },
      );
    }; uploadNextImage();
  }

  // Upload non-image files using the regular route (all at once)
  if (otherFiles.length > 0) {
    const fileList = otherFiles.map(file => ({ file }));

    router.post(
      route('files.store'),
      { files: fileList, path: props.path },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Success notification handled by server
          checkCompletion();
        },
        onError: () => {
          toasts.error('Klaida įkeliant failus');
          checkCompletion();
        },
      },
    );
  }

  // If no files to upload, reset loading state
  if (totalUploads === 0) {
    loading.value = false;
  }
}

function onFilesSelected(files: File[]) {
  console.log('Files selected:', files);
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
