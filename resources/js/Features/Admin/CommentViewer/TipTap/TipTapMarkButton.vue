<template>
  <NButton
    size="small"
    :type="editor.isActive(type) ? 'primary' : 'default'"
    @click="toggleMark"
  >
    <template #icon>
      <NIcon :component="icon" />
    </template>
  </NButton>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";

const props = defineProps<{
  editor: any;
  type: string;
  icon: any;
}>();

const toggleMark = () => {
  props.editor.chain().focus().toggleMark(props.type).run();
};
</script>
