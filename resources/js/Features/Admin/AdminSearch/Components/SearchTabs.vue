<template>
  <div class="flex items-center gap-1 overflow-x-auto">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      :class="[
        'flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm transition-colors',
        tab.value === modelValue
          ? 'border-primary font-semibold text-foreground'
          : 'border-transparent font-medium text-muted-foreground hover:text-foreground',
      ]"
      @click="$emit('update:modelValue', tab.value)"
    >
      {{ tab.label }}
      <span
        v-if="tab.count != null"
        :class="[
          'rounded-full px-1.5 text-xs tabular-nums',
          tab.value === modelValue ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
        ]"
      >
        {{ tab.count }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
export interface SearchTab {
  value: string;
  label: string;
  count?: number;
}

defineProps<{
  tabs: SearchTab[];
  modelValue: string;
}>();

defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>
