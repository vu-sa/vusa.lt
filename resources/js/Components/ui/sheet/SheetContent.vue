<template>
  <DialogPortal>
    <SheetOverlay />
    <DialogContent
      data-slot="sheet-content"
      :class="cn(sheetVariants({ side }), props.class)"
      v-bind="{ ...$attrs, ...forwarded }"
    >
      <slot />

      <DialogClose
        :class="[
          'absolute top-4 right-4 opacity-70 transition-opacity hover:opacity-100',
          'focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-hidden',
          'data-[state=open]:bg-accent ring-offset-background disabled:pointer-events-none',
        ]"
      >
        <X class="size-4" />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>

<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { X } from 'lucide-vue-next';
import {
  DialogClose,
  DialogContent,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui';

import SheetOverlay from './SheetOverlay.vue';

import { sheetVariants, type SheetVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

interface SheetContentProps extends DialogContentProps {
  class?: HTMLAttributes['class'];
  side?: SheetVariants['side'];
}

defineOptions({
  inheritAttrs: false,
});

const props = withDefaults(defineProps<SheetContentProps>(), {
  side: 'right',
  class: undefined,
});
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = reactiveOmit(props, 'class', 'side');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
