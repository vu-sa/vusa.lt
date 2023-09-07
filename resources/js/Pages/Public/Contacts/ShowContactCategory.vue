<template>
  <div class="mt-12 flex flex-col gap-4">
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution="institution" />
      <NDivider
        v-if="institution.id !== institutions[institutions.length - 1].id"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { NDivider } from "naive-ui";

import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";

defineProps<{
  institutions: App.Entities.Institution[];
}>();
</script>
