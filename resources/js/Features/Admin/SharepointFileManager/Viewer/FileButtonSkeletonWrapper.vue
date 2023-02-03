<template>
  <div class="mt-4 inline-flex w-fit max-w-4xl flex-wrap gap-4">
    <div v-if="loading" class="flex flex-col items-center gap-2">
      <NSkeleton
        :sharp="false"
        :height="small ? 96 : 148"
        :width="small ? 96 : 192"
      ></NSkeleton>
      <NSkeleton
        :sharp="false"
        :repeat="2"
        :height="8"
        :width="148"
      ></NSkeleton>
    </div>
    <FileButton
      v-else
      :file="file"
      :small="small"
      :show-thumbnail="showThumbnail"
    ></FileButton>
  </div>
</template>

<script setup lang="ts">
import { NSkeleton } from "naive-ui";

import FileButton from "@/Features/Admin/SharepointFileManager/Viewer/FileButton.vue";

defineProps<{
  file: MyDriveItem;
  loading: boolean;
  small?: boolean;
  showThumbnail: boolean;
}>();
</script>
