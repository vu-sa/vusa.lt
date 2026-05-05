<template>
  <Dialog :open="show" @update:open="(val) => { if (!val) $emit('close') }">
    <DialogContent class="max-w-3xl" @escape-key-down="$emit('close')">
      <DialogHeader v-if="title">
        <DialogTitle>{{ title }}</DialogTitle>
      </DialogHeader>
      <slot />
      <DialogFooter v-if="$slots.footer">
        <slot name="footer" />
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

defineEmits(['close']);

defineProps<{
  show: boolean;
  title?: string;
}>();
</script>
