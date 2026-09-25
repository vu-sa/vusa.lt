<template>
  <div
    data-slot="card-header"
    :class="cn(cardHeaderVariants({ size: props.size }), props.class)"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cardHeaderVariants, type CardHeaderVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  size?: CardHeaderVariants['size'];
  class?: HTMLAttributes['class'];
}>(), {
  size: 'default',
  class: undefined,
});
</script>
