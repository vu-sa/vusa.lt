<template>
  <div class="space-y-6">
    <Head :title="$t('shell.sections.failai')" />

    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('shell.workspaces.svetaine.title') }} · {{ $t('shell.sections.failai') }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight text-foreground font-heading">
          {{ $t('shell.sections.failai') }}
        </h1>
        <p class="text-sm text-muted-foreground">
          {{ $t('Viešų ir vidinių svetainės failų naršymas, įkėlimas ir valdymas.') }}
        </p>
      </div>
    </div>

    <FileManager
      :files="props.files"
      :directories="props.directories"
      :path="props.path"
      :list-loading="navigating"
      :search-results
      :searching
      @file-selected="openFile"
      @back="handleBack"
      @change-directory="handleChangeDirectory"
      @update="handleUpdate"
      @search="handleSearch"
    />
  </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ref } from 'vue';

import FileManager from '@/Features/Admin/FileManager/FileManager.vue';
import { useFileSearch } from '@/Features/Admin/FileManager/useFileSearch';

const props = defineProps<{
  directories: Array<{ path: string; name: string; type: string }>;
  files: Array<{ path: string; name: string; type: string; size: number; modified: number; mimeType: string }>;
  path: string;
}>();

const navigating = ref(false);
const { results: searchResults, searching, search, clear: clearSearch } = useFileSearch();

/**
 * Folder navigation is a real Inertia visit, so the open folder lives in `?path=` and the
 * browser's Back button walks back up the tree. Fetching the listing over JSON instead left
 * the URL on `/mano/files` the whole time, so Back exited the file manager entirely.
 */
function visitPath(nextPath: string) {
  navigating.value = true;
  clearSearch();

  router.get(
    route('files.index'),
    { path: nextPath },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['files', 'directories', 'path'],
      onFinish: () => {
        navigating.value = false;
      },
    },
  );
}

function handleChangeDirectory(nextPath: string) {
  visitPath(nextPath);
}

function handleBack() {
  const segments = props.path.split('/');
  if (segments.length > 2) segments.pop();
  visitPath(segments.join('/'));
}

/** Refresh in place after an upload or delete — not a navigation, so it must not push history. */
function handleUpdate() {
  navigating.value = true;

  router.reload({
    only: ['files', 'directories', 'path'],
    onFinish: () => {
      navigating.value = false;
    },
  });
}

const debouncedSearch = useDebounceFn((query: string) => search(query, props.path), 350);

function handleSearch(query: string, recursive: boolean) {
  if (!recursive || query.length < 2) {
    clearSearch();
    return;
  }
  debouncedSearch(query);
}

function openFile(filePath: string) {
  window.open(filePath.replace(/^public\//, '/uploads/'), '_blank');
}
</script>
