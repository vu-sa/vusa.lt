<template>
  <div>
    <div class="space-y-4">
      <!-- Header with actions and search -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex gap-2" v-if="!props.selectionMode">
          <!-- Toggle between browse and upload modes -->
          <Button 
            :variant="isUploadMode ? 'outline' : 'default'" 
            size="sm" 
            @click="isUploadMode = false"
          >
            <IFluentFolder24Regular class="mr-2 h-4 w-4" />
            Naršyti
          </Button>
          <Button 
            :variant="isUploadMode ? 'default' : 'outline'" 
            size="sm" 
            @click="isUploadMode = true"
          >
            <IFluentCloudArrowUp24Regular class="mr-2 h-4 w-4" />
            Įkelti failus
          </Button>
          <Button variant="outline" size="sm" @click="showFolderUploadModal = true">
            <IFluentFolderAdd24Regular class="mr-2 h-4 w-4" />
            Pridėti aplanką
          </Button>
        </div>
        <div class="flex-1"></div>
        <!-- Fixed width and height container to prevent layout shifts -->
        <div class="w-full sm:w-[300px] h-10 flex items-center">
          <Input 
            v-if="!isUploadMode"
            v-model="search" 
            class="w-full" 
            :size="small ? 'sm' : undefined" 
            placeholder="Ieškoti failų..." 
          />
        </div>
      </div>
      
      <!-- Interactive breadcrumb navigation -->
      <div class="flex items-center gap-2 text-sm bg-muted/30 rounded-md px-3 py-2">
        <IFluentFolder24Filled class="h-4 w-4 text-muted-foreground flex-shrink-0" />
        <nav class="flex items-center gap-1 text-foreground min-w-0 flex-1">
          <!-- Upload mode indicator -->
          <span v-if="isUploadMode && !props.selectionMode" class="text-xs text-muted-foreground mr-2">
            Įkeliama į:
          </span>
          <button 
            @click="!isUploadMode ? navigateToPath('public/files') : undefined"
            class="font-medium transition-colors truncate"
            :class="{ 
              'text-vusa-red hover:text-vusa-red': props.path === 'public/files',
              'hover:text-vusa-red': !isUploadMode,
              'cursor-default': isUploadMode 
            }"
          >
            Failai
          </button>
          <template v-if="breadcrumbParts.length > 0">
            <template v-for="(part, index) in breadcrumbParts" :key="index">
              <span class="text-muted-foreground flex-shrink-0">/</span>
              <button 
                @click="!isUploadMode ? navigateToPath(part.path) : undefined"
                class="transition-colors truncate"
                :class="{ 
                  'text-vusa-red font-medium': index === breadcrumbParts.length - 1,
                  'text-muted-foreground': index < breadcrumbParts.length - 1,
                  'hover:text-vusa-red': !isUploadMode,
                  'cursor-default': isUploadMode
                }"
              >
                {{ part.name }}
              </button>
            </template>
          </template>
        </nav>
      </div>
    </div>
    
    <!-- Upload Mode -->
    <div v-if="isUploadMode && !props.selectionMode" class="mt-4">
      <FileUploadArea 
        :loading="loading"
        @upload="handleFileUpload"
        @files-selected="onFilesSelected"
        ref="uploadAreaRef"
      />
    </div>
    
    <!-- Browse Mode -->
    <div v-else class="mt-4 rounded-md border border-border shadow-xs">
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
                v-model="itemsPerPage" 
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
          <div v-if="!props.selectionMode && !isUploadMode">
            <!-- Multi-select controls - responsive layout -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
              <Button 
                variant="outline" 
                size="sm" 
                @click="toggleMultiSelectMode"
                :class="{ 'bg-vusa-red text-white': isMultiSelectMode }"
              >
                <IFluentCheckmarkCircle24Regular v-if="isMultiSelectMode" class="h-4 w-4 mr-1" />
                <IFluentCircle24Regular v-else class="h-4 w-4 mr-1" />
                {{ isMultiSelectMode ? 'Baigti pasirinkimą' : 'Pasirinkti kelis' }}
              </Button>
              
              <!-- Bulk actions - separate row on mobile -->
              <div v-if="isMultiSelectMode && selectedFiles.size > 0" class="flex flex-wrap items-center gap-1 sm:gap-2">
                <Button variant="outline" size="sm" @click="selectAllFiles">
                  <IFluentCheckmarkCircle24Regular class="h-4 w-4 sm:mr-1" />
                  <span class="hidden sm:inline">Pasirinkti visus</span>
                </Button>
                <Button variant="outline" size="sm" @click="clearSelection">
                  <IFluentCircle24Regular class="h-4 w-4 sm:mr-1" />
                  <span class="hidden sm:inline">Išvalyti</span>
                </Button>
                <Button variant="destructive" size="sm" @click="deleteSelectedFiles">
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
          <div v-for="folder in paginatedDirectories" :key="folder.id" class="flex">
            <button 
              class="w-full aspect-square flex flex-col items-center justify-center p-4 rounded-lg border border-border bg-background hover:bg-muted transition-all duration-200 hover:shadow-md group focus:ring-2 focus:ring-vusa-red focus:ring-offset-2"
              @dblclick="handleChangeDirectory(folder.path)"
            >
              <IFluentFolder24Filled class="h-12 w-12 text-muted-foreground group-hover:text-vusa-red transition-colors" />
              <span class="text-sm text-center mt-3 line-clamp-2 text-foreground font-medium break-all leading-tight">{{ folder.name }}</span>
            </button>
          </div>
          
          <!-- Files -->
          <div v-for="file in paginatedFiles" :key="file.id" class="group relative flex">
            <button 
              :class="[
                'w-full aspect-square flex flex-col items-center justify-center p-4 rounded-lg border border-border bg-background transition-all duration-200 hover:shadow-md focus:ring-2 focus:ring-vusa-red focus:ring-offset-2',
                props.selectionMode && selectedFile === file.path 
                  ? 'ring-2 ring-vusa-red ring-offset-2 bg-vusa-red/5' 
                  : isMultiSelectMode && selectedFiles.has(file.path)
                    ? 'ring-2 ring-vusa-red ring-offset-2 bg-vusa-red/5'
                    : selectedFile === file.path
                      ? 'ring-2 ring-muted-foreground ring-offset-2 bg-muted'
                      : 'hover:bg-muted'
              ]"
              @click="handleFileClick(file.path, $event)"
              @dblclick="props.selectionMode ? handleFileDoubleClick(file.path) : undefined" 
              @keydown.enter="props.selectionMode ? handleFileClick(file.path) : undefined"
              @keydown.space.prevent="props.selectionMode ? handleFileClick(file.path) : undefined"
              tabindex="0"
            >
              <!-- File thumbnail or icon -->
              <div class="h-10 w-10 flex items-center justify-center">
                <img 
                  v-if="file?.name?.endsWith('.jpg') || file?.name?.endsWith('.png') || file?.name?.endsWith('.jpeg') || file?.name?.endsWith('.webp')"
                  :src="`/uploads/${file.path?.replace('public/', '') || ''}`"
                  :alt="file.name"
                  class="h-10 w-10 object-cover rounded shadow-sm"
                />
                <span v-else class="text-muted-foreground group-hover:text-vusa-red transition-colors">
                  <!-- PDF files -->
                  <IFluentDocumentPdf24Regular v-if="getFileExtension(file.path).toLowerCase() === 'pdf'" class="h-10 w-10" />
                  <!-- Document files -->
                  <IFluentDocumentText24Regular v-else-if="['doc', 'docx', 'odt', 'txt', 'rtf'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Spreadsheet files including CSV -->
                  <IFluentDocumentTable24Regular v-else-if="['xls', 'xlsx', 'csv', 'ods'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Video files -->
                  <IFluentVideo24Regular v-else-if="['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Audio files -->
                  <IFluentMusicNote24Regular v-else-if="['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Archive files -->
                  <IFluentFolderZip24Regular v-else-if="['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Code files -->
                  <IFluentCode24Regular v-else-if="['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'].includes(getFileExtension(file.path).toLowerCase())" class="h-10 w-10" />
                  <!-- Default fallback for any other file type -->
                  <IFluentDocument24Regular v-else class="h-10 w-10" />
                </span>
              </div>
              <span class="text-sm text-center mt-3 line-clamp-2 text-muted-foreground break-all leading-tight">{{ file.name }}</span>
            </button>
            
            
            <!-- Selection indicators -->
            <Transition name="selection-badge">
              <div v-if="props.selectionMode && selectedFile === file.path" 
                class="absolute top-1 right-1 bg-vusa-red text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold z-20 shadow-lg">
                ✓
              </div>
            </Transition>
            <Transition name="selection-badge">
              <div v-if="isMultiSelectMode && selectedFiles.has(file.path)" 
                class="absolute top-1 right-1 bg-vusa-red text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold z-20 shadow-lg">
                ✓
              </div>
            </Transition>
            <Transition name="selection-badge">
              <div v-if="!props.selectionMode && !isMultiSelectMode && selectedFile === file.path" 
                class="absolute top-1 right-1 bg-muted-foreground text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold z-20 shadow-lg">
                i
              </div>
            </Transition>
          </div>
        </div>
        
        <!-- Pagination controls -->
        <div v-if="totalPages > 1" class="mt-6 flex items-center justify-center gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="currentPage === 1"
            @click="currentPage = Math.max(1, currentPage - 1)"
          >
            Ankstesnis
          </Button>
          
          <div class="flex items-center gap-1">
            <template v-for="page in visiblePages" :key="page">
              <Button
                v-if="typeof page === 'number'"
                :variant="page === currentPage ? 'default' : 'outline'"
                size="sm"
                @click="currentPage = page"
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
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
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
            : props.selectionMode 
              ? 'Nėra failų šiame aplanke. Naršykite kitus aplankus arba kreipkitės į administratorių.'
              : 'Pradėkite nuo failų įkėlimo į šį aplanką.'
          }}
        </p>
        <div v-if="!props.selectionMode && !search" class="flex flex-wrap gap-2 justify-center">
          <Button size="sm" @click="isUploadMode = true">
            <IFluentCloudArrowUp24Regular class="mr-2 h-4 w-4" />
            Įkelti failą
          </Button>
          <Button variant="outline" size="sm" @click="showFolderUploadModal = true">
            <IFluentFolderAdd24Regular class="mr-2 h-4 w-4" />
            Sukurti aplanką
          </Button>
          <Button v-if="props.path !== 'public/files'" variant="outline" size="sm" @click="handleBack">
            <IFluentArrowLeft24Regular class="mr-2 h-4 w-4" />
            Grįžti atgal
          </Button>
        </div>
        <div v-else class="flex flex-wrap gap-2 justify-center">
          <Button v-if="search" variant="outline" size="sm" @click="search = ''">
            Išvalyti paiešką
          </Button>
          <Button v-if="props.path !== 'public/files'" variant="outline" size="sm" @click="handleBack">
            <IFluentArrowLeft24Regular class="mr-2 h-4 w-4" />
            Grįžti atgal
          </Button>
        </div>
      </div>
    </div>
    
    <!-- File Properties Panel - shown when file is selected in management mode -->
    <div v-if="!props.selectionMode && selectedFile" class="mt-4">
      <div class="rounded-md bg-muted border border-border p-4">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0">
            <span class="text-vusa-red">
              <!-- PDF files -->
              <IFluentDocumentPdf24Regular v-if="getFileExtension(selectedFile).toLowerCase() === 'pdf'" class="h-8 w-8" />
              <!-- Document files -->
              <IFluentDocumentText24Regular v-else-if="['doc', 'docx', 'odt', 'txt', 'rtf'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Spreadsheet files including CSV -->
              <IFluentDocumentTable24Regular v-else-if="['xls', 'xlsx', 'csv', 'ods'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Video files -->
              <IFluentVideo24Regular v-else-if="['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Audio files -->
              <IFluentMusicNote24Regular v-else-if="['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Archive files -->
              <IFluentFolderZip24Regular v-else-if="['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Code files -->
              <IFluentCode24Regular v-else-if="['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Image files -->
              <IFluentImage24Regular v-else-if="['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'ico'].includes(getFileExtension(selectedFile).toLowerCase())" class="h-8 w-8" />
              <!-- Default fallback for any other file type -->
              <IFluentDocument24Regular v-else class="h-8 w-8" />
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-sm font-medium text-foreground mb-3">
              {{ getFileName(selectedFile) }}
            </h3>
            
            <!-- Grid layout for file info -->
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="flex items-center gap-2">
                <IFluentDocument24Regular class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <span class="text-muted-foreground truncate">{{ getFileExtension(selectedFile) }}</span>
              </div>
              
              <div class="flex items-center gap-2">
                <IFluentStorage24Regular class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <span class="text-muted-foreground">{{ getFileSize(selectedFile) }}</span>
              </div>
              
              <div class="flex items-center gap-2">
                <IFluentCalendar24Regular class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <span class="text-muted-foreground">{{ getFileDate(selectedFile) }}</span>
              </div>
              
              <div class="flex items-center gap-2">
                <IFluentFolder24Regular class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <span class="text-muted-foreground truncate">{{ getRelativePath(selectedFile) }}</span>
              </div>
            </div>
            
            <div class="mt-4 flex gap-2">
              <Button variant="default" size="sm" @click="previewFile(selectedFile!)">
                <IFluentOpen24Regular class="h-4 w-4 mr-1" />
                Peržiūrėti
              </Button>
              <Button variant="destructive" size="sm" @click="deleteFile(selectedFile!)">
                <IFluentDelete24Filled class="h-4 w-4 mr-1" />
                Ištrinti
              </Button>
              <Button variant="outline" size="sm" @click="selectedFile = null">
                <IFluentDismiss24Regular class="h-4 w-4 mr-1" />
                Uždaryti
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    
    <CardModal :show="showFolderUploadModal" title="Pridėti aplanką" @close="showFolderUploadModal = false">
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
    <CardModal :show="showDeleteModal" title="Ištrinti failą" @close="showDeleteModal = false">
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
import { useMessage } from 'naive-ui';

import CardModal from '@/Components/Modals/CardModal.vue';
import FileButton from '../SharepointFileManager/Viewer/FileButton.vue';
import FileUploadArea from '@/Components/FileUpload/FileUploadArea.vue';
import { Spinner } from '@/Components/ui/spinner';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const props = defineProps<{
  directories: any;
  files: any;
  path: string;
  small?: boolean;
  /** Enable file selection mode */
  selectionMode?: boolean;
}>()

const emit = defineEmits<{
  back: [],
  changeDirectory: [directory: string],
  fileSelected: [file: string],
  update: [path: string],
}>()

const message = useMessage();

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

// Split path with public/files and return only the path
const shownPath = computed(() => {

  // Check if path has public/files
  if (props.path === "public/files") {
    return "/";
  }

  if (props.path.includes("public/files")) {
    return props.path.split("public/files")[1];
  }

  return props.path;
});

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

const hasContent = computed(() => {
  const hasDirectories = shownDirectories.value.length > 0;
  const hasFiles = shownFiles.value.length > 0;
  
  // Only count actual content (files and directories), not the back button
  return hasDirectories || hasFiles;
});

const breadcrumbParts = computed(() => {
  if (props.path === 'public/files') return [];
  
  const pathWithoutPublicFiles = props.path.replace('public/files/', '');
  const parts = pathWithoutPublicFiles.split('/');
  
  return parts.map((part, index) => {
    const pathUpToIndex = 'public/files/' + parts.slice(0, index + 1).join('/');
    return {
      name: part,
      path: pathUpToIndex
    };
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
  
  // Always show all directories first, then files
  const availableSpace = Math.max(0, itemsPerPage.value);
  return shownDirectories.value.slice(0, Math.min(availableSpace, shownDirectories.value.length));
});

const paginatedFiles = computed(() => {
  if (itemsPerPage.value >= totalItems.value) return shownFiles.value;
  
  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const directoriesCount = paginatedDirectories.value.length;
  const remainingSpace = itemsPerPage.value - directoriesCount;
  
  if (remainingSpace <= 0) return [];
  
  const fileStartIndex = Math.max(0, startIndex - shownDirectories.value.length);
  const fileEndIndex = fileStartIndex + remainingSpace;
  
  return shownFiles.value.slice(fileStartIndex, fileEndIndex);
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

// Reset pagination when search changes
watch([search, itemsPerPage], () => {
  currentPage.value = 1;
});

// Clear states when switching between upload and browse modes
watch(isUploadMode, (newMode) => {
  if (newMode) {
    // Switching to upload mode - clear browsing states
    selectedFile.value = null;
    clearSelection();
    isMultiSelectMode.value = false;
    // Clear search to avoid confusion
    search.value = '';
    currentPage.value = 1;
  } else {
    // Switching to browse mode - no specific clearing needed
    currentPage.value = 1;
  }
});


const createDirectory = () => {
  loading.value = true;

  router.post(
    route("files.createDirectory"),
    { path: props.path, name: newFolderName.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success("Aplankas sukurtas");
        showFolderUploadModal.value = false;
        loading.value = false;
        emit("update", props.path);
      },
    },
  );
};

const deleteFile = (path: string) => {
  selectedFileForDeletion.value = path;
  showDeleteModal.value = true;
};

const deleteFileConfirmed = () => {
  loading.value = true;
  
  // Check if it's multiple files (contains separator)
  if (selectedFileForDeletion.value.includes('|||')) {
    const filesToDelete = selectedFileForDeletion.value.split('|||');
    
    // Use bulk delete endpoint
    router.delete(route("files.bulkDelete"), {
      data: { paths: filesToDelete },
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success(`${filesToDelete.length} failai ištrinti`);
        clearSelection();
        loading.value = false;
        showDeleteModal.value = false;
        emit("update", props.path);
      },
      onError: () => {
        message.error('Klaida trinant failus');
        loading.value = false;
        showDeleteModal.value = false;
      }
    });
  } else {
    // Single file deletion
    router.delete(
      route("files.delete", { path: selectedFileForDeletion.value }),
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          message.success("Failas ištrintas");
          loading.value = false;
          showDeleteModal.value = false;
          emit("update", props.path);
        },
        onError: () => {
          message.error('Klaida trinant failą');
          loading.value = false;
          showDeleteModal.value = false;
        }
      },
    );
  }
};

function handleChangeDirectory(path: string) {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit("changeDirectory", path);
}

function handleBack() {
  selectedFile.value = null;
  clearSelection();
  currentPage.value = 1;
  emit("back");
}

function handleFileClick(filePath: string, event?: MouseEvent) {
  if (props.selectionMode) {
    // In selection mode, immediately emit selection on single click
    emit('fileSelected', filePath);
    return;
  }
  
  if (isMultiSelectMode.value) {
    // Multi-select mode
    if (selectedFiles.value.has(filePath)) {
      selectedFiles.value.delete(filePath);
    } else {
      selectedFiles.value.add(filePath);
    }
    // Force reactivity update
    selectedFiles.value = new Set(selectedFiles.value);
  } else {
    // Single select mode
    selectedFile.value = filePath === selectedFile.value ? null : filePath;
  }
}

function handleFileDoubleClick(filePath: string) {
  // Double click to select and submit - only in selection mode
  if (props.selectionMode) {
    emit('fileSelected', filePath);
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
  selectedFileForDeletion.value = filesToDelete.join('|||'); // Use separator for multiple files
  showDeleteModal.value = true;
}


function getFileExtension(filePath: string): string {
  const fileName = getFileName(filePath);
  const extension = fileName.split('.').pop()?.toLowerCase();
  return extension ? extension.toUpperCase() : 'Failas';
}

function getFileSize(filePath: string): string {
  const fileInfo = shownFiles.value.find((file: any) => file.path === filePath);
  if (fileInfo?.size) {
    const bytes = fileInfo.size;
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }
  return 'Nežinomas';
}

function getFileDate(filePath: string): string {
  const fileInfo = shownFiles.value.find((file: any) => file.path === filePath);
  if (fileInfo?.modified) {
    return new Date(fileInfo.modified * 1000).toLocaleDateString('lt-LT');
  }
  return 'Nežinoma';
}

function getRelativePath(filePath: string): string {
  // Remove public/files prefix and filename to show just the directory
  const pathWithoutPublicFiles = filePath.replace('public/files/', '');
  const directory = pathWithoutPublicFiles.substring(0, pathWithoutPublicFiles.lastIndexOf('/'));
  return directory || '/';
}

function previewFile(filePath: string) {
  // Convert public/files path to uploads URL
  const url = filePath.replace('public/', '/uploads/');
  window.open(url, '_blank');
}

function handleFileUpload(files: File[]) {
  loading.value = true;
  
  // Convert File objects to the format expected by the backend
  const fileList = files.map(file => ({ file }));
  
  router.post(
    route("files.store"),
    { files: fileList, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success(`${files.length} ${files.length === 1 ? 'failas įkeltas' : 'failai įkelti'}! Peržiūrėkite juos žemiau.`);
        loading.value = false;
        isUploadMode.value = false; // Switch back to browse mode
        uploadAreaRef.value?.clearFiles(); // Clear the upload area
        emit("update", props.path);
      },
      onError: () => {
        message.error('Klaida įkeliant failus');
        loading.value = false;
      }
    },
  );
}

function onFilesSelected(files: File[]) {
  // Optional: Handle file selection events if needed
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

/* Selection badge animation */
.selection-badge-enter-active {
  transition: all 0.3s ease-out;
}

.selection-badge-leave-active {
  transition: all 0.2s ease-in;
}

.selection-badge-enter-from {
  opacity: 0;
  transform: scale(0.5) rotate(-180deg);
}

.selection-badge-leave-to {
  opacity: 0;
  transform: scale(0.5);
}
</style>
