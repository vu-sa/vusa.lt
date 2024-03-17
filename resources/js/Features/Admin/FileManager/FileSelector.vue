<template>
  <NSpin class="w-full" :show="loading">
    <FileManager @update="handleUpdate" small class="w-full" :files="files" :directories="directories" :path="path" @back="handleBack"
      @change-directory="handleChangeDirectory" @file-selected="(path) => $emit('submit', path)" />
  </NSpin>
</template>

<script setup lang="ts">
import { NSpin } from 'naive-ui';
import { ref } from 'vue';
import { useFetch } from '@vueuse/core';

import FileManager from './FileManager.vue';

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

  files.value = data.value.files?.map((file, index) => {
    const fileName = file.split("/").slice(-1)[0];
    return { id: index, name: fileName, path: file };
  }) ?? [];

  if (props.fileExtensions) {
    files.value = files.value.filter((file) => {
      return props.fileExtensions?.includes(file.name.split('.').pop());
    });
  }

  directories.value = data.value.directories?.map((directory, index) => {
    const directoryName = directory.split("/").slice(-1)[0];
    return { id: index, name: directoryName, path: directory };
  }) ?? [];

  path.value = data.value?.path ?? "public/files";

  loading.value = false;
}

await getData("public/files");

loading.value = false;
</script>
