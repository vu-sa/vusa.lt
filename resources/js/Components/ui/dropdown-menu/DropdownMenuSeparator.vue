<template>
  <DropdownMenuSeparator
    data-slot="dropdown-menu-separator"
    v-bind="delegatedProps"
    :class="cn('bg-zinc-200 -mx-1 my-1 h-px dark:bg-zinc-800', props.class)"
  />
</template>

<script setup lang="ts">
import {
  DropdownMenuSeparator,
  type DropdownMenuSeparatorProps,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<DropdownMenuSeparatorProps & {
  class?: HTMLAttributes['class'];
}>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});
</script>
