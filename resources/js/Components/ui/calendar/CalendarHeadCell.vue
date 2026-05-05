<template>
  <CalendarHeadCell
    data-slot="calendar-head-cell"
    :class="cn('text-zinc-500 rounded-md w-8 font-normal text-[0.8rem] dark:text-zinc-400', props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarHeadCell>
</template>

<script lang="ts" setup>
import { CalendarHeadCell, type CalendarHeadCellProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<CalendarHeadCellProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
