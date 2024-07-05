<template>
  <NButtonGroup>
    <TipTapMarkButton :editor="editor" type="bold">
      <template #icon>
        <IFluentTextBold20Regular />
      </template>
    </TipTapMarkButton>
    <TipTapMarkButton :editor="editor" type="italic">
      <template #icon>
        <IFluentTextItalic20Regular />
      </template>
    </TipTapMarkButton>
    <NButton size="small" :type="editor.isActive('underline') ? 'primary' : 'default'"
      @click="editor.chain().focus().toggleUnderline().run()">
      <template #icon>
        <IFluentTextUnderline20Regular />
      </template>
    </NButton>
  </NButtonGroup>
</template>

<script setup lang="ts">
import TipTapMarkButton from '@/Features/Admin/CommentViewer/TipTap/TipTapMarkButton.vue';

defineModel<any>("editor")
</script>
