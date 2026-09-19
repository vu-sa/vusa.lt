<template>
  <!--
    One window, two presentations. On a phone it takes over the screen so a single
    decision fills the viewport; on a desktop it is a fixed-height dialog, so the
    window does not resize under the pointer as screens swap.
  -->
  <Drawer v-if="isMobile" :open="isOpen" @update:open="onOpenChange">
    <DrawerContent class="h-[92dvh] max-h-[92dvh] p-0">
      <VisuallyHidden>
        <DrawerTitle>{{ $t('action_window.personas.title') }}</DrawerTitle>
      </VisuallyHidden>
      <ActionWindowBody />
    </DrawerContent>
  </Drawer>

  <Dialog v-else :open="isOpen" @update:open="onOpenChange">
    <!--
      Bounded rather than fixed: a floor keeps the window from resizing noticeably
      between steps, a ceiling keeps a long agenda scrolling inside it, and letting
      it shrink below the floor is what stops a two-item menu opening onto a void.
    -->
    <DialogContent
      :show-close-button="false"
      class="flex max-h-[85vh] min-h-[420px] flex-col gap-0 overflow-hidden p-0 sm:max-w-[560px]"
    >
      <VisuallyHidden>
        <DialogTitle>{{ $t('action_window.personas.title') }}</DialogTitle>
      </VisuallyHidden>
      <ActionWindowBody />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { VisuallyHidden } from 'reka-ui';

import ActionWindowBody from './ActionWindowBody.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useOptionalSidebar } from '@/Composables/useOptionalSidebar';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Drawer, DrawerContent, DrawerTitle } from '@/Components/ui/drawer';

const { isOpen, close } = useActionWindow();

// Inside the legacy shell this is SidebarProvider's own media query; the new shell has no
// provider, so it falls back to the shell's `useIsMobile()`.
const { isMobile } = useOptionalSidebar();

const onOpenChange = (open: boolean) => {
  if (!open) {
    close();
  }
};
</script>
