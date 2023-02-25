<template>
  <Head title="Kontaktų kategorija" />

  <div class="mx-auto mt-16 flex max-w-7xl flex-col gap-4 px-8 lg:px-32">
    <template v-for="institution in institutions" :key="institution.id">
      <PadalinysFigure :institution="institution" />
      <NDivider />
    </template>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { NDivider } from "naive-ui";

import PadalinysFigure from "@/Components/Public/PadalinysFigure.vue";

defineProps<{
  institutions: App.Entities.Institution[];
}>();
</script>
