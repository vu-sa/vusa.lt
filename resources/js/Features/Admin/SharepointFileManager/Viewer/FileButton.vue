<template>
  <div class="w-48">
    <button
      class="grid h-48 w-full grid-rows-[7fr_4fr] items-center rounded-lg border shadow-sm transition hover:shadow-md focus:outline-none focus:ring focus:ring-vusa-yellow dark:border-zinc-900 dark:bg-zinc-900 dark:focus:ring-vusa-red"
      :class="gradientClasses"
      @click="$emit('fileButtonClick')"
      @dblclick="handleFileDoubleClick"
    >
      <div
        class="align-center flex h-full justify-center overflow-hidden rounded-t-md"
      >
        <FadeTransition mode="out-in">
          <img
            v-if="file.thumbnails?.[0].large?.url && showThumbnail"
            class="h-full w-full rounded-t-md object-cover object-top"
            :src="file.thumbnails?.[0].large?.url"
          />
          <NIcon
            v-else
            class="my-auto"
            size="64"
            :component="fileTypeIcon"
          ></NIcon>
        </FadeTransition>
      </div>
      <div
        class="flex h-full w-full flex-col justify-center overflow-auto rounded-b-md bg-white dark:bg-zinc-900 dark:text-white"
      >
        <span class="break-words px-2 text-sm line-clamp-2">{{
          file.name
        }}</span>
      </div>
    </button>
    <span
      class="m-2 mx-auto w-4/5 text-center text-xs text-zinc-400 line-clamp-1"
      >{{ file.listItem?.fields?.additionalData?.Type }}</span
    >
  </div>
</template>

<script setup lang="ts">
import { File, FilePdf, FileWord } from "@vicons/fa";
import { NIcon } from "naive-ui";
import { computed } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import type { DriveItem } from "@microsoft/microsoft-graph-types";

defineEmits<{ (event: "fileButtonClick"): void }>();

const props = defineProps<{
  file: DriveItem;
  showThumbnail: boolean;
}>();

const gradientClasses = computed(() => {
  if (
    props.file.listItem?.fields?.additionalData?.Type ===
    "Veiklą reglamentuojantys dokumentai"
  ) {
    return "from-zinc-200 subtle-gray-gradient bg-gradient-to-b";
  }

  if (
    props.file.listItem?.fields?.additionalData?.Type === "Metodinė medžiaga"
  ) {
    return "from-vusa-yellow/30 to-white dark:from-vusa-red/60 dark:to-zinc-700/80 bg-gradient-to-b";
  }

  return "subtle-gray-gradient dark:from-zinc-800/90 dark:to-zinc-700/90";
});

const fileTypeIcon = computed(() => {
  // if word file
  if (props.file.file === undefined) {
    return File;
  }

  if (
    props.file.file?.mimeType ===
    "application/vnd.openxmlformats-officefile.wordprocessingml.file"
  ) {
    return FileWord;
  }

  if (props.file.file?.mimeType === "application/pdf") {
    return FilePdf;
  }

  return File;
});

const handleFileDoubleClick = () => {
  if (props.file.webUrl === null) {
    return;
  }

  window.open(props.file.webUrl, "_blank");
};
</script>
