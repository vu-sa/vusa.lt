<template>
  <Button size="sm" :variant="editor.isActive(type) ? 'default' : 'outline'" @click="toggleMark">
    <slot name="icon" />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  editor: any;
  type: string;
}>();

const toggleMark = () => {
  props.editor.chain().focus().toggleMark(props.type).run();
};
</script>
