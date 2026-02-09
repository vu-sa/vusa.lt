<template>
  <Separator
    data-slot="button-group-separator"
    v-bind="delegatedProps"
    :orientation="props.orientation"
    :class="cn(
      'bg-zinc-200 relative !m-0 self-stretch data-[orientation=vertical]:h-auto dark:bg-zinc-800',
      props.class,
    )"
  />
</template>

<script setup lang="ts">
import type { SeparatorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';

import { cn } from '@/Utils/Shadcn/utils';
import { Separator } from '@/Components/ui/separator';

const props = withDefaults(defineProps<SeparatorProps & { class?: HTMLAttributes['class'] }>(), {
  orientation: 'vertical',
});
const delegatedProps = reactiveOmit(props, 'class');
</script>
