<template>
  <thead
    data-slot="table-header"
    :class="cn('bg-secondary/50 [&_tr]:border-b [&_tr]:border-border [&_tr:hover]:bg-transparent', props.class)"
  >
    <slot />
  </thead>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
