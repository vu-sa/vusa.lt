<template>
  <template v-for="element in content" :key="element.id">
    <div v-if="element.type === 'tiptap'" v-html="generateHTMLfromTiptap(element.json_content)" />
    <Accordion class="accordion" type="single" v-else-if="element.type === 'shadcn-accordion'" collapsible>
      <AccordionItem v-for="item, index in element.json_content" :value="index" :key="index">
        <AccordionTrigger>{{ item.label }}</AccordionTrigger>
        <AccordionContent>
          <div v-html="generateHTMLfromTiptap(item.content)" />
        </AccordionContent>
      </AccordionItem>
    </Accordion>
    <RichContentCard v-else-if="element.type === 'naiveui-card'" :element="element">
      <div v-html="generateHTMLfromTiptap(element.json_content)" />
    </RichContentCard>
  </template>
</template>

<script setup lang="ts">
import { generateHTML } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TipTapLink from "@tiptap/extension-link";
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ShadcnVue/ui/accordion'

import RichContentCard from './RichContentCard.vue';

defineProps<{
  content: App.Models.ContentPart[];
}>();

function generateHTMLfromTiptap(json_content: App.Models.ContentPart['json_content'] | Record<string, never>) {
  if (Object.keys(json_content).length === 0) {
    return '';
  }

  return generateHTML(json_content, [
    StarterKit.configure({
      heading: {
        levels: [2, 3],
      },
      codeBlock: false
    }),
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
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video h-auto w-full rounded-xl shadow-lg",
      },
    }),
  ]);
}
</script>

<style>
.accordion {
  p {
    margin-top: 0.5rem;

    &:last-child {
      margin-bottom: 0;
    }
  }
}
</style>
