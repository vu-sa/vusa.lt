<template>
  <Button
    data-slot="carousel-previous"
    :disabled="!canScrollPrev"
    :class="cn(
      'absolute size-8 rounded-full',
      orientation === 'horizontal'
        ? 'top-1/2 -left-12 -translate-y-1/2'
        : '-top-12 left-1/2 -translate-x-1/2 rotate-90',
      props.class,
    )"
    :variant
    :size
    @click="scrollPrev"
  >
    <slot>
      <ArrowLeft />
      <span class="sr-only">Previous Slide</span>
    </slot>
  </Button>
</template>

<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';

import type { WithClassAsProps } from './interface';
import { useCarousel } from './useCarousel';

import { cn } from '@/Utils/Shadcn/utils';
import { Button, type ButtonVariants } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
}
& WithClassAsProps>(), {
  variant: 'outline',
  size: 'icon',
});

const { orientation, canScrollPrev, scrollPrev } = useCarousel();
</script>
