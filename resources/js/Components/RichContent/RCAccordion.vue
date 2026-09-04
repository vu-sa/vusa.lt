<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :band :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="content" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <Accordion type="single" collapsible class="space-y-4">
      <AccordionItem v-for="(item, index) in element.json_content" :key="index" :value="`item-${index + 1}`"
        class="border border-border rounded-lg overflow-hidden bg-card">
        <AccordionTrigger
          class="px-4 sm:px-6 py-3 sm:py-4 hover:bg-secondary/60 text-left [&[data-state=open]]:bg-secondary/60"
        >
          <span class="font-medium text-foreground text-sm sm:text-base">{{ item.label }}</span>
        </AccordionTrigger>
        <AccordionContent class="px-4 sm:px-6 pb-4 sm:pb-6 pt-2">
          <div
            class="text-muted-foreground mt-3 leading-relaxed text-sm sm:text-base
              [&_a]:text-brand [&_a]:decoration-brand
              dark:[&_a]:text-red-400 dark:[&_a]:decoration-red-400
              [&_a:hover]:text-red-700 dark:[&_a:hover]:text-red-300
            "
          >
            <RichContentTiptapHTML v-if="!html" :json_content="item.content" />
            <div v-else class="rc-prose" v-html="item.html" />
          </div>
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  </RCSection>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

import RCSection from './RCSection.vue';
import type { BandResolution } from './bandLayout';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';

const RichContentTiptapHTML = defineAsyncComponent(() => import('./RichContentTiptapHTML.vue'));

defineProps<{
  element: models.ContentPart;
  html?: boolean;
  /** Content-part id, used as the ToC scroll anchor when this block has a title (see tocAnchors.ts). */
  anchorId?: number | null;
  band?: BandResolution;
}>();
</script>
