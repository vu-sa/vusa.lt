<template>
  <div class="flex items-start justify-between gap-4 py-2.5">
    <span class="shrink-0 text-sm text-muted-foreground">{{ label }}</span>
    <span class="min-w-0 text-right text-sm font-medium text-foreground">
      <slot>{{ value }}</slot>
    </span>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  value?: string | number;
}>();
</script>
