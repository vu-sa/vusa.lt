<template>
  <template v-for="element in content">
    <div v-if="element.type === 'tiptap'" v-html="generateHTMLfromTiptap(element.json_content)" />
  </template>
</template>

<script setup lang="ts">
import { generateHTML } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

const props = defineProps<{
  content: App.Models.Content[];
}>();

console.log(props.content);

function generateHTMLfromTiptap(json_content: App.Models.Content['json_content']) {
  return generateHTML(JSON.parse(json_content), [StarterKit]);
}
</script>
