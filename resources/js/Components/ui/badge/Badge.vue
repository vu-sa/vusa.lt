<template>
  <div :class="cn(badgeVariants({ variant, size, voice }), props.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import type { BadgeVariants } from '.';
import { badgeVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  variant?: BadgeVariants['variant'];
  size?: BadgeVariants['size'];
  voice?: BadgeVariants['voice'];
  class?: HTMLAttributes['class'];
}>();
</script>
