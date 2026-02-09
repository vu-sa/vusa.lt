<template>
  <Primitive
    :as
    :as-child
    :class="cn(
      'inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-colors',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2',
      'disabled:pointer-events-none disabled:opacity-50',
      props.class
    )"
    data-slot="upload-trigger"
    :disabled="upload.disabled || !upload.canUpload.value"
    @click="upload.openFileDialog"
  >
    <slot />
  </Primitive>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { inject } from 'vue';
import { Primitive, type PrimitiveProps } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
});

const upload = inject<{
  canUpload: { value: boolean };
  disabled: boolean;
  openFileDialog: () => void;
}>('upload')!;
</script>
