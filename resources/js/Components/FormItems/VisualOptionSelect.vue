<template>
  <div class="grid gap-3" :class="gridColsClass">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :disabled="disabled || option.disabled"
      class="group relative overflow-visible border p-3 text-left transition-all duration-150"
      :class="[
        option.value === modelValue
          ? 'border-brand bg-brand/5 ring-1 ring-brand text-foreground'
          : 'border-border bg-background hover:border-foreground/30 hover:bg-secondary/40 text-foreground',
        (disabled || option.disabled) && 'cursor-not-allowed opacity-40 hover:border-border hover:bg-background',
      ]"
      @click="!disabled && !option.disabled && $emit('update:modelValue', option.value)"
    >
      <div
        class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center bg-brand text-brand-foreground transition-all"
        :class="option.value === modelValue ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
      >
        <Check class="h-2.5 w-2.5 stroke-[3]" />
      </div>
      <div
        class="mb-2 flex justify-center transition-opacity"
        :class="option.value === modelValue ? 'opacity-100 text-brand' : 'opacity-50 group-hover:opacity-75 text-muted-foreground'"
      >
        <component :is="option.icon" :class="iconClass" />
      </div>
      <div class="text-center">
        <span class="text-xs font-semibold">{{ option.label }}</span>
        <p v-if="option.description" class="mt-0.5 text-xs text-muted-foreground">
          {{ option.description }}
        </p>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { Check } from 'lucide-vue-next';

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
  disabled?: boolean;
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
