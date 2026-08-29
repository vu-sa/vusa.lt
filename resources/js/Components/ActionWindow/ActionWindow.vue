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
import { computed } from 'vue';

import ActionWindowBody from './ActionWindowBody.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useSidebar } from '@/Components/ui/sidebar/utils';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Drawer, DrawerContent, DrawerTitle } from '@/Components/ui/drawer';

const { isOpen, close } = useActionWindow();

// The app already owns exactly one media query, in SidebarProvider. Reuse it
// rather than introducing a second source of truth that can disagree with it.
// The null fallback keeps the component mountable outside the provider (tests,
// Storybook), where the desktop dialog is the sensible default.
const sidebar = useSidebar(null as never);
const isMobile = computed(() => sidebar?.isMobile.value ?? false);

const onOpenChange = (open: boolean) => {
  if (!open) {
    close();
  }
};
</script>
