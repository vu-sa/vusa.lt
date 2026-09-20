<template>
  <div
    role="radiogroup"
    :aria-label="label"
    class="inline-flex h-9 border border-border bg-background p-0.5 pointer-coarse:h-11"
    data-slot="overview-scope-switch"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      :class="[
        'px-3 text-sm font-medium whitespace-nowrap transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring',
        modelValue === option.value
          ? 'bg-foreground text-background'
          : 'text-muted-foreground hover:text-foreground',
      ]"
      @click="emit('update:modelValue', option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string;
  options: { value: string; label: string }[];
  /** Names the filter for assistive tech, e.g. "Rodyti". */
  label: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>
