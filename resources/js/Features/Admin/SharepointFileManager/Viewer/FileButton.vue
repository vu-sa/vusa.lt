<template>
  <button v-bind="$attrs" role="button" :class="[small ? 'size-28' : 'size-48']"
    class="grid cursor-pointer grid-rows-[7fr_4fr] items-center rounded-lg border p-0 shadow-xs transition hover:shadow-md focus:outline-hidden focus:ring-3 focus:ring-vusa-yellow dark:border-zinc-900 dark:bg-zinc-900 dark:focus:ring-vusa-red">
    <div class="align-center flex h-full justify-center overflow-hidden rounded-t-md">
      <FadeTransition mode="out-in">
        <img v-if="thumbnail && showThumbnail" class="size-full rounded-t-md object-cover object-top" :src="thumbnail">
        <component :is="icon" v-else :class="small ? 'size-[30px]' : 'size-14'" class="my-auto text-zinc-700 dark:text-zinc-200" />
      </FadeTransition>
    </div>
    <div
      class="flex size-full flex-col justify-center overflow-auto rounded-b-md bg-white text-zinc-700 dark:bg-zinc-900 dark:text-white">
      <span :class="[small ? 'text-xs' : 'text-sm']" class="line-clamp-2 break-words px-3">{{ name }}</span>
    </div>
  </button>
  <span v-if="$slots.belowButton" class="m-2 mx-auto line-clamp-1 w-4/5 text-center text-xs text-zinc-400">
    <slot name="below-button" />
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

import File from "~icons/mdi/File";
import FileExcel from "~icons/mdi/FileExcel";
import FilePdf from "~icons/mdi/FilePdf";
import FileWord from "~icons/mdi/FileWord";
import Folder from "~icons/mdi/Folder";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  iconString: 'folder' | 'file' | 'file-pdf' | 'file-word' | 'file-excel';
  name: string;
  small?: boolean;
  showThumbnail?: boolean;
  thumbnail?: string;
}>();


const icon = computed(() => {
  if (props.iconString === 'folder') {
    return Folder;
  }

  // if word file
  if (props.iconString === 'file') {
    return File;
  }

  if (
    props.iconString === 'file-word'
  ) {
    return FileWord;
  }

  if (props.iconString === 'file-excel') {
    return FileExcel;
  }

  if (props.iconString === 'file-pdf') {
    return FilePdf;
  }

  return File;
});

</script>
