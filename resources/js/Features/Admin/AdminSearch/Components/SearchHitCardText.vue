<template>
  <SearchMatch v-if="field === 'title'" :match="titleMatch" :title="hit.title" />
  <template v-else>
    <SearchMatch :match="contextMatch" :title="hit.subtitle" />
    <template v-if="hit.meta">
      <span v-if="contextMatch || hit.subtitle" aria-hidden="true"> · </span>
      <SearchMatch :match="metaMatch" :title="hit.meta" />
    </template>
    <InactiveInstitutionBadge v-if="hit.inactiveInstitution" :active="false" />
    <StatusBadge v-if="hit.statusBadge" :status="hit.statusBadge" />
  </template>
</template>

<script setup lang="ts">
import { useSearchHitMatches } from '../Composables/useSearchHitMatches';
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { StatusBadge } from '@/Components/Patterns';
import SearchMatch from '@/Components/ui/SearchMatch.vue';

const props = defineProps<{
  hit: NormalizedSearchHit;
  field: 'title' | 'description';
}>();

const { titleMatch, metaMatch, contextMatch } = useSearchHitMatches(() => props.hit);
</script>
