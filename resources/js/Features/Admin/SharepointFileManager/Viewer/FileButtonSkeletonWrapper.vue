<template>
  <div class="mt-4 inline-flex w-fit max-w-4xl flex-wrap gap-4">
    <div v-if="loading" class="flex flex-col items-center gap-2">
      <NSkeleton :sharp="false" :height="small ? 96 : 148" :width="small ? 96 : 192" />
      <NSkeleton :sharp="false" :repeat="2" :height="8" :width="148" />
    </div>
    <FileButton v-else :icon-string :name="file.name" :small :show-thumbnail
      :class="gradientClasses.concat([small ? 'h-28' : 'h-48'])" :thumbnail="file.thumbnails?.[0]?.large.url"
      @click="handleFileSelect(file)" @dblclick="handleFileDblClick(file)">
      >
      <template #below-button>
        {{ file.listItem?.fields?.properties?.Type }}
      </template>
    </FileButton>
  </div>
</template>

<script setup lang="ts">
import { NSkeleton } from "naive-ui";
import { computed, inject } from "vue";

import FileButton from "@/Features/Admin/SharepointFileManager/Viewer/FileButton.vue";
import type { DriveItem } from "@microsoft/microsoft-graph-types";

const props = defineProps<{
  file: MyDriveItem;
  loading: boolean;
  small?: boolean;
  showThumbnail: boolean;
}>();

const iconString = computed(() => {
  if (props.file.folder) {
    return "folder";
  }

  // if word file
  if (props.file.file === undefined) {
    return "file";
  }

  if (
    props.file.file?.mimeType ===
    "application/vnd.openxmlformats-officefile.wordprocessingml.file"
  ) {
    return "file-word";
  }

  if (props.file.file?.mimeType === "application/pdf") {
    return "file-pdf";
  }

  return "file"
});

const gradientClasses = computed(() => {
  if (
    props.file.listItem?.fields?.properties?.Type ===
    "Veiklą reglamentuojantys dokumentai"
  ) {
    return ["from-zinc-200", "bg-gradient-to-b"];
  }

  if (props.file.listItem?.fields?.properties?.Type === "Metodinė medžiaga") {
    return [
      "from-vusa-yellow/30",
      "to-white",
      "dark:from-vusa-red/60",
      "dark:to-zinc-700/80",
      "bg-gradient-to-b",
    ];
  }

  return ["dark:from-zinc-800/90", "dark:to-zinc-700/90"];
});

const handleFileSelect = inject<(file: DriveItem) => void>(
  "handleFileSelect",
  () => { },
);

const handleFileDblClick = inject<(file: DriveItem) => void>(
  "handleFileDblClick",
  () => { },
);
</script>
