<template>
  <div class="flex items-start justify-between gap-4 py-2.5" data-slot="preview-fact">
    <dt class="shrink-0 text-sm text-muted-foreground">
      {{ label }}
    </dt>
    <dd
      :class="[
        'min-w-0 text-right text-sm font-medium break-words',
        tone === 'attention' ? 'text-status-attention' : 'text-foreground',
        tabular && 'tabular-nums',
      ]"
    >
      <slot>{{ isBlank ? '—' : value }}</slot>
    </dd>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  label: string;
  value?: string | number | null;
  tabular?: boolean;
  tone?: 'attention';
}>(), {
  value: null,
  tone: undefined,
});

const isBlank = computed(() => props.value === null || props.value === undefined || props.value === '');
</script>
