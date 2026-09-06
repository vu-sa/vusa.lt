<template>
  <ButtonGroup v-if="!bubble">
    <TipTapMarkButton v-if="showBold" :editor type="bold" data-testid="tiptap-format-bold">
      <template #icon>
        <IFluentTextBold20Regular />
      </template>
    </TipTapMarkButton>
    <TipTapMarkButton :editor type="italic" data-testid="tiptap-format-italic">
      <template #icon>
        <IFluentTextItalic20Regular />
      </template>
    </TipTapMarkButton>
    <Button size="sm" data-testid="tiptap-format-underline" :variant="editor.isActive('underline') ? 'default' : 'outline'"
      @click="editor.chain().focus().toggleUnderline().run()">
      <IFluentTextUnderline20Regular />
    </Button>
  </ButtonGroup>

  <!-- Bubble menu: a flat cluster, not a bordered pill nested inside the bubble's own
       border — that "wrap in a wrap" look is what the ButtonGroup/outline combination
       above produces when floated inside `BubbleMenu`'s already-bordered container. -->
  <div v-else class="flex items-center gap-0.5">
    <TipTapMarkButton v-if="showBold" :editor type="bold" data-testid="tiptap-format-bold" bubble>
      <template #icon>
        <IFluentTextBold20Regular />
      </template>
    </TipTapMarkButton>
    <TipTapMarkButton :editor type="italic" data-testid="tiptap-format-italic" bubble>
      <template #icon>
        <IFluentTextItalic20Regular />
      </template>
    </TipTapMarkButton>
    <Button size="icon-sm" data-testid="tiptap-format-underline" :variant="editor.isActive('underline') ? 'secondary' : 'ghost'"
      @click="editor.chain().focus().toggleUnderline().run()">
      <IFluentTextUnderline20Regular />
    </Button>
  </div>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';

import TipTapMarkButton from '@/Features/Admin/CommentViewer/TipTap/TipTapMarkButton.vue';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';

withDefaults(defineProps<{
  showBold?: boolean;
  /** Flat ghost/secondary icon buttons for a floating bubble menu, instead of the bordered
   *  outline/default `ButtonGroup` pill used in the fixed toolbar. */
  bubble?: boolean;
}>(), {
  // eslint-disable-next-line vue/no-boolean-default -- true is the actual default for every caller but TiptapEditor's toolbar mode.
  showBold: true,
});

defineModel<Editor>('editor', { required: true });
</script>
