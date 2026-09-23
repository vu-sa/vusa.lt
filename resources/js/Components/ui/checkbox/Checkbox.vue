<template>
  <CheckboxRoot data-slot="checkbox" v-bind="forwarded" :class="cn('peer size-4 shrink-0 border border-input bg-background transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive data-[state=checked]:border-brand-fill data-[state=checked]:bg-brand-fill data-[state=checked]:text-brand-foreground data-[state=indeterminate]:border-brand-fill data-[state=indeterminate]:bg-brand-fill data-[state=indeterminate]:text-brand-foreground',
                                                                   props.class)">
    <CheckboxIndicator data-slot="checkbox-indicator"
      class="flex items-center justify-center text-current transition-none">
      <slot>
        <Minus v-if="props.modelValue === 'indeterminate'" class="size-3.5" />
        <Check v-else class="size-3.5" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>

<script setup lang="ts">
import type { CheckboxRootEmits, CheckboxRootProps } from 'reka-ui';
import { Check, Minus } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<CheckboxRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<CheckboxRootEmits>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
