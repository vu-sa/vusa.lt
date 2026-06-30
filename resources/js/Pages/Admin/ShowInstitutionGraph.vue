<template>
  <PageContent title="Institucijų vizualizacija">
    <Graph
      :institution-relationships
      :institutions
      :types
      :type-relationships
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

interface InstitutionGraphEdge {
  source: string;
  target: string;
  direction: 'outgoing' | 'sibling';
  type: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  scope: string;
  bidirectional: boolean;
  relationship_name: string | null;
  relationship_description: string | null;
}

interface TypeGraphNode {
  id: string;
  name: string | null;
  institutions_count: number;
}

defineProps<{
  institutions: App.Entities.Institution[];
  institutionRelationships: InstitutionGraphEdge[];
  types: TypeGraphNode[];
  typeRelationships: InstitutionGraphEdge[];
}>();

// Setup breadcrumbs for the Institution Graph page
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('Institucijų grafa'), undefined, InstitutionIconFilled),
]);
</script>
