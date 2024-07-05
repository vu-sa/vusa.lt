<template>
  <NButton
    size="small"
    :type="editor.isActive(type) ? 'primary' : 'default'"
  >
    <template #icon>
      <slot name="icon" />
    </template>
  </NButton>
</template>

<script setup lang="ts">
defineProps<{
  editor: any;
  callback?: any;
  type: string;
}>();
</script>
