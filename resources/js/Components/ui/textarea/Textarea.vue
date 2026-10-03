<template>
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :class="cn(textareaVariants({ variant }), props.class)"
  />
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';

import { textareaVariants, type TextareaVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  defaultValue?: string | number;
  modelValue?: string | number;
  variant?: TextareaVariants['variant'];
  class?: HTMLAttributes['class'];
}>(), {
  defaultValue: undefined,
  modelValue: undefined,
  variant: 'default',
  class: undefined,
});

const emits = defineEmits<(e: 'update:modelValue', payload: string | number) => void>();

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
});
</script>
