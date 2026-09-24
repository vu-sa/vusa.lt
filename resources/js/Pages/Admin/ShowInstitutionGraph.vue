<template>
  <div class="min-h-full" data-slot="workbench">
    <Head :title="$t('Institucijų vizualizacija')" />
    <header class="border-b border-border pb-6 pt-6 sm:pt-10">
      <p class="u-eyebrow">{{ $t('Institucijos') }}</p>
      <h1 class="u-display mt-3 text-4xl sm:text-5xl">{{ $t('Institucijų vizualizacija') }}</h1>
    </header>
    <Graph
      :institution-relationships
      :institutions
      :types
      :type-relationships
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

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
