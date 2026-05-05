<template>
  <CalendarGridRow
    data-slot="calendar-grid-row"
    :class="cn('flex', props.class)" v-bind="forwardedProps"
  >
    <slot />
  </CalendarGridRow>
</template>

<script lang="ts" setup>
import { CalendarGridRow, type CalendarGridRowProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<CalendarGridRowProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
