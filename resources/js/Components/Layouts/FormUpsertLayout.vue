<template>
  <NCard class="subtle-gray-gradient min-w-[450px]">
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
  </NCard>
</template>

<script setup lang="ts">
import { NAlert, NCard } from "naive-ui";
import { computed } from "vue";

const props = defineProps<{
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
