<template>
  <Button
    size="sm"
    :variant="editor.isActive(type) ? 'default' : 'outline'"
  >
    <slot name="icon" />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

defineProps<{
  editor: any;
  callback?: any;
  type: string;
}>();
</script>
