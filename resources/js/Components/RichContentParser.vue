<template>
  <template v-for="element in content" :key="element.id">
    <div v-if="element.type === 'tiptap'" v-html="html ? element.html : generateHTMLfromTiptap(element.json_content)" />
    <Accordion v-else-if="element.type === 'shadcn-accordion'" class="not-typography mb-3 mt-1" type="single" collapsible>
      <AccordionItem v-for="item, index in element.json_content" :key="index" :value="`${index}`">
        <AccordionTrigger>{{ item.label }}</AccordionTrigger>
        <AccordionContent>
          <div v-html="html ? item.content : generateHTMLfromTiptap(item.content)" />
        </AccordionContent>
      </AccordionItem>
    </Accordion>
    <RichContentCard v-else-if="element.type === 'shadcn-card'" class="not-typography mt-4" :element="element">
      <div v-html="html ? element.html : generateHTMLfromTiptap(element.json_content)" />
    </RichContentCard>
    <div v-else-if="element.type === 'image-grid'" class="mt-4">
      <NImageGroup :show-toolbar="false">
        <div class="grid grid-flow-row-dense grid-cols-6 gap-4">
          <div v-for="(image, index) in element.json_content" :key="index" :class="getClassesForImage(image.colspan)">
            <NImage :src="image.image" width="100%" class="size-full rounded-md shadow-sm" object-fit="cover" />
          </div>
        </div>
      </NImageGroup>
    </div>
  </template>
</template>

<script setup lang="ts">
import { NImage, NImageGroup } from 'naive-ui';
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

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ShadcnVue/ui/accordion'
import { CustomHeading } from './TipTap/CustomHeading';
import { Video } from './TipTap/Video';
import RichContentCard from './RichContentCard.vue';

defineProps<{
  content: models.ContentPart[];
  html?: boolean;
}>();

const getClassesForImage = (colspan: string) => {
  if (colspan === 'col-span-full') {
    return `h-48 md:h-60 ${colspan}`;
  }

  return `md:h-40 ${colspan}`;
};

function generateHTMLfromTiptap(json_content: models.ContentPart['json_content'] | Record<string, never>) {
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

<style>
.not-typography {
  p {
    margin-top: 0.5rem;

    &:last-child {
      margin-bottom: 0;
    }
  }
}
</style>
