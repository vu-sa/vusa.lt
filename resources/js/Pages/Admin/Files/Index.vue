<template>
  <div class="space-y-6">
    <Head :title="$t('shell.sections.failai')" />

    <!-- Header -->
    <div class="flex flex-col gap-6 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.24em] text-brand">
          <HardDrive class="size-3.5" aria-hidden="true" />
          {{ $t('shell.workspaces.svetaine.title') }} · {{ $t('shell.sections.failai') }}
        </span>
        <h1 class="u-display mt-3 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
          {{ $t('shell.sections.failai') }}
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-muted-foreground text-pretty">
          {{ $t('Viešų ir vidinių svetainės failų naršymas, įkėlimas ir valdymas.') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          :class="[
            'inline-flex min-h-11 items-center gap-2 bg-brand-fill px-4 text-sm font-bold uppercase tracking-wide',
            'text-brand-foreground transition-colors hover:bg-brand-fill/90',
          ]"
          @click="fileManagerRef?.openUpload()"
        >
          <Upload class="size-4" aria-hidden="true" />
          {{ $t('files.ui.upload') }}
        </button>
        <button
          type="button"
          class="inline-flex min-h-11 items-center gap-2 border border-border bg-secondary/40 px-4 text-sm font-semibold text-foreground transition-colors hover:bg-secondary"
          @click="fileManagerRef?.openCreateFolder()"
        >
          <FolderPlus class="size-4" aria-hidden="true" />
          <span class="hidden sm:inline">{{ $t('files.ui.add_folder') }}</span>
        </button>
      </div>
    </div>

    <FileManager
      ref="fileManagerRef"
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
import { FolderPlus, HardDrive, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

import FileManager from '@/Features/Admin/FileManager/FileManager.vue';
import { useFileSearch } from '@/Features/Admin/FileManager/useFileSearch';

const fileManagerRef = ref<InstanceType<typeof FileManager> | null>(null);

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
