<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(inputVariants({ variant, size }), props.class)"
  >
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';

import { inputVariants, type InputVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  defaultValue?: string | number;
  modelValue?: string | number;
  variant?: InputVariants['variant'];
  size?: InputVariants['size'];
  class?: HTMLAttributes['class'];
}>(), {
  defaultValue: undefined,
  modelValue: undefined,
  variant: 'default',
  size: 'default',
  class: undefined,
});

const emits = defineEmits<(e: 'update:modelValue', payload: string | number) => void>();

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
});
</script>
