<template>
  <div class="relative w-full">
    <!-- Loading overlay -->
    <div v-if="loading" 
      class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm">
      <Spinner class="h-6 w-6" />
    </div>
    
    <FileManager
      small
      selection-mode
      class="w-full"
      :files="files"
      :directories="directories"
      :path="path"
      :allow-upload-in-selection="true"
      :upload-accept="props.uploadAccept"
      :upload-extensions="props.uploadExtensions"
      @update="handleUpdate"
      @back="handleBack"
      @change-directory="handleChangeDirectory"
      @file-selected="(path) => $emit('submit', path)"
    />
  </div>
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
