<template>
  <div v-if="hasErrors">
    <div class="font-medium text-vusa-red">Kažkas ne taip...</div>

    <ul class="mt-3 list-disc list-inside text-sm text-vusa-red">
      <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

const inertiaProps = usePage<InertiaProps>().props.value;

const errors = computed(() => {
  console.log(inertiaProps, inertiaProps.errors);
  return inertiaProps.errors;
});

console.log(inertiaProps, inertiaProps.errors);

const hasErrors = computed(() => {
  console.log(inertiaProps, inertiaProps.errors);

  return Object.keys(errors.value).length > 0;
});
</script>
