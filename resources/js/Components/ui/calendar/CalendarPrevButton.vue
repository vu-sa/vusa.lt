<template>
  <CalendarPrev
    data-slot="calendar-prev-button"
    :class="cn(
      'flex size-8 items-center justify-center border border-border bg-background',
      'text-foreground transition-colors hover:border-foreground/30 hover:bg-secondary/60',
      'disabled:pointer-events-none disabled:opacity-30',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot>
      <ChevronLeft class="size-4" />
    </slot>
  </CalendarPrev>
</template>

<script lang="ts" setup>
import { ChevronLeft } from 'lucide-vue-next';
import { CalendarPrev, type CalendarPrevProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<CalendarPrevProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
