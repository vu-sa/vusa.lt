<template>
  <div class="relative" data-slot="file-manager">
    <CreateFolderDialog
      v-model:open="showCreateFolder"
      v-model:busy="busy"
      :inline="props.selectionMode"
      :path="props.path"
      :folder-label="currentFolderLabel"
      @created="afterMutation"
    />

    <div
      :class="[
        'grid grid-cols-1 border-y border-border',
        hasSidebar && 'lg:grid-cols-[260px_minmax(0,1fr)]',
        props.selectionMode && 'border-x',
      ]"
    >
      <FileManagerSidebar
        v-if="hasSidebar"
        :active-view
        :directories="displayedDirectories"
        :current-path="props.path"
        :starred-count="starredEntries.length"
        :files-count="(props.files ?? []).length"
        :total-size
        :can-create-folder="abilities.createDirectory"
        :actions-for="folderActions"
        @update:active-view="setActiveView"
        @open-folder="openFolderPath"
        @folder-action="handleFolderAction"
        @create-folder="openCreateFolder"
      />

      <section class="flex min-w-0 flex-col bg-background" :aria-label="$t('files.ui.files')">
        <FileManagerHeader
          ref="headerRef"
          :path="props.path"
          :search
          :search-everywhere
          :searching="props.searching"
          :is-upload-mode
          :show-upload-toggle="props.selectionMode && props.allowUploadInSelection"
          :type-filter
          :type-counts
          :folder-count="shownDirectories.length"
          :total-count="baseFiles.length"
          :sort-key
          :sort-dir
          :view-mode="view"
          :result-count="displayedFiles.length"
          :title="listTitle"
          :sticky="!props.selectionMode"
          @update:search="search = $event"
          @update:search-everywhere="searchEverywhere = $event"
          @update:type-filter="typeFilter = $event"
          @update:sort="setSort"
          @update:view-mode="view = $event"
          @update:is-upload-mode="isUploadMode = $event"
          @navigate-to-path="openFolderPath"
        />

        <TopProgressBar :active="busy || !!props.refreshing" :label="$t('files.ui.updating')" class="-mt-0.5" />

        <div v-if="isUploadMode" class="flex-1 p-4">
          <div v-if="!props.selectionMode" class="mb-3 flex items-center justify-between gap-2">
            <p class="text-sm text-muted-foreground">
              {{ $t('files.ui.uploading_into') }} <span class="font-medium text-foreground">{{ currentFolderLabel }}</span>
            </p>
            <Button variant="outline" voice="sentence" size="sm" @click="isUploadMode = false">
              <ArrowLeft class="size-4" aria-hidden="true" />
              {{ $t('files.ui.back_to_files') }}
            </Button>
          </div>
          <FileUploadArea
            ref="uploadAreaRef"
            :loading="busy"
            :force-accept="!!props.uploadAccept || !!props.uploadExtensions"
            :accept="props.uploadAccept || '*'"
            :extensions="props.uploadExtensions"
            @upload="handleFileUpload"
          />
        </div>

        <FileGrid
          v-else
          :files="visibleFiles"
          :total-files="displayedFiles.length"
          :has-more="visibleFiles.length < displayedFiles.length"
          :folders="displayedDirectories"
          :view
          :selected-file
          :checked
          :starred="starredSet"
          :all-checked
          :selection-mode="props.selectionMode"
          :loading="props.listLoading"
          :filtered="isFiltered"
          :accepts-drop="abilities.upload && (!props.selectionMode || props.allowUploadInSelection)"
          :folder-name="currentFolderLabel"
          :can-preview="(file) => previewKind(file.name) !== null"
          :file-actions-for="props.selectionMode ? undefined : fileActions"
          :folder-actions-for="props.selectionMode ? undefined : folderActions"
          @file-click="handleFileClick"
          @file-dblclick="handleFileDoubleClick"
          @toggle-check="handleToggleCheck"
          @toggle-check-all="checks.toggleAll"
          @preview="(file) => (previewPath = file.path)"
          @file-action="handleFileAction"
          @open-folder="(directory) => openFolderPath(directory.path)"
          @folder-action="handleFolderAction"
          @drop-files="handleFileUpload"
          @load-more="visibleCount += FILE_PAGE_SIZE"
          @clear-filters="clearFilters"
        />
      </section>
    </div>

    <CollectionSelectionBar
      v-if="!props.selectionMode && checked.size > 0"
      :count="checked.size"
      :count-label="$t('files.ui.checked')"
      @clear="clearChecked"
    >
      <Button variant="outline" voice="sentence" size="sm" @click="starChecked">
        <Star class="size-4" aria-hidden="true" />
        <span class="hidden sm:inline">{{ $t('files.ui.star') }}</span>
      </Button>
      <Button variant="outline" voice="sentence" size="sm" @click="downloadChecked">
        <Download class="size-4" aria-hidden="true" />
        <span class="hidden sm:inline">{{ $t('files.ui.download') }}</span>
      </Button>
      <Button v-if="abilities.update" variant="outline" voice="sentence" size="sm" @click="moveTarget = [...checked]">
        <FolderInput class="size-4" aria-hidden="true" />
        <span class="hidden sm:inline">{{ $t('files.ui.move') }}</span>
      </Button>
      <Button
        v-if="abilities.delete"
        variant="outline"
        voice="sentence"
        size="sm"
        class="text-destructive hover:border-destructive hover:text-destructive"
        @click="pendingDelete = { kind: 'files', paths: [...checked] }"
      >
        <Trash2 class="size-4" aria-hidden="true" />
        <span class="hidden sm:inline">{{ $t('files.ui.delete') }}</span>
      </Button>
    </CollectionSelectionBar>

    <FilePropertiesDrawer
      :file="selectedEntry"
      :selection-mode="props.selectionMode"
      :is-starred="selectedEntry ? starredSet.has(selectedEntry.path) : false"
      :abilities
      @preview="selectedEntry && (previewPath = selectedEntry.path)"
      @toggle-star="selectedEntry && toggleStar(selectedEntry)"
      @insert="selectedEntry && emit('fileSelected', selectedEntry.path, 'browse')"
      @delete="selectedEntry && (pendingDelete = { kind: 'file', paths: [selectedEntry.path] })"
      @rename="selectedEntry && (renameTarget = { path: selectedEntry.path, name: selectedEntry.name, isFolder: false })"
      @move="selectedEntry && (moveTarget = [selectedEntry.path])"
      @copy-link="selectedEntry && copyLink(selectedEntry)"
      @close="selectedFile = null"
    />

    <FilePreviewModal
      v-model="previewPath"
      :files="previewableFiles"
      @details="(file) => { previewPath = null; selectedFile = file.path; }"
      @copy-link="copyLink"
    />

    <ConfirmDialog
      :open="pendingDelete?.kind === 'folder'"
      :title="$t('files.ui.confirm_delete_folder_title')"
      :description="$t('files.ui.confirm_delete_folder_body', { name: deleteFolderName })"
      :confirm-label="$t('files.ui.delete')"
      destructive
      @update:open="(open) => !open && (pendingDelete = null)"
      @confirm="confirmDelete"
    />

    <DeleteFilesDialog
      v-if="pendingDelete && pendingDelete.kind !== 'folder'"
      :paths="pendingDelete.paths"
      @close="pendingDelete = null"
      @confirm="confirmDelete"
    />

    <RenameDialog
      v-if="renameTarget"
      v-bind="renameTarget"
      @close="renameTarget = null"
      @renamed="handleRenamed"
    />

    <MoveDialog
      v-if="moveTarget"
      :paths="moveTarget"
      :from="props.path"
      @close="moveTarget = null"
      @moved="handleMoved"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useClipboard, useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, Download, FolderInput, Star, Trash2 } from '@lucide/vue';

import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import FileUploadArea from '@/Components/FileUpload/FileUploadArea.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import TopProgressBar from '@/Components/Patterns/TopProgressBar.vue';
import { Button } from '@/Components/ui/button';
import { SessionExpiredError } from '@/Composables/apiRequest';
import { uploadFiles, type UploadedFileResult } from '@/Composables/useFileUpload';
import { useToasts } from '@/Composables/useToasts';
import { getFileExtension } from '@/Utils/fileIcons';

import CreateFolderDialog from './Components/CreateFolderDialog.vue';
import DeleteFilesDialog from './Components/DeleteFilesDialog.vue';
import FileGrid from './Components/FileGrid.vue';
import FileManagerHeader from './Components/FileManagerHeader.vue';
import FileManagerSidebar from './Components/FileManagerSidebar.vue';
import FilePreviewModal from './Components/FilePreviewModal.vue';
import FilePropertiesDrawer from './Components/FilePropertiesDrawer.vue';
import MoveDialog from './Components/MoveDialog.vue';
import RenameDialog from './Components/RenameDialog.vue';
import { downloadPaths, fileActionsFor, folderActionsFor, type FolderLike } from './fileActions';
import type { ActiveView, DirectoryEntry, FileEntry, FileManagerAbilities, FileManagerViewState, FileViewMode } from './types';
import { useFileChecks } from './useFileChecks';
import { useStarredFiles } from './useStarredFiles';
import { FILE_PAGE_SIZE, useFileView } from './useFileView';
import { previewKind, publicUrl, relativeToRoot } from './utils';

interface PendingDelete { kind: 'file' | 'files' | 'folder'; paths: string[] }

const props = withDefaults(defineProps<{
  directories: DirectoryEntry[];
  files: FileEntry[];
  path: string;
  small?: boolean;
  /** Picking a file for an editor: clicks choose instead of opening details. */
  selectionMode?: boolean;
  allowUploadInSelection?: boolean;
  uploadAccept?: string;
  uploadExtensions?: string[];
  /** Navigating to another folder: the list is replaced by a skeleton. */
  listLoading?: boolean;
  /** Refreshing the open folder in place: the list stays, a progress line shows. */
  refreshing?: boolean;
  /** Recursive search results, when the caller is searching every folder. */
  searchResults?: FileEntry[] | null;
  searching?: boolean;
  /** Defaults to everything; the server enforces the real permissions either way. */
  abilities?: FileManagerAbilities;
  initialState?: Partial<FileManagerViewState>;
}>(), {
  uploadAccept: undefined,
  uploadExtensions: undefined,
  searchResults: null,
  abilities: () => ({ upload: true, createDirectory: true, deleteDirectory: true, update: true, delete: true }),
  initialState: () => ({}),
});

const emit = defineEmits<{
  back: [];
  changeDirectory: [directory: string];
  fileSelected: [file: string, source: 'browse' | 'upload'];
  update: [path: string];
  search: [query: string, recursive: boolean];
  stateChange: [state: FileManagerViewState];
}>();

const toasts = useToasts();
const { copy } = useClipboard({ legacy: true });

const headerRef = ref<InstanceType<typeof FileManagerHeader> | null>(null);
const uploadAreaRef = ref<InstanceType<typeof FileUploadArea> | null>(null);

const starred = useStarredFiles();
const starredEntries = starred.entries;
const starredSet = starred.paths;

const {
  search,
  searchEverywhere,
  typeFilter,
  sortKey,
  sortDir,
  activeView,
  visibleCount,
  isRecursiveSearch,
  shownDirectories,
  displayedDirectories,
  baseFiles,
  typeCounts,
  displayedFiles,
  visibleFiles,
  isFiltered,
  setSort,
  clearFilters,
} = useFileView({
  files: () => props.files ?? [],
  directories: () => props.directories ?? [],
  searchResults: () => props.searchResults,
  starredEntries,
  initialState: props.initialState,
});

const storedView = useStorage<FileViewMode>('fileManager-view', 'cards');
const view = ref<FileViewMode>(props.initialState.view ?? storedView.value);

const selectedFile = ref<string | null>(props.initialState.file ?? null);
const previewPath = ref<string | null>(null);

const isUploadMode = ref(false);
const busy = ref(false);
const showCreateFolder = ref(false);
const pendingDelete = ref<PendingDelete | null>(null);
const renameTarget = ref<{ path: string; name: string; isFolder: boolean } | null>(null);
const moveTarget = ref<string[] | null>(null);

const hasSidebar = computed(() => !props.small && !props.selectionMode);
const abilities = computed(() => props.abilities);

const previewableFiles = computed(() => displayedFiles.value.filter(file => previewKind(file.name) !== null));

const selectedEntry = computed<FileEntry | null>(() => {
  if (!selectedFile.value) return null;
  return displayedFiles.value.find(file => file.path === selectedFile.value)
    ?? (props.files ?? []).find(file => file.path === selectedFile.value)
    ?? null;
});

const totalSize = computed(() => (props.files ?? []).reduce((sum, file) => sum + (file.size || 0), 0));

const checks = useFileChecks(displayedFiles);
const { checked, allChecked } = checks;

const currentFolderLabel = computed(() => relativeToRoot(props.path).split('/').pop() || $t('files.ui.root'));

const listTitle = computed(() => {
  if (isRecursiveSearch.value) return $t('files.ui.search_everywhere_title', { search: search.value.trim() });
  if (activeView.value === 'starred') return $t('files.ui.view_starred');
  if (activeView.value === 'recent') return $t('files.ui.recent_title', { folder: currentFolderLabel.value });
  return undefined;
});

const viewState = computed<FileManagerViewState>(() => ({
  q: search.value,
  type: typeFilter.value,
  sort: `${sortKey.value}:${sortDir.value}`,
  view: view.value,
  file: selectedFile.value,
}));

watch(viewState, state => emit('stateChange', state));

watch(view, (next) => {
  storedView.value = next;
});

watch([search, searchEverywhere], ([query, recursive]) => {
  emit('search', query.trim(), recursive);
});

watch(() => props.path, () => {
  selectedFile.value = null;
  clearChecked();
  visibleCount.value = FILE_PAGE_SIZE;
  isUploadMode.value = false;
});

/** A file that disappeared (deleted, moved, renamed) cannot stay open or ticked. */
watch(() => props.files, (files) => {
  const present = new Set((files ?? []).map(file => file.path));
  if (selectedFile.value && !present.has(selectedFile.value) && activeView.value === 'browse' && !isRecursiveSearch.value) {
    selectedFile.value = null;
  }
  checks.keepOnly(present);
});

watch(isUploadMode, (uploading) => {
  if (uploading) {
    selectedFile.value = null;
    clearChecked();
  }
});

function openUpload() {
  isUploadMode.value = true;
}

function openCreateFolder() {
  showCreateFolder.value = true;
}

defineExpose({ openUpload, openCreateFolder });

function setActiveView(next: ActiveView) {
  activeView.value = next;
  clearChecked();
}

function openFolderPath(path: string) {
  activeView.value = 'browse';
  search.value = '';
  if (path !== props.path) {
    emit('changeDirectory', path);
  }
}

/** Inertia mutations redirect back, which already refreshes the page's listing; the picker fetches its own. */
function afterMutation() {
  if (props.selectionMode) {
    emit('update', props.path);
  }
}

/* --- Selection --------------------------------------------------------- */

function isSelectable(name: string): boolean {
  if (!props.uploadExtensions?.length) return true;
  return props.uploadExtensions.map(ext => ext.toLowerCase()).includes(getFileExtension(name));
}

function pick(file: FileEntry) {
  if (!isSelectable(file.name)) {
    toasts.error($t('files.ui.cannot_select_file_type'));
    return;
  }
  selectedFile.value = file.path;
  emit('fileSelected', file.path, 'browse');
}

function handleToggleCheck(file: FileEntry, event?: MouseEvent) {
  if (event?.shiftKey) {
    checks.range(file);
  }
  else {
    checks.toggle(file);
  }
}

function handleFileClick(file: FileEntry, event?: MouseEvent) {
  if (props.selectionMode) {
    pick(file);
    return;
  }

  if (event?.shiftKey) {
    checks.range(file);
  }
  else if (event?.ctrlKey || event?.metaKey || checked.value.size > 0) {
    checks.toggle(file);
  }
  else {
    selectedFile.value = selectedFile.value === file.path ? null : file.path;
  }
}

function handleFileDoubleClick(file: FileEntry) {
  if (props.selectionMode) {
    pick(file);
  }
  else if (previewKind(file.name) !== null) {
    selectedFile.value = null;
    previewPath.value = file.path;
  }
}

function clearChecked() {
  checks.clear();
}

/* --- Actions ------------------------------------------------------------ */

const fileActions = (file: FileEntry) => fileActionsFor(file, starredSet.value.has(file.path), abilities.value);
const folderActions = (directory: FolderLike) => folderActionsFor(directory, abilities.value);

function handleFileAction(key: string, file: FileEntry) {
  switch (key) {
    case 'preview':
      previewPath.value = file.path;
      break;
    case 'details':
      selectedFile.value = file.path;
      break;
    case 'copyLink':
      copyLink(file);
      break;
    case 'open':
      window.open(publicUrl(file.path), '_blank', 'noopener');
      break;
    case 'download':
      downloadPaths([file.path]);
      break;
    case 'star':
      toggleStar(file);
      break;
    case 'rename':
      renameTarget.value = { path: file.path, name: file.name, isFolder: false };
      break;
    case 'move':
      moveTarget.value = [file.path];
      break;
    case 'delete':
      pendingDelete.value = { kind: 'file', paths: [file.path] };
      break;
  }
}

function handleFolderAction(key: string, directory: FolderLike) {
  if (key === 'rename') {
    renameTarget.value = { path: directory.path, name: directory.name, isFolder: true };
  }
  else if (key === 'delete') {
    pendingDelete.value = { kind: 'folder', paths: [directory.path] };
  }
}

function toggleStar(file: FileEntry) {
  starred.toggle(file, props.path);
}

function starChecked() {
  const count = starred.star(displayedFiles.value.filter(file => checked.value.has(file.path)), props.path);
  toasts.success($t('files.ui.starred_count', { count: String(count) }));
}

function copyLink(file: FileEntry) {
  void copy(`${window.location.origin}${publicUrl(file.path)}`).then(() => {
    toasts.success($t('files.ui.link_copied'));
  });
}

function downloadChecked() {
  downloadPaths([...checked.value]);
}

/* --- Rename, move, delete ---------------------------------------------- */

function handleRenamed(from: string, to: string) {
  renameTarget.value = null;
  starred.rename(from, to);
  if (selectedFile.value === from) {
    selectedFile.value = to;
  }
  afterMutation();
}

function handleMoved(destination: string) {
  const moved = new Set(moveTarget.value ?? []);
  moveTarget.value = null;
  starred.move([...moved], destination);
  if (selectedFile.value && moved.has(selectedFile.value)) {
    selectedFile.value = null;
  }
  clearChecked();
  afterMutation();
}

const deleteFolderName = computed(() => pendingDelete.value?.paths[0]?.split('/').pop() ?? '');

function confirmDelete() {
  const pending = pendingDelete.value;
  if (!pending || busy.value) return;

  pendingDelete.value = null;
  busy.value = true;

  const removed = new Set(pending.paths);
  const options = {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (selectedFile.value && removed.has(selectedFile.value)) {
        selectedFile.value = null;
      }
      checks.keepOnly(new Set([...checked.value].filter(path => !removed.has(path))));
      starred.forget(pending.paths);
      afterMutation();
    },
    onFinish: () => {
      busy.value = false;
    },
  };

  if (pending.kind === 'folder') {
    router.delete(route('files.deleteDirectory', { path: pending.paths[0] }), options);
  }
  else if (pending.kind === 'files') {
    router.delete(route('files.bulkDelete'), { ...options, data: { paths: pending.paths } });
  }
  else {
    router.delete(route('files.delete', { path: pending.paths[0] }), options);
  }
}

/* --- Upload ------------------------------------------------------------- */

async function handleFileUpload(files: File[], targetPath: string = props.path) {
  if (busy.value) return;
  busy.value = true;

  try {
    const result = await uploadFiles(files, targetPath);

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
    if (error instanceof SessionExpiredError) return;
    toasts.error(error instanceof Error ? error.message : $t('files.errors.upload_all_failed'));
  }
  finally {
    busy.value = false;
  }
}

function selectUploadedFile(uploaded: UploadedFileResult[]) {
  if (!props.selectionMode) return;

  const file = uploaded.find(candidate => isSelectable(candidate.name));
  if (!file) return;

  selectedFile.value = file.path;
  emit('fileSelected', file.path, 'upload');
}

/* --- Keyboard ----------------------------------------------------------- */

function isTyping(target: EventTarget | null): boolean {
  const element = target as HTMLElement | null;
  return !!element && (['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName) || element.isContentEditable);
}

const overlayOpen = computed(() => [selectedFile.value, previewPath.value, pendingDelete.value, renameTarget.value, moveTarget.value]
  .some(value => value !== null) || showCreateFolder.value);

function handleKeyDown(event: KeyboardEvent) {
  if (overlayOpen.value || props.selectionMode || isTyping(event.target)) return;

  if (event.key === 'Escape' && checked.value.size > 0) {
    clearChecked();
  }
  else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a' && displayedFiles.value.length > 0) {
    event.preventDefault();
    checked.value = new Set(displayedFiles.value.map(file => file.path));
  }
  else if (event.key === '/') {
    event.preventDefault();
    headerRef.value?.focusSearch();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

</script>
