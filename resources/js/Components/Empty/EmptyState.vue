<template>
  <EmptyState v-bind="$attrs">
    <template v-for="(_, name) in $slots" #[name]="slotProps">
      <slot :name v-bind="slotProps ?? {}" />
    </template>
  </EmptyState>
</template>

<script setup lang="ts">
/**
 * @deprecated Legacy wrapper around @/Components/Patterns/EmptyState.vue (PR 2.5).
 * Import EmptyState directly from '@/Components/Patterns/EmptyState.vue'. Slated for removal in Phase 8.
 */
import EmptyState from '@/Components/Patterns/EmptyState.vue';

defineOptions({ inheritAttrs: false });
</script>
