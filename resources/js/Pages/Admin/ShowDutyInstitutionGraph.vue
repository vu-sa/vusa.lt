<template>
  <PageContent :title="`Institucijų vizualizacija`">
    <Graph
      :duty-institution-relationships="dutyInstitutionRelationships"
      :duty-institutions="dutyInstitutions"
    />
  </PageContent>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

import Graph from "@/Components/Graphs/DutyInstitutionGraph.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  dutyInstitutions: Record<string, any>[];
  dutyInstitutionRelationships: Record<string, any>[];
}>();
</script>
