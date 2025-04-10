<template>
  <div v-html="html ? element.html : generateHTMLfromTiptap(element.json_content)"></div>
</template>

<script setup lang="ts">
import { generateHTMLfromTiptap } from '@/Components/RichContentTiptapHTML.vue';

defineProps<{
  element: models.ContentPart;
  html?: boolean;
}>();
</script>

