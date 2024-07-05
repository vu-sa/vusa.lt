<template>
  <NButton size="small" :type="editor.isActive(type) ? 'primary' : 'default'" @click="toggleMark">
    <template #icon>
      <slot name="icon" />
    </template>
  </NButton>
</template>

<script setup lang="ts">
const props = defineProps<{
  editor: any;
  type: string;
}>();

const toggleMark = () => {
  props.editor.chain().focus().toggleMark(props.type).run();
};
</script>
