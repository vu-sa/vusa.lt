<template>
  <div :class="cn('relative w-full', props.class)" data-slot="native-select">
    <slot name="icon">
      <component
        :is="icon"
        v-if="icon"
        :class="cn(
          'pointer-events-none absolute top-1/2 -translate-y-1/2 text-muted-foreground',
          size === 'sm' ? 'left-2.5 size-3.5' : 'left-3 size-4'
        )"
        aria-hidden="true"
      />
    </slot>

    <select
      :id
      v-model="model"
      :disabled
      :required
      :aria-label
      :class="cn(
        'w-full appearance-none border border-border bg-secondary/50 text-foreground transition-colors',
        'focus:border-brand focus:bg-background focus:outline-none focus:ring-2 focus:ring-brand/20',
        'disabled:cursor-not-allowed disabled:opacity-50',
        size === 'sm'
          ? ['h-8 text-xs pr-8 pointer-coarse:min-h-11', icon ? 'pl-8' : 'px-2.5']
          : ['h-11 text-sm pr-9 pointer-coarse:min-h-11', icon ? 'pl-9' : 'px-3'],
        error ? 'border-destructive focus:border-destructive focus:ring-destructive/20' : '',
        selectClass
      )"
      @blur="emit('blur', $event)"
      @change="emit('change', $event)"
    >
      <option v-if="placeholder" :value="placeholderValue" :disabled="!placeholderSelectable">
        {{ placeholder }}
      </option>
      <slot>
        <option
          v-for="opt in options"
          :key="String(opt.value)"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </slot>
    </select>

    <ChevronDown
      :class="cn(
        'pointer-events-none absolute top-1/2 -translate-y-1/2 text-muted-foreground',
        size === 'sm' ? 'right-2.5 size-3.5' : 'right-3 size-4'
      )"
      aria-hidden="true"
    />
  </div>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

import { cn } from '@/Utils/Shadcn/utils';

export interface NativeSelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<{
  id?: string;
  options?: NativeSelectOption[];
  placeholder?: string;
  placeholderValue?: string | number | null;
  placeholderSelectable?: boolean;
  disabled?: boolean;
  required?: boolean;
  error?: boolean | string;
  size?: 'default' | 'sm';
  icon?: Component;
  class?: HTMLAttributes['class'];
  selectClass?: HTMLAttributes['class'];
  ariaLabel?: string;
}>(), {
  id: undefined,
  options: () => [],
  placeholder: undefined,
  placeholderValue: '',
  error: undefined,
  size: 'default',
  icon: undefined,
  class: undefined,
  selectClass: undefined,
  ariaLabel: undefined,
});

const emit = defineEmits<{
  change: [event: Event];
  blur: [event: FocusEvent];
}>();

const model = defineModel<string | number | null | undefined>();
</script>
