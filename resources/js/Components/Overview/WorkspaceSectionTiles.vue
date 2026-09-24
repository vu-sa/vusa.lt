<template>
  <OverviewSection
    v-if="tiles.length > 0"
    :title="$t('shell.chrome.sections')"
    :variant
    :icon="variant === 'home' ? LayoutGrid : undefined"
  >
    <NavigationTiles :items="tiles" :data-workspace="workspaceKey" />
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';

import { NavigationTiles, OverviewSection } from '@/Components/Patterns';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { sectionTile } from '@/Constants/adminSections';

const props = defineProps<{
  workspaceKey: string;
  /** `home` matches pages whose other sections use the home heading. */
  variant?: 'default' | 'home';
}>();

const { workspaces } = useAdminNavigation();

// The catalog already hides sections the user cannot `viewAny`; the overview is this page.
const tiles = computed(() => (workspaces.value.find(workspace => workspace.key === props.workspaceKey)?.sections ?? [])
  .filter(section => section.key !== 'apzvalga')
  .map(sectionTile));
</script>
