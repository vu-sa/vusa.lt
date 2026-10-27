<template>
  <template v-for="list in lists" :key="list.node">
    <TiptapToolButton
      v-if="editor.schema.nodes[list.node]"
      toggle
      :active="editor.isActive(list.node)"
      :label="$t(list.label)"
      :data-testid="`tiptap-${list.node}`"
      @click="toggleList(list.node)"
    >
      <component :is="list.icon" />
    </TiptapToolButton>
  </template>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { List, ListOrdered } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapToolButton from './TiptapToolButton.vue';

const props = defineProps<{ editor: Editor }>();
const emit = defineEmits<{ engage: [] }>();
const lists = [
  { node: 'bulletList', label: 'rich-content.bullet_list', icon: List },
  { node: 'orderedList', label: 'rich-content.ordered_list', icon: ListOrdered },
] as const;

function toggleList(node: 'bulletList' | 'orderedList') {
  const chain = props.editor.chain().focus();
  if (node === 'bulletList') chain.toggleBulletList().run();
  else chain.toggleOrderedList().run();
  emit('engage');
}
</script>
