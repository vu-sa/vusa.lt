<template>
  <Primitive
    role="group"
    data-slot="button-group"
    :data-orientation="props.orientation"
    :as
    :as-child
    :class="cn('bg-zinc-100 flex items-center gap-2 rounded-md border border-zinc-200 px-4 text-sm font-medium shadow-xs [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 dark:bg-zinc-800 dark:border-zinc-800', props.class)"
  >
    <slot />
  </Primitive>
</template>

<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';

import type { ButtonGroupVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class'];
  orientation?: ButtonGroupVariants['orientation'];
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
});
</script>
