<template>
  <div :class="cn('flex items-center gap-1', props.class)">
    <Button
      type="button"
      variant="outline"
      size="icon"
      class="size-9 shrink-0"
      :disabled="!canDecrement"
      @click="decrement"
    >
      <MinusIcon class="size-4" />
      <span class="sr-only">Decrease</span>
    </Button>
    <Input
      :id
      type="number"
      :model-value="displayValue"
      :min
      :max
      :disabled
      class="text-center [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
      @input="onInputChange"
    />
    <Button
      type="button"
      variant="outline"
      size="icon"
      class="size-9 shrink-0"
      :disabled="!canIncrement"
      @click="increment"
    >
      <PlusIcon class="size-4" />
      <span class="sr-only">Increase</span>
    </Button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { MinusIcon, PlusIcon } from 'lucide-vue-next';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = withDefaults(defineProps<{
  modelValue: number | null | undefined;
  min?: number;
  max?: number;
  step?: number;
  disabled?: boolean;
  class?: string;
  id?: string;
}>(), {
  min: 0,
  max: Infinity,
  step: 1,
  disabled: false,
});

const emit = defineEmits<(e: 'update:modelValue', value: number | null) => void>();

const displayValue = computed(() => props.modelValue == null ? '' : String(props.modelValue));

const canDecrement = computed(() => !props.disabled && props.modelValue != null && props.modelValue > props.min);
const canIncrement = computed(() => !props.disabled && (props.modelValue == null || props.modelValue < props.max));

const increment = () => {
  if (canIncrement.value) {
    if (props.modelValue == null) {
      emit('update:modelValue', props.min);
      return;
    }
    const newValue = Math.min(props.modelValue + props.step, props.max);
    emit('update:modelValue', newValue);
  }
};

const decrement = () => {
  if (canDecrement.value) {
    const newValue = Math.max(props.modelValue! - props.step, props.min);
    emit('update:modelValue', newValue);
  }
};

const onInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.value === '') {
    emit('update:modelValue', null);
    return;
  }
  const value = parseInt(target.value, 10);
  if (!isNaN(value)) {
    const clampedValue = Math.max(props.min, Math.min(value, props.max));
    emit('update:modelValue', clampedValue);
  }
};
</script>
