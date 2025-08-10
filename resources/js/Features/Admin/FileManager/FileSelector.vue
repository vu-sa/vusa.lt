<template>
  <Spinner class="w-full" :show="loading">
    <FileManager
      small
      selection-mode
      class="w-full"
      :files
      :directories
      :path
      :allow-upload-in-selection="true"
      :upload-accept="props.uploadAccept"
      :upload-extensions="props.uploadExtensions"
      @update="handleUpdate"
      @back="handleBack"
      @change-directory="handleChangeDirectory"
      @file-selected="(path) => $emit('submit', path)"
    />
  </Spinner>
</template>

<script setup lang="ts">
import { ref, watchEffect } from 'vue';
import { useFileListing } from './useFileListing';
import FileManager from './FileManager.vue';
import { Spinner } from '@/Components/ui/spinner';

defineEmits<{
  (e: 'submit', path: string): void
}>();

const props = defineProps<{
  uploadAccept?: string;
  uploadExtensions?: string[];
}>();

const loading = ref(true);

const { filesRaw, directoriesRaw, currentPath, loading: fetchLoading, fetch, back } = useFileListing('public/files');
const files = filesRaw as any;
const directories = directoriesRaw as any;
const path = currentPath as any;

async function handleBack() {
  loading.value = true;
  await back();
}

async function handleChangeDirectory(nextPath: string) {
  loading.value = true;
  await fetch(nextPath);
}

async function handleUpdate(nextPath: string) {
  loading.value = true;
  await fetch(nextPath);
}

loading.value = fetchLoading.value;
watchEffect(() => {
  loading.value = fetchLoading.value;
});
</script>
