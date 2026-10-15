<template>
  <!-- `ui/command`'s CommandDialog hard-codes its content classes and `ui/` is never hand-edited,
       so the palette composes Dialog + Command itself to go full-screen below `sm`. -->
  <Dialog v-bind="forwarded">
    <DialogHeader class="sr-only">
      <DialogTitle>{{ title }}</DialogTitle>
      <DialogDescription>{{ description }}</DialogDescription>
    </DialogHeader>
    <DialogContent
      class="gap-0 overflow-hidden p-0 max-sm:top-0 max-sm:left-0 max-sm:h-dvh max-sm:max-w-none max-sm:translate-x-0 max-sm:translate-y-0 max-sm:border-0 sm:max-w-xl"
    >
      <Command>
        <slot />
      </Command>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import type { DialogRootEmits, DialogRootProps } from 'reka-ui';
import { useForwardPropsEmits } from 'reka-ui';

import { Command } from '@/Components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const props = defineProps<DialogRootProps & {
  title: string;
  description: string;
}>();
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>
