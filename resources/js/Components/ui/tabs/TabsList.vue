<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      'bg-zinc-100/80 text-zinc-500 inline-flex h-10 w-full sm:w-fit items-center justify-start sm:justify-center gap-1 rounded-xl p-1 dark:bg-zinc-800/60 dark:text-zinc-400 overflow-x-auto scrollbar-none',
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>

<script setup lang="ts">
import { TabsList, type TabsListProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});
</script>
