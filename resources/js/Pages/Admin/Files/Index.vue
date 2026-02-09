<template>
  <PageContent title="Failų tvarkyklė">
    <FileManager :files="showedFiles" :directories="showedDirectories" :path @file-selected="openFile"
      @back="getAllFilesAndDirectories('../')" @change-directory="getAllFilesAndDirectories" />
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

import FileManager from '@/Features/Admin/FileManager/FileManager.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';

// Declare props
const props = defineProps<{
  directories: Array<{ path: string; name: string; type: string }>;
  files: Array<{ path: string; name: string; type: string; size: number; modified: number; mimeType: string }>;
  path: string;
}>();

// Compute showed directories
const showedDirectories = computed(() => {
  return props.directories.map((directory, index) => {
    return {
      id: index,
      name: directory.name,
      path: directory.path,
    };
  });
});

// Compute showed files
const showedFiles = computed(() => {
  return props.files.map((file, index) => {
    return {
      id: index,
      name: file.name,
      path: file.path,
      size: file.size,
      modified: file.modified,
      mimeType: file.mimeType,
    };
  });
});

// Generate next path from button click
function getNextPath(selectedDirectory) {
  if (selectedDirectory === '../') {
    const arrayPath = props.path.split('/');
    let selectedDirectory = '';
    arrayPath.pop();

    arrayPath.forEach((element) => {
      selectedDirectory += `${element}/`;
    });

    return selectedDirectory.slice(0, -1);
  }
  else return selectedDirectory;
}

function urlPath(path) {
  return path.substring(path.indexOf('/') + 1);
}

function openFile(path) {
  // truncate 'public'
  const url = urlPath(path);
  window.open(`/uploads/${url}`, '_blank');
}

// On directory button click, get all files and directories
async function getAllFilesAndDirectories(selectedDirectory) {
  router.reload({
    data: { path: getNextPath(selectedDirectory) },
  });
}
</script>
