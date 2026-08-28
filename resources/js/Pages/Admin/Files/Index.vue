<template>
  <PageContent :title="$t('files.ui.root')">
    <FileManager
      :files
      :directories
      :path
      :list-loading="loading"
      :search-results="searchResults"
      :searching
      @file-selected="openFile"
      @back="handleBack"
      @change-directory="handleChangeDirectory"
      @update="handleUpdate"
      @search="handleSearch"
    />
  </PageContent>
</template>

<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';

import FileManager from '@/Features/Admin/FileManager/FileManager.vue';
import { useFileListing } from '@/Features/Admin/FileManager/useFileListing';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';

// The page still receives the first listing as Inertia props, but navigation runs through the
// same JSON composable the Tiptap picker uses. It used to issue a full `router.reload` per
// folder click, so the two entry points drifted apart and only one of them ever showed a
// loading state.
const props = defineProps<{
  directories: Array<{ path: string; name: string; type: string }>;
  files: Array<{ path: string; name: string; type: string; size: number; modified: number; mimeType: string }>;
  path: string;
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
} = useFileListing(props.path);

const files = filesRaw as any;
const directories = directoriesRaw as any;
const path = currentPath as any;

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

function openFile(filePath: string) {
  window.open(filePath.replace(/^public\//, '/uploads/'), '_blank');
}
</script>
