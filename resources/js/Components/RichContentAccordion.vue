<template>
  <Accordion class="not-typography mb-3 mt-1" type="single" collapsible>
    <AccordionItem v-for="item, index in element.json_content" :key="index" :value="`${index}`">
      <AccordionTrigger>{{ item.label }}</AccordionTrigger>
      <AccordionContent>
        <div v-html="html ? item.content : generateHTMLfromTiptap(item.content)" />
      </AccordionContent>
    </AccordionItem>
  </Accordion>
</template>

<script setup lang="ts">
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ShadcnVue/ui/accordion'

import { generateHTMLfromTiptap } from './richContent'

defineProps<{
  element: models.ContentPart;
  html?: boolean;
}>();
</script>
