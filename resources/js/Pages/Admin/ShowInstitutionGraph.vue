<template>
  <div class="min-h-full" data-slot="workbench">
    <Head :title="$t('Institucijų vizualizacija')" />
    <header class="border-b border-border pb-6 pt-6 sm:pt-10">
      <p class="u-eyebrow">
        {{ $t('Institucijos') }}
      </p>
      <h1 class="u-display mt-3 break-words text-2xl sm:text-4xl lg:text-5xl">
        {{ $t('Institucijų vizualizacija') }}
      </h1>
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
import { Head } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import Graph from '@/Components/Graphs/InstitutionGraph.vue';

interface InstitutionGraphEdge {
  source: string;
  target: string;
  via?: 'direct' | 'type';
  kind: string;
  kind_label: string;
  cross_tenant: boolean;
  mutual: boolean;
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

</script>
