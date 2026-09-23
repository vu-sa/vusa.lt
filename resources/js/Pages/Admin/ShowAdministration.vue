<template>
  <OverviewPage
    :eyebrow="$t('shell.chrome.product')"
    :title="$t('shell.chrome.all_sections')"
    :lead="$t('shell.chrome.all_sections_lead')"
  >
    <template #actions>
      <div class="relative w-full sm:w-64">
        <SearchIcon aria-hidden="true" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          type="search"
          class="pl-8 pointer-coarse:h-11"
          :aria-label="$t('shell.chrome.find_section')"
          :placeholder="$t('shell.chrome.find_section')"
        />
      </div>
    </template>

    <!-- The Organizacija shortcuts that are tools rather than sections (duty_update, duty_periods). -->
    <OverviewSection v-if="filteredTools.length" :title="$t('shell.chrome.tools')">
      <NavigationTiles :items="filteredTools" data-workspace="tools" />
    </OverviewSection>

    <OverviewSection v-for="workspace in filteredWorkspaces" :key="workspace.key" :title="$t(workspace.label)">
      <p class="-mt-1 text-sm text-muted-foreground">
        {{ $t(workspace.description) }}
      </p>
      <NavigationTiles :items="workspace.tiles" :data-workspace="workspace.key" />
    </OverviewSection>

    <EmptyState
      v-if="!hasVisibleItems"
      mode="no-results"
      :title="$t('shell.chrome.no_sections')"
      :description="$t('Bandykite pakeisti paieškos kriterijus arba filtrus.')"
    />
  </OverviewPage>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { FileStack, SearchIcon } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState, NavigationTiles, OverviewSection, type NavigationTileItem } from '@/Components/Patterns';
import { Input } from '@/Components/ui/input';
import { sectionTile } from '@/Constants/adminSections';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const adminNavigation = computed(() => usePage().props.adminNavigation);

const searchQuery = ref('');

const matchesQuery = (tile: NavigationTileItem): boolean => {
  const query = searchQuery.value.trim().toLowerCase();

  return query === '' || [tile.label, tile.description ?? ''].some(text => text.toLowerCase().includes(query));
};

const filteredTools = computed<NavigationTileItem[]>(() => {
  const organizacija = adminNavigation.value?.workspaces.find(workspace => workspace.key === 'organizacija');

  return (organizacija?.createActions ?? [])
    .flatMap(action => action.target.kind === 'route'
      ? [{
          key: action.key,
          label: $t(action.label),
          description: action.description ? $t(action.description) : null,
          icon: (action.entityType ? getEntityTypeDefinition(action.entityType)?.icon : undefined) ?? FileStack,
          href: route(action.target.routeName),
        }]
      : [])
    .filter(matchesQuery);
});

const filteredWorkspaces = computed(() =>
  (adminNavigation.value?.workspaces ?? [])
    .map(workspace => ({
      key: workspace.key,
      label: workspace.label,
      description: workspace.description,
      tiles: workspace.sections.map(sectionTile).filter(matchesQuery),
    }))
    .filter(workspace => workspace.tiles.length > 0),
);

const hasVisibleItems = computed(() => filteredTools.value.length > 0 || filteredWorkspaces.value.length > 0);
</script>
