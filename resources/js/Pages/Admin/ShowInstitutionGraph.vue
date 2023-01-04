<template>
  <PageContent :title="`Institucijų vizualizacija`">
    <Graph
      :institution-relationships="institutionRelationships"
      :institutions="institutions"
    />
  </PageContent>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

import Graph from "@/Components/Graphs/InstitutionGraph.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  institutions: App.Models.Institution[];
  institutionRelationships: Record<string, any>[];
}>();
</script>
