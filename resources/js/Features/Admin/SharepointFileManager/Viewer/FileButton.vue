<template>
  <button
    v-bind="$attrs"
    type="button"
    :class="[
      small ? 'size-28' : 'size-48',
      'grid cursor-pointer grid-rows-[7fr_4fr] items-center',
      'border border-border bg-card p-0 transition',
      'hover:bg-accent/50 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    ]"
  >
    <div class="flex h-full items-center justify-center overflow-hidden">
      <FadeTransition mode="out-in">
        <img
          v-if="thumbnail && showThumbnail"
          class="size-full object-cover object-top"
          :src="thumbnail"
          :alt="name"
        >
        <component
          :is="icon"
          v-else
          :class="small ? 'size-[30px]' : 'size-14'"
          class="my-auto text-muted-foreground"
        />
      </FadeTransition>
    </div>
    <div class="flex size-full flex-col justify-center overflow-auto bg-card text-foreground">
      <span :class="[small ? 'text-xs' : 'text-sm']" class="line-clamp-2 break-words px-3">{{ name }}</span>
    </div>
  </button>
  <span v-if="$slots.belowButton" class="m-2 mx-auto line-clamp-1 w-4/5 text-center text-xs text-muted-foreground">
    <slot name="below-button" />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { File, FileSpreadsheet, FileText, Folder } from 'lucide-vue-next';

import FadeTransition from '@/Components/Transitions/FadeTransition.vue';

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

  if (props.iconString === 'file') {
    return File;
  }

  if (props.iconString === 'file-word' || props.iconString === 'file-pdf') {
    return FileText;
  }

  if (props.iconString === 'file-excel') {
    return FileSpreadsheet;
  }

  return File;
});
</script>
