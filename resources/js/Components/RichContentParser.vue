<template>
  <template v-for="element in content" :key="element.id">
    <div v-if="element.type === 'tiptap'" v-html="generateHTMLfromTiptap(element.json_content)" />
    <NCollapse v-else-if="element.type === 'naiveui-collapse'">
      <NCollapseItem v-for="item in element.json_content" :key="item.id" :title="item.label">
        <div v-html="generateHTMLfromTiptap(item.content)" />
      </NCollapseItem>
    </NCollapse>
    <RichContentCard v-else-if="element.type === 'naiveui-card'" :element="element">
      <div v-html="generateHTMLfromTiptap(element.json_content)" />
    </RichContentCard>
  </template>
</template>

<script setup lang="ts">
import { NCollapse, NCollapseItem } from 'naive-ui';
import { generateHTML } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TipTapLink from "@tiptap/extension-link";

import RichContentCard from './RichContentCard.vue';
import Youtube from '@tiptap/extension-youtube';

defineProps<{
  content: App.Models.ContentPart[];
}>();

function generateHTMLfromTiptap(json_content: App.Models.ContentPart['json_content'] | Record<string, never>) {
  if (Object.keys(json_content).length === 0) {
    return '';
  }

  return generateHTML(json_content, [
    StarterKit,
    Table.configure({
      HTMLAttributes: {
        class: "not-prose border-collapse table-auto"
      },
    }),
    TableCell.configure({
      HTMLAttributes: {
        class: "border-b border-t border-zinc-200 p-2",
      },
    }),
    TableHeader.configure({
      HTMLAttributes: {
        class: "p-2 text-left"
      },
    }),
    TableRow,
    TipTapLink,
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video h-auto w-full rounded-xl shadow-lg",
      },
    }),
  ]);
}
</script>
