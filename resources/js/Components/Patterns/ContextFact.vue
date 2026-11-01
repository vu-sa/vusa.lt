<template>
  <div class="contents" data-slot="context-fact">
    <dt class="text-muted-foreground">
      {{ label }}
    </dt>
    <dd class="min-w-0 font-medium text-foreground">
      <slot>
        <span class="block truncate">{{ value }}</span>
      </slot>
    </dd>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  value?: string | number | null;
}>();
</script>
