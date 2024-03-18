<template>
  <NButton
    size="small"
    :type="editor.isActive(type) ? 'primary' : 'default'"
    @click="callback"
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
  callback: any;
  type: string;
  icon: any;
}>();
</script>
