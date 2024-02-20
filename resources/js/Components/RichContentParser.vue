<template>
  <template v-for="element in content">
    <div v-if="element.type === 'tiptap'" v-html="generateHTMLfromTiptap(element.json_content)" />
    <NCollapse v-else-if="element.type === 'naiveui-collapse'">
      <NCollapseItem v-for="item in element.json_content" :key="item.id" :title="item.label">
        <div v-html="generateHTMLfromTiptap(item.content)" />
      </NCollapseItem>
    </NCollapse>
  </template>
</template>

<script setup lang="ts">
import { generateHTML } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { NCollapse, NCollapseItem } from 'naive-ui';

const props = defineProps<{
  content: App.Models.Content[];
}>();

console.log(props.content);

function generateHTMLfromTiptap(json_content: App.Models.Content['json_content']) {
  return generateHTML(json_content, [StarterKit]);
}
</script>
