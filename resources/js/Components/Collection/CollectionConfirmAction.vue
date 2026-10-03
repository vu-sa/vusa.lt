<template>
  <ConfirmDialog
    :open="dialog !== null"
    :title="dialog?.title ?? ''"
    :description="dialog?.description"
    :confirm-label="dialog?.confirmLabel ?? ''"
    :destructive="dialog?.destructive"
    @update:open="open => !open && emit('cancel')"
    @confirm="emit('confirm')"
  />
</template>

<script setup lang="ts">
import { ConfirmDialog } from '@/Components/Patterns';

defineProps<{
  dialog: { title: string; description?: string; confirmLabel: string; destructive: boolean } | null;
}>();

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();
</script>
