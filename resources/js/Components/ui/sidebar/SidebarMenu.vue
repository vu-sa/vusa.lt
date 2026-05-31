<template>
  <ul
    data-sidebar="menu"
    :class="cn('flex w-full min-w-0 flex-col gap-1 group-data-[density=compact]/density:gap-0.5', props.class)"
  >
    <slot />
  </ul>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
