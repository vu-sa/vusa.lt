<template>
  <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="label">
    <span class="text-xs text-muted-foreground">{{ label }}:</span>
    <button
      v-for="option in options"
      :key="option"
      type="button"
      :aria-pressed="modelValue.includes(option)"
      :class="controlVariants({ size: 'sm', active: modelValue.includes(option), voice: 'sentence' })"
      @click="toggle(option)"
    >
      {{ option }} {{ unit }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { controlVariants } from '@/Components/ui/control';

const props = defineProps<{
  label: string;
  options: number[];
  unit: string;
  modelValue: number[];
}>();

const emit = defineEmits<{
  'update:modelValue': [value: number[]];
}>();

const toggle = (option: number) => {
  const next = props.modelValue.includes(option)
    ? props.modelValue.filter(value => value !== option)
    : [...props.modelValue, option];

  emit('update:modelValue', next.sort((a, b) => b - a));
};
</script>
