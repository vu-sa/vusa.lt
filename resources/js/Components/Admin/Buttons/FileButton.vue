<template>
  <button
    class="main-card-gradient grid h-48 w-48 grid-rows-[2fr_1fr] items-center rounded-lg border shadow-sm transition hover:shadow-md focus:outline-none focus:ring focus:ring-vusa-yellow dark:border-zinc-900 dark:bg-zinc-900 dark:focus:ring-vusa-red"
  >
    <div class="align-center flex justify-center">
      <NIcon size="64" :component="icon"></NIcon>
    </div>
    <div
      class="flex h-full w-full flex-col justify-center rounded-b-md bg-white dark:bg-zinc-900 dark:text-white"
    >
      <span class="px-2 text-sm line-clamp-2">{{ document.name }}</span>
    </div>
  </button>
</template>

<script setup lang="ts">
import { File, FilePdf } from "@vicons/fa";
import { NIcon } from "naive-ui";
import { computed } from "vue";

const props = defineProps<{
  document: any;
}>();

const icon = computed(() => {
  if (props.document.file.mimeType === "application/pdf") {
    return FilePdf;
  } else {
    return File;
  }
});
</script>
