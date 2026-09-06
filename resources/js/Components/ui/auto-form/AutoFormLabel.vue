<template>
  <FormLabel>
    <slot />
    <span v-if="required" class="text-destructive"> *</span>
  </FormLabel>
</template>

<script setup lang="ts">
import { FormLabel } from '@/Components/ui/form';

defineProps<{
  required?: boolean;
}>();
</script>
