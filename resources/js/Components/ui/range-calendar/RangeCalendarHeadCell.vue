<template>
  <RangeCalendarHeadCell :class="cn('w-9 text-[0.8rem] font-normal text-muted-foreground', props.class)" v-bind="forwardedProps">
    <slot />
  </RangeCalendarHeadCell>
</template>

<script lang="ts" setup>
import type { RangeCalendarHeadCellProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { RangeCalendarHeadCell, useForwardProps } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<RangeCalendarHeadCellProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>
