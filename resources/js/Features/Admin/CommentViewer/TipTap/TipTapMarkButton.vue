<template>
  <Button :size="bubble ? 'icon-sm' : 'sm'" :variant="active ? activeVariant : inactiveVariant" @click="toggleMark">
    <slot name="icon" />
  </Button>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { computed } from 'vue';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  editor: Editor;
  type: string;
  /** Flat ghost/brand look for a bubble menu or framed toolbar instead of the fixed toolbar's
   *  bordered outline/default pair — see TiptapFormattingButtons.vue. */
  bubble?: boolean;
}>();

const active = computed(() => props.editor.isActive(props.type));
const activeVariant = computed(() => (props.bubble ? 'brand' : 'default'));
const inactiveVariant = computed(() => (props.bubble ? 'ghost' : 'outline'));

const toggleMark = () => {
  props.editor.chain().focus().toggleMark(props.type).run();
};
</script>
