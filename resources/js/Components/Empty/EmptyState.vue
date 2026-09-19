<template>
  <EmptyState v-bind="$attrs">
    <template v-for="(_, name) in $slots" #[name]="slotProps">
      <slot :name v-bind="slotProps ?? {}" />
    </template>
  </EmptyState>
</template>

<script setup lang="ts">
import EmptyState from '@/Components/Patterns/EmptyState.vue';

defineOptions({ inheritAttrs: false });
</script>
