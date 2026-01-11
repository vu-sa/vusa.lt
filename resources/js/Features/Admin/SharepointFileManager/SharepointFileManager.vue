<template>
  <div class="mt-4 rounded-md border border-zinc-200 p-8 shadow-xs dark:border-zinc-50/10">
    <template v-if="startingPath">
      <!-- Header with toolbar -->
      <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="flex gap-2">
            <Button class="rounded-full" @click="showFileUploader = true">
              <IFluentDocumentAdd24Regular class="mr-2 h-4 w-4" />
              {{ $t('forms.add') }}
            </Button>
            <Button :disabled="loading" variant="ghost" size="icon" class="rounded-full" @click="refreshFiles">
              <IFluentArrowClockwise24Filled class="h-4 w-4" />
            </Button>
          </div>
          <div class="flex-1"></div>
          <!-- Search -->
          <div class="w-full sm:w-[300px] h-10 flex items-center">
            <div class="relative w-full">
              <Input
                v-model="search"
                placeholder="Ieškoti..."
                class="w-full pr-10"
              />
              <IFluentSearch20Filled class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            </div>
          </div>
        </div>
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm bg-muted/30 rounded-md px-3 py-2">
          <IFluentFolder24Filled class="h-4 w-4 text-muted-foreground flex-shrink-0" />
          <nav class="flex items-center gap-1 text-foreground min-w-0 flex-1">
            <button 
              @click="navigateToPath(startingPath!)"
              class="font-medium transition-colors truncate hover:text-vusa-red"
              :class="{ 'text-vusa-red': currentPath === startingPath }"
            >
              {{ rootFolderName }}
            </button>
            <template v-if="breadcrumbParts.length > 0">
              <template v-for="(part, index) in breadcrumbParts" :key="index">
                <span class="text-muted-foreground flex-shrink-0">/</span>
                <button 
                  @click="navigateToPath(part.path)"
                  class="transition-colors truncate hover:text-vusa-red"
                  :class="{ 
                    'text-vusa-red font-medium': index === breadcrumbParts.length - 1,
                    'text-muted-foreground': index < breadcrumbParts.length - 1
                  }"
                >
                  {{ part.name }}
                </button>
              </template>
            </template>
          </nav>
        </div>
      </div>
      
      <!-- Upload Context Indicator -->
      <Alert v-if="fileable" variant="default" class="mt-4 border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
        <IFluentInfo16Regular class="h-4 w-4 text-blue-600 dark:text-blue-400" />
        <AlertDescription class="text-blue-700 dark:text-blue-300">
          {{ $t('Nauji failai bus priskirti') }}: <strong>{{ fileableDisplayName }}</strong>
        </AlertDescription>
      </Alert>
      
      <!-- File Grid -->
      <FileGrid
        :paginated-directories="paginatedDirectories"
        :paginated-files="paginatedFiles"
        :selected-file="selectedFile?.id ?? null"
        :selected-files="new Set()"
        :is-multi-select-mode="false"
        :selection-mode="false"
        :is-upload-mode="false"
        :search="search"
        :path="currentPath"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :current-page="currentPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        :view-mode="viewMode"
        :loading="loading"
        :hide-multi-select="true"
        @update:items-per-page="itemsPerPage = $event"
        @update:current-page="currentPage = $event"
        @update:view-mode="viewMode = $event"
        @folder-click="handleFolderClick"
        @folder-double-click="handleFolderDoubleClick"
        @file-click="handleFileClick"
        @file-double-click="handleFileDoubleClick"
        @go-back="navigateUp"
        @clear-search="search = ''"
        @show-upload-mode="showFileUploader = true"
        @show-create-folder="handleCreateFolder"
      />
      
      <!-- File Properties Drawer (unified) -->
      <FilePropertiesDrawer 
        source="sharepoint"
        :sharepoint-file="selectedFile" 
        @close="selectedFile = null" 
        @delete="handleSharepointFileDelete" 
      />
      
      <!-- SharePoint File Uploader (modal) -->
      <FileUploader 
        :show="showFileUploader" 
        :fileable="sanitizedFileable" 
        @close="handleFileUploaderClose" 
      />
    </template>
    <p v-else v-once>
      Failų tvarkyklė išjungta, nes institucija nėra priskirta padaliniui.
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, provide } from "vue";
import { router } from "@inertiajs/vue3";
import { useFetch, useStorage } from "@vueuse/core";
import { useFuse } from "@vueuse/integrations/useFuse";
import { toast } from "vue-sonner";
import { trans as $t } from "laravel-vue-i18n";

// UI Components
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Alert, AlertDescription } from "@/Components/ui/alert";

// Unified FileManager Components
import FileGrid from "@/Features/Admin/FileManager/Components/FileGrid.vue";
import FilePropertiesDrawer from "@/Features/Admin/FileManager/Components/FilePropertiesDrawer.vue";

// SharePoint-specific Components
import FileUploader from "./Uploader/FileUploader.vue";

// Icons
import IFluentDocumentAdd24Regular from '~icons/fluent/document-add-24-regular';
import IFluentArrowClockwise24Filled from '~icons/fluent/arrow-clockwise-24-filled';
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';
import IFluentSearch20Filled from '~icons/fluent/search-20-filled';
import IFluentInfo16Regular from '~icons/fluent/info-16-regular';

const props = defineProps<{
  fileable?: FileableFormData;
  startingPath?: string;
}>();

// Sanitize fileable to prevent serialization issues
const sanitizedFileable = computed(() => {
  if (!props.fileable) return undefined;
  return { id: props.fileable.id, type: props.fileable.type };
});

// State
const currentPath = ref(props.startingPath ?? '');
const loading = ref(true);
const files = ref<MyDriveItem[]>([]);
const showFileUploader = ref(false);
const viewMode = useStorage<'grid' | 'list'>('fileManager-viewMode', 'grid');
const selectedFile = ref<MyDriveItem | null>(null);
const search = ref('');
const itemsPerPage = ref(50);
const currentPage = ref(1);

// Display name for upload context indicator
const fileableDisplayName = computed(() => {
  if (!props.fileable) return '';
  
  // Extract a readable name from the fileable type
  const typeMap: Record<string, string> = {
    'Meeting': 'Posėdis',
    'Institution': 'Institucija',
    'Duty': 'Pareigos',
    'Type': 'Tipas',
  };
  
  const typeName = typeMap[props.fileable.type] || props.fileable.type;
  
  // If we have a fileable_name from the form data, use it
  if ((props.fileable as any).fileable_name) {
    return `${typeName}: ${(props.fileable as any).fileable_name}`;
  }
  
  return typeName;
});

// Fuse.js fuzzy search
const fuseOptions = computed(() => ({
  fuseOptions: {
    keys: ['name'],
    threshold: 0.4,
  },
  matchAllWhenSearchEmpty: true,
}));

const { results: searchResults } = useFuse(search, files, fuseOptions);

// Separate files and directories
const allItems = computed(() => {
  return searchResults.value.map(r => r.item);
});

const directories = computed(() => {
  return allItems.value
    .filter(item => !!item.folder)
    .map(item => ({
      id: item.id ?? item.name ?? '',
      path: `${currentPath.value}/${item.name}`,
      name: item.name ?? '',
      type: 'directory' as const,
      _raw: item,
    }));
});

const filesOnly = computed(() => {
  return allItems.value
    .filter(item => !item.folder)
    .map(item => ({
      id: item.id ?? item.name ?? '',
      path: `${currentPath.value}/${item.name}`,
      name: item.name ?? '',
      type: 'file' as const,
      size: item.size ?? undefined,
      modified: item.lastModifiedDateTime ? new Date(item.lastModifiedDateTime).getTime() / 1000 : undefined,
      mimeType: item.file?.mimeType ?? undefined,
      _raw: item,
    }));
});

// Pagination
const totalItems = computed(() => directories.value.length + filesOnly.value.length);

const totalPages = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return 1;
  return Math.ceil(totalItems.value / itemsPerPage.value);
});

const paginatedDirectories = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return directories.value;
  
  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;
  const dirEnd = Math.min(endIndex, directories.value.length);
  const dirStart = Math.min(startIndex, directories.value.length);
  
  return directories.value.slice(dirStart, dirEnd);
});

const paginatedFiles = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return filesOnly.value;
  
  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;
  const fileStart = Math.max(0, startIndex - directories.value.length);
  const fileEnd = Math.max(0, endIndex - directories.value.length);
  
  if (startIndex >= directories.value.length) {
    return filesOnly.value.slice(fileStart, fileEnd);
  } else if (endIndex > directories.value.length) {
    const remainingSpace = endIndex - directories.value.length;
    return filesOnly.value.slice(0, remainingSpace);
  }
  return [];
});

const visiblePages = computed(() => {
  const pages: (number | string)[] = [];
  const total = totalPages.value;
  const current = currentPage.value;
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
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

// Breadcrumb
const rootFolderName = computed(() => {
  if (!props.startingPath) return 'Failai';
  const parts = props.startingPath.split('/');
  return parts[parts.length - 1] || 'Failai';
});

const breadcrumbParts = computed(() => {
  if (!props.startingPath || currentPath.value === props.startingPath) return [];
  
  const relativePath = currentPath.value.replace(props.startingPath + '/', '');
  if (!relativePath) return [];
  
  const parts = relativePath.split('/');
  return parts.map((part, index) => ({
    name: part,
    path: props.startingPath + '/' + parts.slice(0, index + 1).join('/'),
  }));
});

// Fetch files from SharePoint
async function fetchFiles(path: string) {
  if (!path) return;
  
  loading.value = true;
  try {
    const { data } = await useFetch(
      route('sharepoint.getDriveItems', { path })
    ).json();
    
    files.value = data.value ?? [];
  } finally {
    loading.value = false;
  }
}

// Watch path changes
watch(currentPath, (newPath) => {
  if (newPath) {
    currentPage.value = 1;
    selectedFile.value = null;
    search.value = ''; // Clear search when navigating
    fetchFiles(newPath);
  }
});

// Watch search changes
watch(search, () => {
  currentPage.value = 1;
});

// Initial fetch
if (props.startingPath) {
  fetchFiles(props.startingPath);
}

// Event handlers
function handleFolderClick(_folder: any) {
  // Single click does nothing for folders
}

function handleFolderDoubleClick(folder: any) {
  currentPath.value = folder.path;
}

function handleFileClick(file: any) {
  // Find the raw SharePoint item
  const rawItem = file._raw as MyDriveItem | undefined;
  if (rawItem && rawItem.file) {
    selectedFile.value = rawItem;
  } else {
    selectedFile.value = null;
  }
}

function handleFileDoubleClick(file: any) {
  const rawItem = file._raw as MyDriveItem | undefined;
  if (rawItem?.webUrl) {
    window.open(rawItem.webUrl, '_blank');
  }
}

function navigateUp() {
  if (currentPath.value === props.startingPath) return;
  const parts = currentPath.value.split('/');
  parts.pop();
  currentPath.value = parts.join('/');
}

function navigateToPath(path: string) {
  currentPath.value = path;
}

function refreshFiles() {
  fetchFiles(currentPath.value);
}

function handleFileUploaderClose() {
  showFileUploader.value = false;
  refreshFiles();
}

async function handleCreateFolder() {
  const folderName = prompt($t('Įveskite aplanko pavadinimą'));
  if (!folderName || !folderName.trim()) return;
  
  loading.value = true;
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch(route('sharepoint.createFolder'), {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ path: currentPath.value, name: folderName.trim() }),
    });
    
    if (!response.ok) {
      const error = await response.json();
      toast.error(error.message || $t('Nepavyko sukurti aplanko'));
      return;
    }
    
    toast.success($t('Aplankas sukurtas'));
    refreshFiles();
  } catch (error) {
    toast.error($t('Nepavyko sukurti aplanko'));
  } finally {
    loading.value = false;
  }
}

function handleFileDeleted(id: string | number) {
  const index = files.value.findIndex(f => f.id === id);
  if (index !== -1) {
    files.value.splice(index, 1);
  }
  selectedFile.value = null;
}

// Handle SharePoint file deletion via FilePropertiesDrawer emit
function handleSharepointFileDelete() {
  if (!selectedFile.value) return;
  
  // Check if this file has an associated FileableFile record (sharepointFile)
  // The sharepointFile property is added by the backend when file is linked to a fileable
  const sharepointFileRecord = (selectedFile.value as Record<string, unknown>).sharepointFile as { id: number } | undefined;
  
  if (!sharepointFileRecord?.id) {
    // No associated FileableFile record - just remove from local state
    handleFileDeleted(selectedFile.value.id ?? '');
    return;
  }
  
  router.delete(route('sharepointFiles.destroy', sharepointFileRecord.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      if (selectedFile.value?.id) {
        handleFileDeleted(selectedFile.value.id);
      }
    },
    onError: () => {
      toast.error('Failed to delete file');
    },
  });
}

function handleUploadModeChange(value: boolean) {
  if (value) {
    showFileUploader.value = true;
  }
}

// Provide handlers for child components (legacy support)
provide('handleFileSelect', handleFileClick);
provide('handleFileDblClick', handleFileDoubleClick);
</script>
