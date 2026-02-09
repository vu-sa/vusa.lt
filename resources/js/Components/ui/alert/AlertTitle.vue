<template>
  <div
    data-slot="alert-title"
    :class="cn('col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight', props.class)"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
