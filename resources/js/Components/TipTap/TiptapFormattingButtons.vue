<template>
  <div class="flex items-center gap-0.5">
    <TiptapToolButton
      v-if="showBold"
      data-testid="tiptap-format-bold"
      toggle
      :active="editor.isActive('bold')"
      :label="$t('rich-content.bold')"
      @click="editor.chain().focus().toggleBold().run()"
    >
      <IFluentTextBold20Regular />
    </TiptapToolButton>
    <TiptapToolButton
      data-testid="tiptap-format-italic"
      toggle
      :active="editor.isActive('italic')"
      :label="$t('rich-content.italic')"
      @click="editor.chain().focus().toggleItalic().run()"
    >
      <IFluentTextItalic20Regular />
    </TiptapToolButton>
    <TiptapToolButton
      data-testid="tiptap-format-underline"
      toggle
      :active="editor.isActive('underline')"
      :label="$t('rich-content.underline')"
      @click="editor.chain().focus().toggleUnderline().run()"
    >
      <IFluentTextUnderline20Regular />
    </TiptapToolButton>
  </div>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapToolButton from './TiptapToolButton.vue';

import IFluentTextBold20Regular from '~icons/fluent/text-bold20-regular';
import IFluentTextItalic20Regular from '~icons/fluent/text-italic20-regular';
import IFluentTextUnderline20Regular from '~icons/fluent/text-underline20-regular';

withDefaults(defineProps<{
  editor: Editor;
  showBold?: boolean;
}>(), {
  // eslint-disable-next-line vue/no-boolean-default -- true is the actual default for every caller but a bold-styled field.
  showBold: true,
});
</script>
