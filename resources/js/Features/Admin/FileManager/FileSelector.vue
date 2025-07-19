<template>
  <Spinner class="w-full" :show="loading">
    <FileManager 
      small 
      selection-mode
      class="w-full" 
      :files 
      :directories 
      :path 
      @update="handleUpdate" 
      @back="handleBack"
      @change-directory="handleChangeDirectory" 
      @file-selected="(path) => $emit('submit', path)" />
  </Spinner>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useFetch } from '@vueuse/core';

import FileManager from './FileManager.vue';
import { Spinner } from '@/Components/ui/spinner';

defineEmits<{
  (e: 'submit', path: string): void
}>();

const props = defineProps<{
  fileExtensions?: string[];
}>();

const loading = ref(true);

const files = ref([]);
const directories = ref([]);
const path = ref("public/files");

async function handleBack(path: string) {
  loading.value = true;
  await getData(path);
}

async function handleChangeDirectory(path: string) {
  loading.value = true;
  await getData(path);
}

async function handleUpdate(path: string) {
  loading.value = true;
  await getData(path);
}

async function getData(changedDirectory: string) {
  const { data, pending, error, refresh } = await useFetch(route('files.getFiles', {
    path: changedDirectory
  })).get().json();

  // Backend now returns file objects instead of strings
  files.value = data.value.files?.map((file, index) => {
    return { 
      id: index, 
      name: file.name, 
      path: file.path,
      size: file.size,
      modified: file.modified,
      mimeType: file.mimeType
    };
  }) ?? [];

  if (props.fileExtensions) {
    files.value = files.value.filter((file) => {
      return props.fileExtensions?.includes(file.name.split('.').pop());
    });
  }

  // Backend now returns directory objects instead of strings
  directories.value = data.value.directories?.map((directory, index) => {
    return { 
      id: index, 
      name: directory.name, 
      path: directory.path 
    };
  }) ?? [];

  path.value = data.value?.path ?? path.value;

  loading.value = false;
}

await getData("public/files");

loading.value = false;
</script>
