<template>
  <Accordion class="not-typography mb-3 mt-1" type="single" collapsible>
    <AccordionItem v-for="item, index in element.json_content" :key="index" :value="`${index}`">
      <AccordionTrigger>{{ item.label }}</AccordionTrigger>
      <AccordionContent>
        <RichContentTiptapHTML v-if="!html" :json_content="item.content" />
        <div v-else v-html="item.html" />
      </AccordionContent>
    </AccordionItem>
  </Accordion>
</template>

<script setup lang="ts">
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ShadcnVue/ui/accordion'
import { defineAsyncComponent } from 'vue';

const RichContentTiptapHTML = defineAsyncComponent(() => import('@/Components/RichContentTiptapHTML.vue'));

defineProps<{
  element: models.ContentPart;
  html?: boolean;
}>();
</script>
