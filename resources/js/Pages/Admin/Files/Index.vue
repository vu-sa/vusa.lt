<template>
  <div>
    <Head :title="$t('shell.sections.failai')" />

    <CollectionTitleBand
      :eyebrow="`${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.failai')}`"
      :title="$t('shell.sections.failai')"
      :lead="$t('files.ui.lead')"
      entity-type="file"
    >
      <template #actions>
        <SpotlightPopover
          v-if="props.abilities.createDirectory"
          :title="$t('files.spotlight.title')"
          :description="$t('files.spotlight.body')"
          :is-dismissed="spotlight.isDismissed.value"
          @dismiss="spotlight.dismiss()"
        >
          <Button
            type="button"
            variant="outline"
            voice="sentence"
            size="lg"
            :aria-label="$t('files.ui.add_folder')"
            @click="openCreateFolder"
          >
            <FolderPlus class="size-4" aria-hidden="true" />
            <span class="hidden sm:inline">{{ $t('files.ui.add_folder') }}</span>
          </Button>
        </SpotlightPopover>
        <Button
          v-if="props.abilities.upload"
          type="button"
          variant="brand"
          size="lg"
          @click="fileManagerRef?.openUpload()"
        >
          <Upload class="size-4" aria-hidden="true" />
          {{ $t('files.ui.upload') }}
        </Button>
      </template>
    </CollectionTitleBand>

    <StagingNote topic="files" class="mb-4" />

    <FileManager
      ref="fileManagerRef"
      :files="props.files"
      :directories="props.directories"
      :path="props.path"
      :abilities="props.abilities"
      :list-loading="navigating"
      :refreshing
      :search-results
      :searching
      :initial-state
      @change-directory="visitPath"
      @back="handleBack"
      @update="handleUpdate"
      @search="handleSearch"
      @state-change="syncUrl"
    />
  </div>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { FolderPlus, Upload } from '@lucide/vue';
import { ref } from 'vue';

import FileManager from '@/Features/Admin/FileManager/FileManager.vue';
import type {
  DirectoryEntry,
  FileEntry,
  FileManagerAbilities,
  FileManagerViewState,
  FileViewMode,
  TypeFilter,
} from '@/Features/Admin/FileManager/types';
import { useFileSearch } from '@/Features/Admin/FileManager/useFileSearch';
import { parentPath } from '@/Features/Admin/FileManager/utils';
import CollectionTitleBand from '@/Components/Collection/CollectionTitleBand.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import StagingNote from '@/Components/StagingNote.vue';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  directories: DirectoryEntry[];
  files: FileEntry[];
  path: string;
  abilities: FileManagerAbilities;
}>();

const fileManagerRef = ref<InstanceType<typeof FileManager> | null>(null);
const spotlight = useFeatureSpotlight('files-folder-actions-v1');

const navigating = ref(false);
const refreshing = ref(false);
const { results: searchResults, searching, search, clear: clearSearch } = useFileSearch();

const DEFAULT_STATE: Omit<FileManagerViewState, 'view'> = { q: '', type: 'all', sort: 'name:asc', file: null };

function readInitialState(): Partial<FileManagerViewState> {
  const query = new URL(usePage().url, 'http://localhost').searchParams;
  const state: Partial<FileManagerViewState> = {};

  if (query.get('q')) state.q = query.get('q')!;
  if (query.get('type')) state.type = query.get('type') as TypeFilter;
  if (/^(name|modified|size):(asc|desc)$/.test(query.get('sort') ?? '')) state.sort = query.get('sort') as FileManagerViewState['sort'];
  if (query.get('view') === 'cards' || query.get('view') === 'table') state.view = query.get('view') as FileViewMode;
  if (query.get('file')) state.file = query.get('file');

  return state;
}

const initialState = readInitialState();

/**
 * Folder navigation is a real Inertia visit, so the open folder lives in `?path=` and the
 * browser's Back button walks back up the tree.
 */
function visitPath(nextPath: string) {
  navigating.value = true;
  clearSearch();

  // The type, sort and view carry over to the next folder; the search and open file do not.
  const current = new URL(window.location.href).searchParams;
  const kept = Object.fromEntries(['type', 'sort', 'view'].flatMap(key => (current.get(key) ? [[key, current.get(key)!]] : [])));

  router.get(
    route('files.index'),
    { path: nextPath, ...kept },
    {
      preserveState: true,
      only: ['files', 'directories', 'path', 'abilities'],
      onFinish: () => {
        navigating.value = false;
      },
    },
  );
}

function handleBack() {
  visitPath(parentPath(props.path));
}

/** Refresh in place after an upload — not a navigation, so the list stays and history is untouched. */
function handleUpdate() {
  refreshing.value = true;

  router.reload({
    only: ['files', 'directories', 'path'],
    onFinish: () => {
      refreshing.value = false;
    },
  });
}

/** Filters, sort, view and the open file ride in the URL without a server round trip. */
function syncUrl(state: FileManagerViewState) {
  const url = new URL(window.location.href);
  const params: Record<string, string | null> = {
    q: state.q.trim() || null,
    type: state.type === DEFAULT_STATE.type ? null : state.type,
    sort: state.sort === DEFAULT_STATE.sort ? null : state.sort,
    view: state.view === 'cards' ? null : state.view,
    file: state.file,
  };

  Object.entries(params).forEach(([key, value]) => {
    if (value) {
      url.searchParams.set(key, value);
    }
    else {
      url.searchParams.delete(key);
    }
  });

  const next = `${url.pathname}${url.search}`;
  if (next !== `${window.location.pathname}${window.location.search}`) {
    router.replace({ url: next, preserveState: true, preserveScroll: true });
  }
}

const debouncedSearch = useDebounceFn((query: string) => search(query, props.path), 350);

function handleSearch(query: string, recursive: boolean) {
  if (!recursive || query.length < 2) {
    clearSearch();
    return;
  }
  debouncedSearch(query);
}

function openCreateFolder() {
  void spotlight.dismiss();
  fileManagerRef.value?.openCreateFolder();
}
</script>
