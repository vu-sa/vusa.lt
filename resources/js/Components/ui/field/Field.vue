<template>
  <div
    role="group"
    data-slot="field"
    :data-orientation="orientation"
    :class="cn(
      fieldVariants({ orientation }),
      props.class,
    )"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import type { FieldVariants } from '.';
import { fieldVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
  orientation?: FieldVariants['orientation'];
}>();
</script>
