<template>
  <div class="mt-6">
    <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
      {{ title }}
    </h3>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
}>();
</script>
