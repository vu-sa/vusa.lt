<template>
  <div class="main-card">
    <p class="mb-4 text-2xl font-extrabold">
      <slot name="card-header">Informacija</slot>
    </p>
    <NAlert
      v-if="hasErrors"
      class="mb-4"
      title="Pataisykite klaidas"
      type="error"
    >
      <ul class="list-inside">
        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
      </ul>
    </NAlert>
    <slot />
  </div>
</template>

<script setup lang="ts">
import { NAlert } from "naive-ui";
import { computed } from "vue";

const props = defineProps<{
  model: Record<string, any>;
  errors?: Record<string, string>;
}>();

const hasErrors = computed(() => {
  // if undefined, return false
  if (props.errors === undefined) {
    return false;
  }

  return Object.keys(props.errors).length > 0;
});
</script>
