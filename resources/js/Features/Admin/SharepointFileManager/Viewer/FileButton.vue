<template>
  <div class="w-48">
    <button
      class="grid h-48 w-full grid-rows-[7fr_4fr] items-center rounded-lg border shadow-sm transition hover:shadow-md focus:outline-none focus:ring focus:ring-vusa-yellow dark:border-zinc-900 dark:bg-zinc-900 dark:focus:ring-vusa-red"
      :class="gradientClasses"
      @click="$emit('fileButtonClick')"
    >
      <div class="align-center flex justify-center">
        <NIcon size="64" :component="fileTypeIcon"></NIcon>
      </div>
      <div
        class="flex h-full w-full flex-col justify-center overflow-auto rounded-b-md bg-white dark:bg-zinc-900 dark:text-white"
      >
        <span class="break-words px-2 text-sm line-clamp-2">{{
          document.name
        }}</span>
      </div>
    </button>
    <span
      class="m-2 mx-auto w-4/5 text-center text-xs text-zinc-400 line-clamp-1"
      >{{ document.type }}</span
    >
  </div>
</template>

<script setup lang="ts">
import { File, FilePdf, FileWord } from "@vicons/fa";
import { NIcon } from "naive-ui";
import { computed } from "vue";

defineEmits<{ (event: "fileButtonClick"): void }>();

const props = defineProps<{
  // TODO: define document interface
  document: any;
}>();

const gradientClasses = computed(() => {
  if (props.document.type === "Veiklą reglamentuojantys dokumentai") {
    return "from-zinc-200 subtle-gray-gradient bg-gradient-to-b";
  }

  if (props.document.type === "Metodinė medžiaga") {
    return "from-vusa-yellow/30 to-white dark:from-vusa-red/60 dark:to-zinc-700/80 bg-gradient-to-b";
  }

  return "subtle-gray-gradient dark:from-zinc-800/90 dark:to-zinc-700/90";
});

const fileTypeIcon = computed(() => {
  // if word file
  if (props.document.file === undefined) {
    return File;
  }

  if (
    props.document.file.mimeType ===
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
  ) {
    return FileWord;
  }

  if (props.document.file.mimeType === "application/pdf") {
    return FilePdf;
  }

  return File;
});
</script>
