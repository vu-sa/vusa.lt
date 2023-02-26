<template>
  <Head title="Kontaktų kategorija" />

  <div class="mx-auto mt-16 flex max-w-7xl flex-col gap-4 px-8 lg:px-32">
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
import { Head } from "@inertiajs/vue3";
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
