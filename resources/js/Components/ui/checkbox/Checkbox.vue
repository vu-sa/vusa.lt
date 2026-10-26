<template>
  <CheckboxRoot
    data-slot="checkbox"
    v-bind="forwarded"
    :class="cn(checkboxVariants({ size }), props.class)"
  >
    <CheckboxIndicator data-slot="checkbox-indicator"
      class="flex items-center justify-center text-current transition-none">
      <slot>
        <Minus v-if="props.modelValue === 'indeterminate'" />
        <Check v-else />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>

<script setup lang="ts">
import type { CheckboxRootEmits, CheckboxRootProps } from 'reka-ui';
import { Check, Minus } from '@lucide/vue';
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { checkboxVariants, type CheckboxVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<CheckboxRootProps & { size?: CheckboxVariants['size']; class?: HTMLAttributes['class'] }>();
const emits = defineEmits<CheckboxRootEmits>();

const delegatedProps = computed(() => {
  const { class: _, size: _size, ...delegated } = props;

  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
