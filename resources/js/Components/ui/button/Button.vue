<template>
  <Primitive
    data-slot="button"
    :as
    :as-child
    :class="cn(buttonVariants({ variant, voice, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>

<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';

import type { ButtonVariants } from '.';
import { buttonVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

interface Props extends PrimitiveProps {
  variant?: ButtonVariants['variant'];
  voice?: ButtonVariants['voice'];
  size?: ButtonVariants['size'];
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
});
</script>
