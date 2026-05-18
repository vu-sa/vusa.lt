<template>
  <PageContent title="Institucijų vizualizacija">
    <Graph
      :institution-relationships
      :institutions
    />
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import Graph from '@/Components/Graphs/InstitutionGraph.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { InstitutionIconFilled } from '@/Components/icons';

defineProps<{
  institutions: App.Entities.Institution[];
  institutionRelationships: Record<string, any>[];
}>();

// Setup breadcrumbs for the Institution Graph page
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('Institucijų grafa'), undefined, InstitutionIconFilled),
]);
</script>
