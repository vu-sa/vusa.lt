<template>
  <div>
    <div class="flex flex-wrap items-center gap-4">
      <FileButtonSkeletonWrapper
        v-for="file in data"
        :key="file.id"
        :file
        small
        show-thumbnail
      />
    </div>
    <FilePropertiesDrawer
      source="sharepoint"
      :sharepoint-file="selectedFile"
      @close="selectedFile = null"
    />
  </div>
</template>

<script setup lang="tsx">
import { provide, ref } from 'vue';
import { useFetch } from '@vueuse/core';

import FileButtonSkeletonWrapper from './FileButtonSkeletonWrapper.vue';

import FilePropertiesDrawer from '@/Features/Admin/FileManager/Components/FilePropertiesDrawer.vue';

const props = defineProps<{
  fileable: {
    id: string | number;
    type: string;
  };
}>();

const selectedFile = ref<MyDriveItem | null>(null);

const { data } = await useFetch(
  route('sharepoint.getTypesDriveItems', {
    type: props.fileable.type,
    id: props.fileable.id,
  }),
).json<MyDriveItem[]>();

const handleFileSelect = (file: MyDriveItem) => {
  if (!file.file) {
    selectedFile.value = null;
    return;
  }

  selectedFile.value = file;
};

// const handleMaskClick = () => {
//   selectedFile.value = null;
// };

const handleFileDblClick = (file: MyDriveItem) => {
  if (file.name === '...') {
    // remove last folder from path
    return;
  }

  if (file.webUrl === null) {
    return;
  }

  if (file.folder) {
    return;
  }

  // TODO: use created link, not weburl
  window.open(file.webUrl, '_blank');
};

provide('handleFileSelect', handleFileSelect);
provide('handleFileDblClick', handleFileDblClick);
</script>
