<template>
  <div class="space-y-2" data-slot="checklist-progress">
    <p class="text-sm text-muted-foreground" aria-live="polite">{{ label }}</p>
    <div role="progressbar" :aria-label="label" :aria-valuenow="resolved" :aria-valuemax="total" :aria-valuemin="0" class="h-1 w-full bg-secondary">
      <div class="h-full bg-foreground" :style="{ width: `${total ? Math.min(100, resolved / total * 100) : 0}%` }" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ resolved: number; total: number; label: string }>();
</script>
