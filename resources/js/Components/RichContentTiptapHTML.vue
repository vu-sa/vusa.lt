<template>
  <div v-html="generateHTMLfromTiptap(json_content)" class="tracking-normal" />
</template>

<script setup lang="ts">
import { generateHTML } from '@tiptap/vue-3';
import { StarterKit } from '@tiptap/starter-kit';
import { TableKit } from '@tiptap/extension-table';
import { Youtube } from '@tiptap/extension-youtube';

import { CustomHeading } from './TipTap/CustomHeading';
import { Video } from './TipTap/Video';
import { AccessibleImage } from './TipTap/AccessibleImage';

defineProps<{
  json_content: any;
}>();
</script>

<script lang="ts">
// Export this function so it can be used in other components
export const generateHTMLfromTiptap = (json_content: any) => {
  if (!json_content || Object.keys(json_content).length === 0) {
    return '';
  }

  return generateHTML(json_content, [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      link: {
        HTMLAttributes: {
          class: 'text-blue-500 underline tracking-normal',
        },
      },
    }),
    CustomHeading.configure({
      headings: [2, 3],
    }),
    AccessibleImage.configure({
      HTMLAttributes: {
        class: 'w-full rounded-md',
        loading: 'lazy',
      },
      allowBase64: true,
    }),
    TableKit.configure({
      table: {
        HTMLAttributes: {
          class: "border-collapse table-auto w-full tracking-normal",
        },
      },
      tableCell: {
        HTMLAttributes: {
          class: "border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right",
        },
      },
      tableHeader: {
        HTMLAttributes: {
          class: "border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left font-bold tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right",
        },
      },
      tableRow: {
        HTMLAttributes: {
          class: "m-0 border-t p-0 even:bg-zinc-100 dark:even:bg-zinc-800/20",
        },
      },
    }),
    Video,
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video h-auto w-full rounded-xl shadow-lg",
      },
    }),
  ]);
}
</script>
