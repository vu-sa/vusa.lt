<template>
  <div class="mt-4 inline-flex w-48 max-w-4xl flex-wrap gap-4">
    <div v-if="loading" class="flex flex-col items-center gap-2">
      <Skeleton :class="small ? 'h-24 w-24' : 'h-36 w-48'" />
      <Skeleton v-for="i in 2" :key="i" class="h-2 w-36" />
    </div>
    <FileButton v-else :icon-string :name="file.name" :small :show-thumbnail
      :thumbnail="file.thumbnails?.[0]?.large.url" @click="handleFileSelect(file)" @dblclick="handleFileDblClick(file)">
      <template #below-button>
        {{ file.listItem?.fields?.properties?.Type }}
      </template>
    </FileButton>
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue';
import type { DriveItem } from '@microsoft/microsoft-graph-types';

import { Skeleton } from '@/Components/ui/skeleton';
import FileButton from '@/Features/Admin/SharepointFileManager/Viewer/FileButton.vue';

const props = defineProps<{
  file: MyDriveItem;
  loading: boolean;
  small?: boolean;
  showThumbnail: boolean;
}>();

const iconString = computed(() => {
  if (props.file.folder) {
    return 'folder';
  }

  if (props.file.file === undefined) {
    return 'file';
  }

  if (
    props.file.file?.mimeType
    === 'application/vnd.openxmlformats-officefile.wordprocessingml.file'
  ) {
    return 'file-word';
  }

  if (props.file.file?.mimeType === 'application/pdf') {
    return 'file-pdf';
  }

  return 'file';
});

const handleFileSelect = inject<(file: DriveItem) => void>(
  'handleFileSelect',
  () => { },
);

const handleFileDblClick = inject<(file: DriveItem) => void>(
  'handleFileDblClick',
  () => { },
);
</script>
