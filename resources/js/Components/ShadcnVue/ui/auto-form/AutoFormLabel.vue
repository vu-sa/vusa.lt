<script setup lang="ts">
import { FormLabel } from '@/Components/ShadcnVue/ui/form'

defineProps<{
  required?: boolean
}>()
</script>

<template>
  <FormLabel>
    <slot />
    <span v-if="required" class="text-red-500 dark:text-red-900"> *</span>
  </FormLabel>
</template>
