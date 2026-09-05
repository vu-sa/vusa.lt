<template>
  <div class="relative w-full">
    <FileManager
      small
      selection-mode
      class="w-full"
      :files
      :directories
      :path
      :list-loading="loading"
      :search-results="searchResults"
      :searching
      :allow-upload-in-selection="true"
      :upload-accept="props.uploadAccept"
      :upload-extensions="props.uploadExtensions"
      @update="handleUpdate"
      @back="handleBack"
      @change-directory="handleChangeDirectory"
      @search="handleSearch"
      @file-selected="(path, source) => $emit('submit', path, source)"
    />
  </div>
</template>

<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';

import { useFileListing } from './useFileListing';
import FileManager from './FileManager.vue';

defineEmits<(e: 'submit', path: string, source: 'browse' | 'upload') => void>();

const props = defineProps<{
  uploadAccept?: string;
  uploadExtensions?: string[];
}>();

const {
  filesRaw,
  directoriesRaw,
  currentPath,
  loading,
  searchResults,
  searching,
  fetch,
  search,
  clearSearch,
  back,
} = useFileListing('public/files', props.uploadExtensions);

const files = filesRaw as any;
const directories = directoriesRaw as any;
const path = currentPath as any;

// The overlay spinner is gone: FileGrid already renders a skeleton from `list-loading`, and
// two competing spinners over the same region only ever disagreed.
const debouncedSearch = useDebounceFn((query: string) => search(query), 350);

function handleSearch(query: string, recursive: boolean) {
  if (!recursive || query.length < 2) {
    clearSearch();
    return;
  }
  debouncedSearch(query);
}

async function handleBack() {
  clearSearch();
  await back();
}

async function handleChangeDirectory(nextPath: string) {
  clearSearch();
  await fetch(nextPath);
}

async function handleUpdate(nextPath: string) {
  await fetch(nextPath);
}
</script>
