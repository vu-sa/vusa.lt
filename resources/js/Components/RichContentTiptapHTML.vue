<template>
  <div v-html="generateHTMLfromTiptap(json_content)" />
</template>

<script setup lang="ts">
import { generateHTML } from '@tiptap/vue-3';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TipTapLink from "@tiptap/extension-link";
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';

import { CustomHeading } from './TipTap/CustomHeading';
import { Video } from './TipTap/Video';

defineProps<{
  json_content: any;
}>();

const generateHTMLfromTiptap = (json_content: any) => {
  if (Object.keys(json_content).length === 0) {
    return '';
  }

  return generateHTML(json_content, [
    StarterKit.configure({
      heading: false,
      codeBlock: false
    }),
    CustomHeading.configure({
      headings: [2, 3],
    }),
    Image,
    Table.configure({
      HTMLAttributes: {
        class: "border-collapse table-auto w-full"
      },
    }),
    TableCell.configure({
      HTMLAttributes: {
        class: "border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left [&[align=center]]:text-center [&[align=right]]:text-right",
      },
    }),
    TableHeader.configure({
      HTMLAttributes: {
        class: "border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left font-bold [&[align=center]]:text-center [&[align=right]]:text-right"
      },
    }),
    TableRow.configure({
      HTMLAttributes: {
        class: "m-0 border-t p-0 even:bg-zinc-100 dark:even:bg-zinc-800/20",
      },
    }),
    TipTapLink,
    Underline,
    Video,
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video h-auto w-full rounded-xl shadow-lg",
      },
    }),
  ]);
}
</script>
