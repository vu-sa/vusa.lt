<template>
  <Head title="Kontaktų kategorija"></Head>

  <FadeTransition appear>
    <div
      class="mt-4 grid grid-cols-1 gap-y-12 px-8 md:grid-cols-3 md:gap-8 lg:px-32 2xl:grid-cols-4"
    >
      <template v-for="institution in institutions" :key="institution.id">
        <CategoryCard :institution="institution"> </CategoryCard>
      </template>
    </div>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";

import CategoryCard from "@/Components/Public/CategoryCard.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  institutions: App.Models.DutyInstitution[];
}>();
</script>
