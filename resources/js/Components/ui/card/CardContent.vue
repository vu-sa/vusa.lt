<template>
  <div
    data-slot="card-content"
    :class="cn(cardContentVariants({ size: props.size }), props.class)"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cardContentVariants, type CardContentVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  size?: CardContentVariants['size'];
  class?: HTMLAttributes['class'];
}>(), {
  size: 'default',
  class: undefined,
});
</script>
