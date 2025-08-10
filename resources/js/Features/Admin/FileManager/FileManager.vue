<template>
  <div>
    <!-- Header with toolbar and breadcrumbs -->
    <FileManagerHeader
      :path="props.path"
      :search="search"
      :is-upload-mode="isUploadMode"
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
        <Button :disabled="loading" :data-loading="loading" @click="createDirectory">Sukurti</Button>
        <Button variant="outline" @click="showFolderUploadModal = false">Atšaukti</Button>
      </div>
    </div>
    
    <!-- Upload Mode -->
    <div v-if="isUploadMode && (!props.selectionMode || props.allowUploadInSelection)" class="mt-4">
      <FileUploadArea 
        :loading="loading"
        :force-accept="!!props.uploadAccept || !!props.uploadExtensions"
        :accept="props.uploadAccept || '*'"
        :extensions="props.uploadExtensions"
        @upload="handleFileUpload"
        @files-selected="onFilesSelected"
        ref="uploadAreaRef"
      />
    </div>
    
    <!-- Browse Mode -->
    <div v-else class="mt-4">
      <!-- Main file browser - fixed width, no layout shifts -->
      <FileGrid
        :paginated-directories="paginatedDirectories"
        :paginated-files="paginatedFiles"
        :selected-file="selectedFile"
        :selected-files="selectedFiles"
        :is-multi-select-mode="isMultiSelectMode"
        :selection-mode="props.selectionMode"
        :is-upload-mode="isUploadMode"
        :search="search"
        :path="props.path"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :current-page="currentPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        @update:items-per-page="itemsPerPage = $event"
        @update:current-page="currentPage = $event"
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
        :selected-file="selectedFile"
        :files="shownFiles"
        @preview="previewFile(selectedFile!)"
        @delete="deleteFile(selectedFile!)"
        @close="selectedFile = null"
      />
    </div>
    
    <!-- Modals -->
  <CardModal v-if="!props.selectionMode" :show="showFolderUploadModal" title="Pridėti aplanką" @close="showFolderUploadModal = false">
      <div>
        <div class="grid w-full max-w-sm items-center gap-1.5 mb-4">
          <Label for="folderName">Naujo aplanko pavadinimas</Label>
          <Input id="folderName" v-model="newFolderName" placeholder="Pavadinimas..." />
        </div>
        <Button :disabled="loading" :data-loading="loading" @click="createDirectory">
          Sukurti
        </Button>
      </div>
    </CardModal>
    
  <CardModal v-if="!props.selectionMode" :show="showDeleteModal" title="Ištrinti failą" @close="showDeleteModal = false">
      <div>
        <p class="mb-4 text-base font-bold">
          {{ selectedFileForDeletion.includes('|||') 
            ? `Ar tikrai nori ištrinti ${selectedFileForDeletion.split('|||').length} failus? Failų bus neįmanoma atkurti!`
            : 'Ar tikrai nori ištrinti šį failą? Failo bus neįmanoma atkurti!' 
          }}
        </p>
        <p class="mb-4">
          Prieš ištrinant {{ selectedFileForDeletion.includes('|||') ? 'failus' : 'failą' }}, įsitikink, kad {{ selectedFileForDeletion.includes('|||') ? 'jie nėra naudojami' : 'jis nėra naudojamas' }} jokiame puslapyje.
        </p>
        <div class="mb-4 text-zinc-500 max-h-24 overflow-y-auto">
          <template v-if="selectedFileForDeletion.includes('|||')">
            <div v-for="file in selectedFileForDeletion.split('|||')" :key="file" class="text-xs font-mono">
              {{ getFileName(file) }}
            </div>
          </template>
          <template v-else>
            <span class="text-xs font-mono">{{ selectedFileForDeletion }}</span>
          </template>
        </div>
        <div class="flex gap-2">
          <Button :disabled="loading" :data-loading="loading" variant="destructive" @click="deleteFileConfirmed">
            Taip
          </Button>
          <Button variant="outline" @click="showDeleteModal = false">
            Ne
          </Button>
        </div>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToasts } from '@/Composables/useToasts';

// Components
import CardModal from '@/Components/Modals/CardModal.vue';
import FileUploadArea from '@/Components/FileUpload/FileUploadArea.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

// Custom components
import FileManagerHeader from './Components/FileManagerHeader.vue';
import FileGrid from './Components/FileGrid.vue';
import FilePropertiesDrawer from './Components/FilePropertiesDrawer.vue';

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
}>();

const emit = defineEmits<{
  back: [],
  changeDirectory: [directory: string],
  fileSelected: [file: string],
  update: [path: string],
}>();

const toasts = useToasts();

// State
const showFolderUploadModal = ref(false);
const showDeleteModal = ref(false);
const selectedFileForDeletion = ref("");
const newFolderName = ref("");
const loading = ref(false);
const selectedFile = ref<string | null>(null);
const selectedFiles = ref<Set<string>>(new Set());
const isMultiSelectMode = ref(false);
const isUploadMode = ref(false);
const uploadAreaRef = ref();
const search = ref("");
const itemsPerPage = ref(50);
const currentPage = ref(1);

// Computed properties
const shownFiles = computed(() => {
  if (search.value === "") {
    return props.files;
  }
  return props.files.filter((file: any) => {
    return file.name.toLowerCase().includes(search.value.toLowerCase());
  });
});

const shownDirectories = computed(() => {
  if (search.value === "") {
    return props.directories;
  }
  return props.directories.filter((directory: any) => {
    return directory.name.toLowerCase().includes(search.value.toLowerCase());
  });
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
  } else if (endIndex > shownDirectories.value.length) {
    // Partial page - some directories and some files
    const remainingSpace = endIndex - shownDirectories.value.length;
    return shownFiles.value.slice(0, remainingSpace);
  } else {
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
  } else {
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
  } else {
    currentPage.value = 1;
  }
});

// Methods
const createDirectory = () => {
  loading.value = true;
  router.post(
    route("files.createDirectory"),
    { path: props.path, name: newFolderName.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toasts.success("Aplankas sukurtas");
        showFolderUploadModal.value = false;
        loading.value = false;
        emit("update", props.path);
      },
      onError: () => {
        loading.value = false;
      },
      onFinish: () => {
        loading.value = false;
      }
    },
  );
};

const deleteFile = (path: string) => {
  selectedFileForDeletion.value = path;
  showDeleteModal.value = true;
};

const deleteFileConfirmed = () => {
  loading.value = true;
  
  if (selectedFileForDeletion.value.includes('|||')) {
    const filesToDelete = selectedFileForDeletion.value.split('|||');
    router.delete(route("files.bulkDelete"), {
      data: { paths: filesToDelete },
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toasts.success(`${filesToDelete.length} failai ištrinti`);
        clearSelection();
        loading.value = false;
        showDeleteModal.value = false;
        emit("update", props.path);
      },
      onError: () => {
        toasts.error('Klaida trinant failus');
        loading.value = false;
        showDeleteModal.value = false;
      }
    });
  } else {
    router.delete(
      route("files.delete", { path: selectedFileForDeletion.value }),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          toasts.success("Failas ištrintas");
          loading.value = false;
          showDeleteModal.value = false;
          emit("update", props.path);
        },
        onError: () => {
          toasts.error('Klaida trinant failą');
          loading.value = false;
          showDeleteModal.value = false;
        }
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
  emit("changeDirectory", folder.path);
}

function handleBack() {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit("back");
}

function handleFileClick(file: any, event?: MouseEvent) {
  if (props.selectionMode) {
    // If selection is restricted by allowed extensions, enforce it
    const allowed = props.uploadExtensions?.length
      ? props.uploadExtensions.map((e) => e.toLowerCase())
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
    } else {
      selectedFiles.value.add(file.path);
    }
    selectedFiles.value = new Set(selectedFiles.value);
  } else {
    selectedFile.value = file.path === selectedFile.value ? null : file.path;
  }
}

function handleFileDoubleClick(file: any) {
  if (props.selectionMode) {
    const allowed = props.uploadExtensions?.length
      ? props.uploadExtensions.map((e) => e.toLowerCase())
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
  
  // Show confirmation dialog
  if (confirm(`Ar tikrai norite ištrinti tuščią aplanką "${folderName}"? Šio veiksmo nebus galima atšaukti.`)) {
    loading.value = true;
    
    router.delete(route("files.deleteDirectory"), {
      data: { path: folderToDelete },
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toasts.success(`Aplankas "${folderName}" sėkmingai ištrintas.`);
        loading.value = false;
        // Navigate back to parent directory
        emit("back");
      },
      onError: (errors) => {
        loading.value = false;
        // Show the first error message
        const errorMessage = Object.values(errors)[0];
        toasts.error(typeof errorMessage === 'string' ? errorMessage : 'Nepavyko ištrinti aplanko.');
      }
    });
  }
}

function handleFileUpload(files: File[]) {
  loading.value = true;
  
  const fileList = files.map(file => ({ file }));
  
  router.post(
    route("files.store"),
    { files: fileList, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        toasts.success(`${files.length} ${files.length === 1 ? 'failas įkeltas' : 'failai įkelti'}! Peržiūrėkite juos žemiau.`);
        loading.value = false;
        isUploadMode.value = false;
        uploadAreaRef.value?.clearFiles();
        emit("update", props.path);
      },
      onError: () => {
        toasts.error('Klaida įkeliant failus');
        loading.value = false;
      }
    },
  );
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