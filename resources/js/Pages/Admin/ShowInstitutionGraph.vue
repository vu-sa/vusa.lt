<template>
  <PageContent :title="`Institucijų vizualizacija`">
    <Graph
      :institution-relationships="institutionRelationships"
      :institutions="institutions"
    />
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

import Graph from "@/Components/Graphs/InstitutionGraph.vue";

defineProps<{
  institutions: App.Entities.Institution[];
  institutionRelationships: Record<string, any>[];
}>();
</script>
