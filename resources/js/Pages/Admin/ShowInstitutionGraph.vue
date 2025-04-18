<template>
  <PageContent :title="`Institucijų vizualizacija`" :breadcrumbs="breadcrumbs">
    <Graph
      :institution-relationships="institutionRelationships"
      :institutions="institutions"
    />
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import Graph from "@/Components/Graphs/InstitutionGraph.vue";
import { useBreadcrumbs, type BreadcrumbItem } from "@/Composables/useBreadcrumbs";
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import Icons from "@/Types/Icons/filled";

defineProps<{
  institutions: App.Entities.Institution[];
  institutionRelationships: Record<string, any>[];
}>();

// Setup breadcrumbs for the Institution Graph page
const { createBreadcrumbItem } = useBreadcrumbs();

const breadcrumbs = computed((): BreadcrumbItem[] => [
  createBreadcrumbItem($t('Institucijų grafa'), undefined, Icons.INSTITUTION)
]);
</script>
