<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      'bg-secondary/80 text-muted-foreground inline-flex h-10 w-full sm:w-fit items-center justify-start sm:justify-center gap-1 border border-border p-1 overflow-x-auto scrollbar-none',
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
