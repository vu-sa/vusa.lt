<template>
  <div class="flex items-center gap-0.5">
    <TiptapToolButton
      v-for="mark in availableMarks"
      :key="mark.name"
      :data-testid="`tiptap-format-${mark.name}`"
      toggle
      :active="editor.isActive(mark.name)"
      :label="$t(mark.label)"
      @click="toggleMark(mark.name)"
    >
      <component :is="mark.icon" />
    </TiptapToolButton>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Editor } from '@tiptap/core';
import { Bold, Italic, Strikethrough, Underline } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapToolButton from './TiptapToolButton.vue';

const props = withDefaults(defineProps<{
  editor: Editor;
  showBold?: boolean;
  marks?: ('bold' | 'italic' | 'underline' | 'strike')[];
}>(), {
  // eslint-disable-next-line vue/no-boolean-default -- existing editor fields offer bold by default.
  showBold: true,
  marks: () => ['bold', 'italic', 'underline'],
});

const emit = defineEmits<{ engage: [] }>();

function toggleMark(name: string) {
  props.editor.chain().focus().toggleMark(name).run();
  emit('engage');
}

const definitions = [
  { name: 'bold', label: 'rich-content.bold', icon: Bold },
  { name: 'italic', label: 'rich-content.italic', icon: Italic },
  { name: 'underline', label: 'rich-content.underline', icon: Underline },
  { name: 'strike', label: 'rich-content.strikethrough', icon: Strikethrough },
] as const;

const availableMarks = computed(() => definitions.filter(mark =>
  props.marks.includes(mark.name) && Boolean(props.editor.schema.marks[mark.name]) && (mark.name !== 'bold' || props.showBold),
));
</script>
