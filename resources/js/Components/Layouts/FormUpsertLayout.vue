<template>
  <NCard class="min-w-[450px]">
    <NAlert v-if="hasErrors" class="mb-4" title="Pataisykite klaidas" type="error">
      <ul class="list-inside">
        <li v-for="(error, index) in errors" :key="index">
          {{ error }}
        </li>
      </ul>
    </NAlert>
    <slot />
  </NCard>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const errors = usePage().props.errors;

const hasErrors = computed(() => {
  // if undefined, return false
  if (errors === undefined) {
    return false;
  }

  return Object.keys(errors).length > 0;
});
</script>
