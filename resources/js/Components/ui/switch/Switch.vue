<template>
  <SwitchRoot
    v-bind="forwarded"
    :class="cn(
      'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center border border-input bg-background px-[3px] transition-colors outline-none hover:border-foreground/60 focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-brand-fill data-[state=checked]:bg-brand-fill',
      props.class,
    )"
  >
    <SwitchThumb
      :class="cn('pointer-events-none block size-4 bg-muted-foreground transition-[translate,background-color] duration-150 data-[state=checked]:translate-x-5 data-[state=checked]:bg-brand-foreground')"
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>

<script setup lang="ts">
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import {
  SwitchRoot,
  SwitchThumb,
  useForwardPropsEmits,
} from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class'] }>();

const emits = defineEmits<SwitchRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
