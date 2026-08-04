<template>
  <div class="grid gap-3" :class="gridColsClass">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :disabled="option.disabled"
      class="group relative overflow-visible rounded-lg border-2 p-3 text-left transition-all duration-200"
      :class="[
        option.value === modelValue
          ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
          : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600',
        option.disabled && 'cursor-not-allowed opacity-40 hover:border-border',
      ]"
      @click="!option.disabled && $emit('update:modelValue', option.value)"
    >
      <div
        class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
        :class="option.value === modelValue ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
      >
        <IFluentCheckmark12Regular class="h-2.5 w-2.5" />
      </div>
      <div
        class="mb-2 flex justify-center transition-opacity"
        :class="option.value === modelValue ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'"
      >
        <component :is="option.icon" :class="iconClass" />
      </div>
      <div class="text-center">
        <span class="text-xs font-medium">{{ option.label }}</span>
        <p v-if="option.description" class="mt-0.5 text-xs text-muted-foreground">
          {{ option.description }}
        </p>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';

import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';

export interface VisualOption {
  value: string;
  label: string;
  description?: string;
  icon: Component;
  disabled?: boolean;
}

const props = withDefaults(defineProps<{
  modelValue: string;
  options: VisualOption[];
  /** Grid columns from `md:` breakpoint up. Below that it's always 2 columns. */
  columns?: number;
  iconClass?: string;
}>(), {
  columns: 3,
  iconClass: 'h-10 w-16',
});

defineEmits<(e: 'update:modelValue', value: string) => void>();

const gridColsClass = computed(() => {
  const colsMap: Record<number, string> = {
    2: 'grid-cols-2',
    3: 'grid-cols-2 md:grid-cols-3',
    4: 'grid-cols-2 md:grid-cols-4',
  };
  return colsMap[props.columns] ?? 'grid-cols-2 md:grid-cols-3';
});
</script>
