<template>
  <ButtonGroup>
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
    <Button size="sm" :variant="editor.isActive('underline') ? 'default' : 'outline'"
      @click="editor.chain().focus().toggleUnderline().run()">
      <IFluentTextUnderline20Regular />
    </Button>
  </ButtonGroup>
</template>

<script setup lang="ts">
import TipTapMarkButton from '@/Features/Admin/CommentViewer/TipTap/TipTapMarkButton.vue';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';

defineModel<any>("editor")
</script>
