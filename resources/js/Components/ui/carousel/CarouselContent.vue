<template>
  <div
    ref="carouselRef"
    data-slot="carousel-content"
    class="overflow-hidden"
  >
    <div
      :class="
        cn(
          'flex',
          orientation === 'horizontal' ? '-ml-4' : '-mt-4 flex-col',
          props.class,
        )"
      v-bind="$attrs"
    >
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { WithClassAsProps } from './interface';
import { useCarousel } from './useCarousel';

import { cn } from '@/Utils/Shadcn/utils';

defineOptions({
  inheritAttrs: false,
});

const props = defineProps<WithClassAsProps>();

const { carouselRef, orientation } = useCarousel();
</script>
