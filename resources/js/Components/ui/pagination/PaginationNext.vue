<template>
  <PaginationNext data-slot="pagination-next"
    :class="cn(buttonVariants({ variant: 'ghost', voice, size }), 'gap-1 px-2.5 sm:pr-2.5', props.class)" v-bind="forwarded">
    <slot>
      <span class="hidden sm:block">Next</span>
      <ChevronRightIcon />
    </slot>
  </PaginationNext>
</template>

<script setup lang="ts">
import type { PaginationNextProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ChevronRightIcon } from 'lucide-vue-next';
import { PaginationNext, useForwardProps } from 'reka-ui';

import type { ButtonVariants } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import { buttonVariants } from '@/Components/ui/button';

const props = withDefaults(defineProps<PaginationNextProps & {
  size?: ButtonVariants['size'];
  voice?: ButtonVariants['voice'];
  class?: HTMLAttributes['class'];
}>(), {
  size: 'default',
  voice: undefined,
  class: undefined,
});

const delegatedProps = reactiveOmit(props, 'class', 'size', 'voice');
const forwarded = useForwardProps(delegatedProps);
</script>
