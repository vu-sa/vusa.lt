<template>
  <Accordion class="my-1" type="single" collapsible>
    <AccordionItem v-for="item, index in element.json_content" :key="index" class="dark:border-zinc-600"
      :value="`${index}`">
      <AccordionTrigger class="text-lg cursor-pointer tracking-tight">
        {{ item.label }}
      </AccordionTrigger>
      <AccordionContent class="tracking-normal">
        <RichContentTiptapHTML v-if="!html" :json_content="item.content" />
        <div v-else v-html="item.html" />
      </AccordionContent>
    </AccordionItem>
  </Accordion>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';

const RichContentTiptapHTML = defineAsyncComponent(() => import('@/Components/RichContentTiptapHTML.vue'));

defineProps<{
  element: models.ContentPart;
  html?: boolean;
}>();
</script>

<style scoped>
h3 {
  margin-bottom: 0;
}
</style>
