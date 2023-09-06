<template>
  <div class="mt-12 flex flex-col gap-4">
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure
        :institution="institution"
        :is-padalinys="isPadaliniai"
      />
      <NDivider />
    </template>
  </div>
</template>

<script setup lang="ts">
import { NDivider } from "naive-ui";
import { computed } from "vue";

import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";

defineProps<{
  institutions: App.Entities.Institution[];
}>();

// check if url ends with kontaktai/kategorija/padaliniai
const isPadaliniai = computed(() => {
  const url = window.location.href;
  return url.endsWith("/padaliniai");
});
</script>
